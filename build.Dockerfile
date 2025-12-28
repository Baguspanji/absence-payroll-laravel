FROM dunglas/frankenphp

RUN install-php-extensions \
    pcntl bcmath sockets gd exif zip pdo_mysql mysqli intl
# Add other PHP extensions here...

# Configure PHP to suppress deprecation warnings
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/custom.ini

# Install supervisor
RUN apt-get update && apt-get install -y supervisor && \
    mkdir -p /var/log/supervisor && \
    rm -rf /var/lib/apt/lists/*

# Add composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock /app/

COPY . /app

WORKDIR /app
RUN composer update --no-dev --no-scripts --no-autoloader --ignore-platform-reqs && \
    composer dump-autoload --optimize

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
