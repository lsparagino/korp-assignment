FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    postgresql-dev \
    git \
    unzip \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install bcmath gd zip pdo pdo_mysql intl opcache

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create system user to run composer and artisan commands
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy configs
COPY deployment/gcp/nginx.conf /etc/nginx/http.d/default.conf
COPY deployment/gcp/supervisord.conf /etc/supervisord.conf

# Expose port 8080
EXPOSE 8080

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
