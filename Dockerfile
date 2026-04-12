FROM php:8.2

# Install system dependencies + GD
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git unzip zip curl \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Install PHP dependencies
RUN composer install --ignore-platform-reqs

# Permissions (important for Laravel)
RUN chmod -R 777 storage bootstrap/cache

# Expose port
EXPOSE 8000

# Start Laravel
CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=$PORT