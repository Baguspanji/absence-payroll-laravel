FROM dunglas/frankenphp

RUN install-php-extensions \
    pcntl bcmath sockets gd exif zip
# Add other PHP extensions here...

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
RUN composer install --no-dev --optimize-autoloader

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ENTRYPOINT ["php", "artisan", "octane:frankenphp"]

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
