#!/usr/bin/env bash
# Called over SSH by .github/workflows/deploy-ssh.yml. Requires Linux and Bash.
set -Eeuo pipefail

deploy_path="$(printf '%s' "${1:?Missing encoded deployment path}" | base64 --decode)"
revision="${2:?Missing commit SHA}"
[[ "$deploy_path" = /* && "$deploy_path" != / ]] || { echo 'An absolute project path is required.' >&2; exit 1; }
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || { echo 'Invalid commit SHA.' >&2; exit 1; }
cd -- "$deploy_path"

for command in git php composer npm flock; do
    command -v "$command" >/dev/null || { echo "Missing command: $command" >&2; exit 1; }
done
test -f .env || { echo 'Configure the server .env first.' >&2; exit 1; }
test -f vendor/autoload.php || { echo 'Run the initial Composer installation first.' >&2; exit 1; }
test -w storage && test -w bootstrap/cache
git rev-parse --is-inside-work-tree >/dev/null
exec 9>"$(git rev-parse --git-path crou-deploy.lock)"
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
test -z "$(git status --porcelain --untracked-files=normal)" || {
    echo 'The server checkout has local changes. Resolve them before deployment.' >&2
    exit 1
}

export GIT_TERMINAL_PROMPT=0
git fetch --no-tags origin '+refs/heads/main:refs/remotes/origin/main'
git cat-file -e "$revision^{commit}"
git merge-base --is-ancestor "$revision" origin/main || {
    echo 'The requested commit is not on origin/main.' >&2; exit 1;
}
git merge-base --is-ancestor HEAD "$revision" || {
    echo 'The server cannot fast-forward to this commit; deployment stopped.' >&2; exit 1;
}

maintenance_started=false
on_error() {
    status=$?
    if [[ "$maintenance_started" == true ]]; then
        echo 'Deployment failed. Maintenance remains enabled; inspect the failure before running php artisan up.' >&2
    fi
    exit "$status"
}
trap on_error ERR

php artisan down --retry=60
maintenance_started=true
git merge --ff-only "$revision"
# Drop stale configuration before Composer runs Laravel package discovery.
php artisan config:clear
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --no-audit --no-fund
npm run build

# Run pending migrations only. Never reset the database or run seeders here.
php artisan migrate --force --no-interaction
php artisan storage:link
php artisan config:cache
php artisan view:cache
php artisan queue:restart
php artisan up
maintenance_started=false
echo "Deployment completed: $(git rev-parse HEAD)"
