FROM dunglas/frankenphp:1-php8.4-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends unzip \
  && rm -rf /var/lib/apt/lists/* \
  && install-php-extensions \
       bcmath \
       exif \
       gd \
       intl \
       mbstring \
       opcache \
       pdo_mysql \
       zip

RUN { \
      echo 'date.timezone = UTC'; \
      echo 'error_log = /dev/stderr'; \
      echo 'max_execution_time = 60'; \
      echo 'memory_limit = 256M'; \
      echo 'opcache.enable = 1'; \
      echo 'opcache.max_accelerated_files = 20000'; \
      echo 'opcache.memory_consumption = 128'; \
      echo 'opcache.validate_timestamps = 0'; \
      echo 'post_max_size = 64M'; \
      echo 'upload_max_filesize = 64M'; \
    } > /usr/local/etc/php/conf.d/cabinet.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
  && mkdir -p logs tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/sessions storage/uploads/private storage/uploads/public \
  && chmod -R ug+rwX logs tmp storage

COPY Caddyfile /etc/caddy/Caddyfile
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
