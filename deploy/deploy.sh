#!/usr/bin/env bash
#
# Urbanflaky — one-command production deploy.
#
# Flow:  you `git push origin main`  ->  run this on the server  ->  live.
#
# Install (once, as deploy; the runnable copy lives OUTSIDE the git tree so a
# deploy can't overwrite the script while it is running):
#   sudo cp deploy/deploy.sh /usr/local/bin/uf-deploy && sudo chmod +x /usr/local/bin/uf-deploy
#
# Run:
#   uf-deploy                      # while SSH'd into the server
#   ssh deploy@187.127.148.88 uf-deploy   # from your machine
#
# Safe to re-run. It hard-resets the working tree to origin/main (discarding the
# regenerable build artifacts a deploy produces) but never touches .env, the
# storage/app/public media, or caches — those are gitignored/untracked.

set -euo pipefail
APP=/var/www/urbanflaky
cd "$APP"

step() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31m!! deploy aborted: %s\033[0m\n' "$*" >&2; php artisan up || true; exit 1; }
trap 'fail "step failed (see output above); site brought back UP"' ERR

step "Maintenance mode ON"
php artisan down --retry=15 || true

step "Fetch + hard-reset to origin/main"
git fetch origin --prune
git reset --hard origin/main
git --no-pager log --oneline -1

step "PHP deps (composer, no-dev)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

step "Frontend build"
if [ -f package-lock.json ]; then npm ci --no-audit --no-fund || npm install --no-audit --no-fund
else npm install --no-audit --no-fund; fi
npm run build

step "Database migrations"
php artisan migrate --force

step "Re-cache config / routes / events (and clear views + response cache)"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan responsecache:clear || true

step "Image derivative cache (public/cache writable by PHP-FPM)"
# The resizer writes each /cache/<template>/... derivative here so nginx can
# serve it statically. PHP-FPM runs as www-data, so the tree must be group
# www-data + setgid (new dirs inherit the group); same scheme as storage/.
# Best-effort: a failure only means images keep being resized in PHP.
mkdir -p public/cache
{ chgrp -R www-data public/cache \
  && find public/cache -type d -exec chmod 2775 {} + \
  && find public/cache -type f -exec chmod 664 {} + ; } \
  || echo "!! could not make public/cache group-writable for www-data (images still work, just slower)"

step "Restart queue worker + PHP-FPM"
sudo supervisorctl restart 'urbanflaky-worker:*'
sudo systemctl restart php8.3-fpm

step "Maintenance mode OFF"
php artisan up

trap - ERR
step "Smoke test"
code=$(curl -sS -o /dev/null -w '%{http_code}' https://urbanflaky.in/ || echo 000)
echo "https://urbanflaky.in/ -> $code"
P=$(php artisan tinker --execute="echo DB::table('product_images')->value('path');" 2>/dev/null | tr -d '[:space:]' || true)
if [ -n "$P" ]; then
  img=$(curl -sS -o /dev/null -w '%{http_code}' "https://urbanflaky.in/cache/medium/$P" || echo 000)
  echo "product image (/cache/medium) -> $img"
fi
[ "$code" = "200" ] && printf '\n\033[1;32m==> DEPLOY COMPLETE\033[0m\n' || fail "homepage returned $code"
