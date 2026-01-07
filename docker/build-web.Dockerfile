FROM bagusp/absence:latest

# Create PHP configuration for error logging
RUN mkdir -p /usr/local/etc/php/conf.d && \
    echo "error_log = /dev/stderr" > /usr/local/etc/php/conf.d/99-docker.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/99-docker.ini

COPY composer.json composer.lock /app/

COPY . /app

WORKDIR /app

RUN composer update --no-dev --no-scripts --no-autoloader --ignore-platform-reqs && \
    composer dump-autoload --optimize

COPY docker/config/supervisord-web.conf /etc/supervisor/conf.d/supervisord.conf

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
