#!/usr/bin/env bash
set -Eeuo pipefail

deploy_path="$(printf '%s' "${1:?Missing encoded deployment path}" | base64 --decode)"
revision="${2:?Missing commit SHA}"
stage="${3:?Missing staging directory}"
[[ "$deploy_path" = /* && "$deploy_path" != / && "$deploy_path" != "$HOME" ]] || exit 1
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || exit 1
[[ "$stage" =~ ^/tmp/crou-release\.[a-zA-Z0-9]+$ && -d "$stage" ]] || exit 1
trap 'status=$?; rm -rf -- "$stage"; if (( status != 0 )); then echo "Deployment failed; do not disable maintenance before checking the logs." >&2; fi; exit "$status"' EXIT
export PATH="$HOME/.local/bin:$PATH"
for command in php tar flock; do
    command -v "$command" >/dev/null || { echo "Missing command: $command" >&2; exit 1; }
done
if command -v composer >/dev/null; then
    composer_command=(composer)
elif [[ -r "$HOME/composer.phar" ]]; then
    composer_command=(php "$HOME/composer.phar")
else
    echo 'Install Composer or provide ~/composer.phar.' >&2; exit 1
fi
"${composer_command[@]}" --version
mkdir -p -- "$deploy_path"
cd -- "$deploy_path"
exec 9>.deploy.lock
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
if [[ ! -f .env && ! -f "$stage/production.env" ]]; then
    echo 'First installation: configure the ENV_PRODUCTION GitHub secret with the production .env, including a stable APP_KEY and database credentials.' >&2
    exit 1
fi
# Existing installations enter maintenance before any files are changed.
if [[ -f artisan && -f vendor/autoload.php ]]; then
    php artisan down --retry=60
fi
mkdir -p storage/framework/{cache/data,sessions,views} storage/{logs,app/public,app/private} bootstrap/cache
# A static maintenance response also protects a first installation until it is ready.
cat > storage/framework/maintenance.php <<'PHP'
<?php http_response_code(503); header('Retry-After: 60'); echo 'Maintenance'; exit;
PHP
if [[ ! -f .env ]]; then
    cp "$stage/production.env" .env
    chmod 600 .env
fi
mkdir "$stage/app"
tar -xzf "$stage/release.tar.gz" -C "$stage/app"
# public/files also contains versioned frontend code, not just uploaded files.
# Update only these static directories; leave user content and other paths intact.
for asset_directory in assets/css assets/css_web assets/js assets/js_web \
    assets/fonts assets/icon assets/pages bower_components; do
    if [[ -d "$stage/app/public/files/$asset_directory" ]]; then
        mkdir -p "public/files/$asset_directory"
        cp -a "$stage/app/public/files/$asset_directory/." "public/files/$asset_directory/"
    fi
done
# Keep uploaded images while installing static images that are not present yet.
if [[ -d "$stage/app/public/admin/assets/images" ]]; then
    mkdir -p public/admin/assets/images
    cp -an "$stage/app/public/admin/assets/images/." public/admin/assets/images/
fi
# Authentication artwork is versioned, unlike the other persistent public/files.
# Install missing artwork without replacing files already managed on the server.
if [[ -d "$stage/app/public/files/assets/images/auth" ]]; then
    mkdir -p public/files/assets/images/auth
    cp -an "$stage/app/public/files/assets/images/auth/." public/files/assets/images/auth/
fi
tar -C "$stage/app" --exclude='./storage' --exclude='./bootstrap/cache' \
    --exclude='./.env*' --exclude='./.git' --exclude='./public/storage' \
    --exclude='./public/files' --exclude='./public/admin/assets/images' \
    -cf - . | tar -xf -
# Composer runs without Laravel scripts until dependencies are complete.
"${composer_command[@]}" install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts
# The historical sessions table cannot store Laravel string IDs and user UUIDs.
# Match the working local setup without changing the production database schema.
php <<'PHP'
<?php
$path = '.env';
$content = file_get_contents($path);
if ($content === false) {
    throw new RuntimeException('Cannot read server environment.');
}
$updated = preg_replace('/^SESSION_DRIVER=[^\r\n]*/m', 'SESSION_DRIVER=file', $content, -1, $count);
if ($count === 0) {
    $updated = rtrim($content)."\nSESSION_DRIVER=file\n";
}
if (file_put_contents($path, $updated, LOCK_EX) === false) {
    throw new RuntimeException('Cannot configure file sessions.');
}
PHP
php artisan config:clear
php artisan package:discover --no-interaction
php scripts/check-production-database.php --initialize-empty
php artisan migrate --force --no-interaction
php artisan storage:link
php artisan config:cache
php artisan view:cache
php artisan queue:restart
php artisan up
printf '%s\n' "$revision" > .deployed-revision
echo "Deployment completed: $revision"
