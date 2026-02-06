#!/bin/sh
set -e

# Run Laravel optimizations (allow failure)
echo "Running Laravel optimizations..."
php artisan config:cache || echo "Config caching failed, continuing..."
php artisan route:cache || echo "Route caching failed, continuing..."
php artisan view:cache || echo "View caching failed, continuing..."

# Set permissions for cached files (just in case)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
