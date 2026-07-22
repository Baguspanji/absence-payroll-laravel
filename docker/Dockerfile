FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libssl-dev \
    supervisor \
    wget \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       bcmath \
       pcntl \
       sockets \
       gd \
       exif \
       zip \
       pdo_mysql \
       mysqli \
       intl \
       opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN { \
    echo "error_log = /dev/stderr"; \
    echo "log_errors = On"; \
    echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT"; \
} > /usr/local/etc/php/conf.d/99-docker.ini

RUN a2enmod rewrite

COPY docker/config/apache-laravel.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /app

COPY composer.json composer.lock /app/

RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

COPY . /app

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

COPY docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

HEALTHCHECK --interval=1m30s --timeout=10s --retries=3 --start-period=40s \
    CMD wget -qO- http://localhost:80/up || exit 1

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
