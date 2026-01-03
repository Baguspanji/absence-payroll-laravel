FROM bagusp/absence:latest

COPY composer.json composer.lock /app/

COPY . /app

WORKDIR /app

RUN composer update --no-dev --no-scripts --no-autoloader --ignore-platform-reqs && \
    composer dump-autoload --optimize

# Copy supervisor config
COPY docker/config/supervisord-scheduler.conf /etc/supervisor/conf.d/supervisord.conf

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
