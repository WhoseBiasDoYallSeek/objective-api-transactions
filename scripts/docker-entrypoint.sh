#!/bin/sh
set -eu

# ensure logs folder exists
mkdir -p /var/www/logs /var/www/var
chown -R www-data:www-data /var/www/logs /var/www/var || true

# start crond in background (Alpine's busybox crond)
crond -b -L /var/www/logs/cron.log || true

# start the PHP built-in server
exec php -S 0.0.0.0:8080 -t public
