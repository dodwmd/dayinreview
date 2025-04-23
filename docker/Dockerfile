FROM php:8.2-fpm-alpine

LABEL maintainer="Day in Review"
LABEL org.opencontainers.image.source="https://github.com/dodwmd/dayinreview"

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apk --no-cache add \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Set file permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage

# Run composer install
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Generate optimized files
RUN php artisan optimize

# Expose port 9000 (PHP-FPM)
EXPOSE 9000

# Start PHP-FPM server
CMD ["php-fpm"]
