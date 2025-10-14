FROM dunglas/frankenphp

RUN install-php-extensions \
    pcntl pdo pdo_sqlite mbstring tokenizer xml ctype json curl bcmath sockets opcache gd exif fileinfo zip
# Add other PHP extensions here...

# Add composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- \
--install-dir=/usr/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock /app/

COPY . /app

WORKDIR /app
RUN composer install --no-dev --optimize-autoloader


ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
