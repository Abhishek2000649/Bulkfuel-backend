FROM php:8.2

# Install system dependencies + GD + MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git unzip zip curl \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project
COPY . .

# Install dependencies
RUN composer install --ignore-platform-reqs

# Create image upload folder (IMPORTANT)
RUN mkdir -p public/images/profile

# Fix permissions (IMPORTANT for uploads)
RUN chmod -R 777 public/images
RUN chmod -R 777 storage bootstrap/cache

# Expose Railway dynamic port
EXPOSE 8000

# Start Laravel (FIXED CMD)
CMD sh -c "php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"