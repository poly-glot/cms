FROM dunglas/frankenphp:1-php8.4-bookworm AS build

ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
       git \
       unzip \
       libicu-dev \
       libzip-dev \
       libonig-dev \
       libxml2-dev \
       libpng-dev \
       libjpeg-dev \
       libfreetype6-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" \
       intl \
       pdo_mysql \
       mbstring \
       zip \
       opcache \
       gd \
       bcmath \
       exif \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
  && mkdir -p logs tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/sessions storage/uploads \
  && chown -R www-data:www-data logs tmp storage \
  && chmod -R ug+rwX logs tmp storage


FROM dunglas/frankenphp:1-php8.4-bookworm

ENV DEBIAN_FRONTEND=noninteractive
ENV SERVER_NAME=":8080"

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
       libicu72 \
       libzip4 \
       libonig6 \
       libxml2 \
       libpng16-16 \
       libjpeg62-turbo \
       libfreetype6 \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" \
       intl \
       pdo_mysql \
       mbstring \
       zip \
       opcache \
       gd \
       bcmath \
       exif \
  && rm -rf /var/lib/apt/lists/*

RUN { \
      echo 'memory_limit = 256M'; \
      echo 'upload_max_filesize = 64M'; \
      echo 'post_max_size = 64M'; \
      echo 'max_execution_time = 60'; \
      echo 'date.timezone = UTC'; \
      echo 'opcache.enable = 1'; \
      echo 'opcache.validate_timestamps = 0'; \
      echo 'opcache.memory_consumption = 128'; \
      echo 'opcache.max_accelerated_files = 20000'; \
      echo 'error_log = /dev/stderr'; \
    } > /usr/local/etc/php/conf.d/cabinet.ini

WORKDIR /app
COPY --from=build --chown=www-data:www-data /app /app
COPY Caddyfile /etc/caddy/Caddyfile
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
