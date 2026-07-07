FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN apt-get update && apt-get install -y libssl-dev zlib1g-dev pkg-config && pecl install redis && docker-php-ext-enable redis && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Set composer environment variables to prevent memory limits
ENV COMPOSER_MEMORY_LIMIT=-1

# Install composer dependencies
RUN composer update --no-interaction --optimize-autoloader --no-dev --no-scripts --ignore-platform-reqs

# Expose port 8000
EXPOSE 8000

# Start server
CMD if [ ! -f /var/www/.env ]; then cp /var/www/.env.example /var/www/.env; fi && php artisan key:generate --force && php artisan serve --host=0.0.0.0 --port=8000
