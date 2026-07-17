#!/bin/sh
set -e

# storage/ is mounted as an empty named volume on first boot, which hides
# the directory structure baked into the image - recreate it every start
# (cheap, idempotent) so Blade's compiled-view cache etc. always has
# somewhere writable to go.
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
    storage/logs storage/app/public
chown -R www-data:www-data storage bootstrap/cache

case "$1" in
    app)
        php artisan migrate --force
        php artisan storage:link || true
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        exec apache2-foreground
        ;;
    queue)
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
