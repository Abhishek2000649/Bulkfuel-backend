FROM php:8.2

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git unzip zip curl \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --ignore-platform-reqs

RUN chmod -R 777 storage bootstrap/cache

CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=$PORT