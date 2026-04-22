FROM php:8.2

# Install dependencies + supervisor + pcntl (IMPORTANT)
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    git unzip zip curl supervisor \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --ignore-platform-reqs

# Create folder
RUN mkdir -p public/images/profile

# Secure permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080

# Run migrations + supervisor
CMD sh -c "php artisan migrate --force || true && supervisord -c /etc/supervisor/conf.d/supervisord.conf"