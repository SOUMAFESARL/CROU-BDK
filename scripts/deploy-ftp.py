"""Transfer the prepared package with lftp; never delete remote files."""
import os
from pathlib import Path
import re
import subprocess
import sys


def quote(value):
    if any(c in value for c in '\r\n\x00'):
        raise ValueError('FTP settings must not contain line breaks')
    return '"' + re.sub(r'([\\"$`])', r'\\\1', value) + '"'


def commands(env):
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
        # No --delete: .env, uploads, caches and all other remote-only files survive.
        'mirror --reverse --continue --verbose --parallel=2 --no-perms '
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
        'bye',
        '',
    ])


if __name__ == '__main__':
    if not os.environ.get('LFTP_PASSWORD'):
        raise ValueError('Missing FTP password')
    if not Path('deploy-package/vendor/autoload.php').is_file():
        raise ValueError('Missing prepared production package')
    # Password stays in the environment and is never inserted into commands/logs.
    result = subprocess.run(['lftp', '--norc'], input=commands(os.environ), text=True)
    if result.returncode:
        print('FTP transfer failed. For certificate hostname errors, set development/FTP_SERVER '
              'to the host covered by the FTP certificate (web53.lws-hosting.com for the current LWS server). '
              'Inspect the lftp error above; TLS verification remains enabled.', file=sys.stderr)
        sys.exit(result.returncode)
