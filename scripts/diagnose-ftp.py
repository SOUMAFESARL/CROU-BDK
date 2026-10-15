"""Read development error categories over verified FTPS; never print raw logs."""
import ftplib
import os
import re
import ssl
import sys
from pathlib import Path
from io import BytesIO


context = ssl.create_default_context()
context.minimum_version = ssl.TLSVersion.TLSv1_2
context.maximum_version = ssl.TLSVersion.TLSv1_2
with ftplib.FTP_TLS(context=context, timeout=30) as ftp:
    ftp.connect(os.environ['FTP_SERVER'], 21)
    ftp.login(os.environ['FTP_USERNAME'], os.environ['FTP_PASSWORD'])
    ftp.prot_p()
    ftp.cwd(os.environ['FTP_SERVER_DIR'])
    root = ftp.pwd()
    if '--repair-auth-configuration' in sys.argv:
        # Keep credentials in memory; never include environment contents in logs.
        environment = bytearray()
        ftp.retrbinary('RETR .env', environment.extend)
        content = environment.decode('utf-8-sig')
        content, count = re.subn(r'^SESSION_DRIVER=[^\r\n]*', 'SESSION_DRIVER=file', content, flags=re.M)
        if not count:
            content = content.rstrip() + '\nSESSION_DRIVER=file\n'
        ftp.storbinary('STOR .env', BytesIO(content.encode('utf-8')))
        ftp.storbinary('STOR config/auth.php', BytesIO(Path('config/auth.php').read_bytes()))
        # Only remove the generated config cache, never application data.
        try:
            ftp.size('bootstrap/cache/config.php')
        except ftplib.error_perm:
            pass
        else:
            ftp.delete('bootstrap/cache/config.php')
        print('Configured file sessions and users provider; cleared generated config cache.')
    if '--repair-web-root' in sys.argv:
        ftp.storbinary('STOR .htaccess', BytesIO(Path('.htaccess').read_bytes()))
        print('Installed project-root routing to public/.')
    for directory in ['bootstrap/cache', 'storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache/data', 'storage/logs']:
        if '--repair-directories' in sys.argv:
            current = root.rstrip('/')
            for part in directory.split('/'):
                current += '/' + part
                try:
                    ftp.mkd(current)
                except ftplib.error_perm:
                    # Only ignore an existing directory after verifying it.
                    ftp.cwd(current)
                ftp.cwd(root)
        try:
            ftp.cwd(root + '/' + directory)
            print(directory + ': directory present')
        except ftplib.error_perm:
            print(directory + ': DIRECTORY MISSING')
        finally:
            ftp.cwd(root)
    for path in ['error_log', 'public/error_log', 'storage/logs/laravel.log']:
        chunks = bytearray()
        def collect(data):
            chunks.extend(data)
            if len(chunks) > 1024 * 1024:
                del chunks[:-1024 * 1024]
        try:
            ftp.retrbinary('RETR ' + path, collect)
        except ftplib.error_perm:
            print(path + ': unavailable')
            continue
        text = chunks.decode('utf-8', errors='replace')
        # Report known categories only; logs can contain SQL, credentials and PII.
        categories = {
            'PHP_VERSION': r'(?:Composer detected issues.*PHP|require.*PHP version|PHP Parse error|syntax error, unexpected)',
            'AUTOLOAD_MISSING': r'(?:Failed opening required|failed to open stream).*?(?:vendor|autoload)',
            'APP_KEY_MISSING': r'No application encryption key',
            'DB_ACCESS': r'(?:Access denied for user|SQLSTATE\[HY000\] \[1045\])',
            'DB_MISSING': r'(?:Unknown database|Base table or view not found)',
            'PERMISSIONS': r'(?:Permission denied|not writable)',
            'VITE_MANIFEST': r'Vite manifest not found',
            'AUTH_PROVIDER': r'(?:User provider|UserProvider|retrieveByCredentials)',
            'PHP_MEMORY': r'Allowed memory size.*exhausted',
            'PHP_EXTENSION': r'(?:could not find driver|Call to undefined function)',
            'SESSION_SCHEMA': r'(?:Incorrect integer value.*sessions|Field.*user_id.*default value)',
            'BOOTSTRAP_CACHE': r'bootstrap[/\\]cache.*(?:writable|present)',
            'COMPOSER_CLASS_MISSING': r'Class .*not found',
        }
        recent = text[-100000:]
        found = [name for name, pattern in categories.items() if re.search(pattern, recent, re.I)]
        print(path + ': ' + (', '.join(found) if found else 'No recognized error category'))
        # Print exception class names only, without exception messages or traces.
        classes = re.findall(r'([A-Za-z_\\]+(?:Exception|Error)):', recent)
        if classes:
            print('Recent exception classes: ' + ', '.join(dict.fromkeys(classes[-10:])))
        targets = re.findall(r'Target class \[([A-Za-z_\\][A-Za-z0-9_\\]*)\] does not exist', recent)
        if targets:
            print('Missing container classes: ' + ', '.join(dict.fromkeys(targets[-10:])))
        dates = re.findall(r'\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\]', recent)
        if dates:
            print('Last log timestamp: ' + dates[-1])
    for path in ['vendor/autoload.php', 'vendor/composer/autoload_real.php', 'vendor/composer/platform_check.php', 'vendor/laravel/framework/src/Illuminate/View/ViewServiceProvider.php']:
        try:
            data = bytearray()
            ftp.retrbinary('RETR ' + path, data.extend)
            print(path + ': present')
            if path.endswith('platform_check.php'):
                versions = re.findall(r'PHP_VERSION_ID\s*(?:>=|<)\s*(\d+)', data.decode(errors='replace'))
                print('Composer PHP version thresholds: ' + ', '.join(versions))
        except ftplib.error_perm:
            print(path + ': MISSING')
