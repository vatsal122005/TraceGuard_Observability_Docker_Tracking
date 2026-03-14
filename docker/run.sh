#!/bin/sh

cd /var/www/html

# Wait for DB to be ready (docker-compose handles this usually, but good to have)
# echo "Caching configuration..."
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
