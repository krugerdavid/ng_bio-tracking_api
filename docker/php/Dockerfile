FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies (bash para shell interactivo, $PHPIZE_DEPS para compilar extensiones)
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    $PHPIZE_DEPS

# Install PHP extensions (pdo_mysql para conectar a MySQL en Docker/producción)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# PCOV para code coverage (php artisan test --coverage)
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
# RUN addgroup -g 1000 www && adduser -u 1000 -G www -D www

# Set permissions
# RUN chown -R www:www /var/www

# Change current user to www
# USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
