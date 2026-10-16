"""Transfer the prepared package with lftp; never delete remote files."""
import os
from pathlib import Path
import re
import subprocess
import sys
from concurrent.futures import ThreadPoolExecutor


def quote(value):
    if any(c in value for c in '\r\n\x00'):
        raise ValueError('FTP settings must not contain line breaks')
    return '"' + re.sub(r'([\\"$`])', r'\\\1', value) + '"'


def commands(env, vendor_only=False):
    host = env['FTP_SERVER']
    if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9.-]*', host):
        raise ValueError('FTP_SERVER must be a hostname or IPv4 address')
    remote = env['FTP_SERVER_DIR']
    if remote in ('/', './', '') or not remote.endswith('/') or '..' in remote.split('/'):
        raise ValueError('Use a dedicated FTP destination ending in /')
    protocol = env.get('FTP_PROTOCOL', 'ftps')
    if protocol not in ('ftp', 'ftps'):
        raise ValueError('Supported protocols: ftp, ftps (explicit TLS on port 21)')
    tls = 'yes' if protocol == 'ftps' else 'no'
    return '\n'.join([
        'set cmd:fail-exit yes',
        'set cmd:verify-path yes',
        'set net:timeout 30',
        'set net:max-retries 5',
        'set net:reconnect-interval-base 5',
        'set net:reconnect-interval-max 30',
        'set ftp:passive-mode yes',
        f'set ftp:ssl-allow {tls}',
        f'set ftp:ssl-force {tls}',
        f'set ftp:ssl-protect-data {tls}',
        'set ssl:verify-certificate yes',
        'set ssl:check-hostname yes',
        'set ssl:priority "NORMAL:-VERS-SSL3.0:-VERS-TLS1.0:-VERS-TLS1.1:-VERS-TLS1.3"',
        f'open --user {quote(env["FTP_USERNAME"])} --env-password -p 21 {quote("ftp://" + host)}',
        # These persistent directories are excluded from the file mirror but
        # must exist on a first installation for Laravel to bootstrap.
        # Some FTP servers return 550 even for mkdir -p on existing folders.
        # Accept that only if CWD verifies access, then restore the login folder
        # so relative FTP_SERVER_DIR values keep their original meaning.
        *[f'mkdir -p {quote(remote + directory)} || cd {quote(remote + directory)}\ncd' for directory in [
            'bootstrap/cache', 'storage/framework/views', 'storage/framework/sessions',
            'storage/framework/cache/data', 'storage/logs', 'storage/app/public',
            'storage/app/private',
        ]],
        *([
        'mirror --reverse --continue --verbose --parallel=4 --no-perms '
        f'./deploy-package/vendor/ {quote(remote + "vendor/")}',
        ] if vendor_only else [
        # Vendor is handled by a separate lftp process with four parallel files.
        # No --delete: .env, uploads, caches and all remote-only files survive.
        'mirror --reverse --continue --verbose --parallel=2 --no-perms '
        '--exclude-glob vendor/ '
        '--exclude-glob .env* --exclude-glob .git* '
        '--exclude-glob storage/ --exclude-glob bootstrap/cache/ '
        '--exclude-glob public/storage/ --exclude-glob public/files/assets/images/ '
        '--exclude-glob public/admin/assets/images/ --exclude-glob public/hot '
        f'./deploy-package/ {quote(remote)}',
        # Static artwork is installed only when absent; keep customized logos.
        'mirror --reverse --only-missing --verbose --no-perms '
        f'./deploy-package/public/admin/assets/images/logos/ {quote(remote + "public/admin/assets/images/logos/")}',
        'mirror --reverse --only-missing --verbose --no-perms '
        f'./deploy-package/public/files/assets/images/auth/ {quote(remote + "public/files/assets/images/auth/")}',
        ]),
        'bye',
        '',
    ])


if __name__ == '__main__':
    if not os.environ.get('LFTP_PASSWORD'):
        raise ValueError('Missing FTP password')
    if not Path('deploy-package/vendor/autoload.php').is_file():
        raise ValueError('Missing prepared production package')
    # Password stays in the environment and is never inserted into commands/logs.
    def transfer(vendor_only):
        label = 'vendor' if vendor_only else 'application'
        print(f'Starting {label} FTP transfer.', flush=True)
        result = subprocess.run(['lftp', '--norc'], input=commands(os.environ, vendor_only), text=True)
        print(f'{label} FTP transfer exited with code {result.returncode}.', flush=True)
        return result.returncode

    # Wait for both transfers; a failure in either must fail the workflow.
    with ThreadPoolExecutor(max_workers=2) as executor:
        results = list(executor.map(transfer, [False, True]))
    if any(results):
        print('FTP transfer failed. For certificate hostname errors, set development/FTP_SERVER '
              'to the host covered by the FTP certificate (web53.lws-hosting.com for the current LWS server). '
              'Inspect the lftp error above; TLS verification remains enabled.', file=sys.stderr)
        sys.exit(1)
