FROM php:8.2

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git unzip zip curl \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /app

COPY . .

RUN composer install --ignore-platform-reqs

CMD php artisan serve --host=0.0.0.0 --port=$PORT