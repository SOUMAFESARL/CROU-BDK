#!/usr/bin/env bash
set -Eeuo pipefail

# Some hosts serve the project root and bypass .htaccess for static URLs.
# Keep Vite's /build/ URLs working without copying generated assets twice.
[[ -f artisan && -f public/index.php ]] || {
    echo 'Run this script from the Laravel project directory.' >&2
    exit 1
}

for directory in build css js fonts files; do
    [[ -d "public/$directory" ]] || continue
    if [[ -L "$directory" ]]; then
        [[ "$(readlink "$directory")" == "public/$directory" ]] || {
            echo "Unexpected asset symlink: $directory; inspect it before deploying." >&2
            exit 1
        }
    elif [[ -e "$directory" ]]; then
        echo "Asset path already exists: $directory; inspect it before deploying." >&2
        exit 1
    else
        ln -s "public/$directory" "$directory"
    fi
done
