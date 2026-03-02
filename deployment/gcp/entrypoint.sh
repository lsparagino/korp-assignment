#!/bin/sh
set -e

# Run Laravel optimizations (allow failure)
echo "Running Laravel optimizations..."
php artisan config:cache || echo "Config caching failed, continuing..."
php artisan route:cache || echo "Route caching failed, continuing..."
php artisan view:cache || echo "View caching failed, continuing..."

# Start supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
