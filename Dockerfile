FROM php:8.2

# Install system dependencies + GD (with JPEG) + MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    git unzip zip curl \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --ignore-platform-reqs

# Create folder
RUN mkdir -p public/images/profile

# Permissions
RUN chmod -R 777 public/images
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD sh -c "php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"