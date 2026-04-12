FROM php:8.2

# Install dependencies + GD + MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git unzip zip curl \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Install PHP dependencies
RUN composer install --ignore-platform-reqs

# Permissions
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}