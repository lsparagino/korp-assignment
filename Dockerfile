FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    icu-dev \
    libpng-dev \
    libzip-dev \
    nginx \
    oniguruma-dev \
    postgresql-dev \
    supervisor \
    unzip \
    && docker-php-ext-install bcmath gd zip pdo pdo_mysql intl opcache

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files (filtered by .dockerignore)
# NOSONAR - recursive copy is safe, controlled by .dockerignore
COPY . .

# Install dependencies && Create system user to run composer and artisan commands
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Copy configs
COPY deployment/gcp/nginx.conf /etc/nginx/http.d/default.conf
COPY deployment/gcp/supervisord.conf /etc/supervisord.conf
COPY deployment/gcp/php-optimizations.ini /usr/local/etc/php/conf.d/optimizations.ini
COPY deployment/gcp/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set execution permissions and grant www-data access to nginx runtime directories
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/cache/nginx /var/log/nginx \
    && chown -R www-data:www-data /var/cache/nginx \
    && chown -R www-data:www-data /var/log/nginx \
    && touch /var/run/nginx.pid \
    && chown www-data:www-data /var/run/nginx.pid

# Run as non-root user
USER www-data

# Expose port 8080
EXPOSE 8080

# Use optimized entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
