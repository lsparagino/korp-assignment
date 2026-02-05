#!/bin/sh
set -e

# Run Laravel optimizations
echo "Running Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions for cached files (just in case)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
