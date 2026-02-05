FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS \
    postgresql-dev \
    rabbitmq-c-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        intl \
        bcmath \
        opcache \
        pcntl \
        sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install AMQP extension for RabbitMQ
RUN pecl install amqp && docker-php-ext-enable amqp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/backend

# Create system user
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www

# Set proper permissions
RUN chown -R www:www /var/www

USER www

EXPOSE 9000
CMD ["php-fpm"]
