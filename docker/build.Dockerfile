FROM dunglas/frankenphp:1-php8.4-alpine

RUN install-php-extensions \
    pcntl bcmath sockets gd exif zip pdo_mysql mysqli intl
# Add other PHP extensions here...

# Configure PHP to suppress deprecation warnings
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/custom.ini

# Install supervisor
RUN apk add --no-cache supervisor && \
    mkdir -p /var/log/supervisor

# Add composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

EXPOSE 8000
