FROM php:8.2

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    git unzip zip curl supervisor \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pcntl

# 🔥 IMPORTANT: PHP upload limit yahi set karo
RUN echo "upload_max_filesize=10M" >> /usr/local/etc/php/php.ini
RUN echo "post_max_size=12M" >> /usr/local/etc/php/php.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --ignore-platform-reqs

# Folder
RUN mkdir -p public/images/profile

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080

# Run
CMD sh -c "php artisan migrate --force || true && supervisord -c /etc/supervisor/conf.d/supervisord.conf"