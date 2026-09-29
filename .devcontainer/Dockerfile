FROM mcr.microsoft.com/devcontainers/php:1-8.4-bookworm

ENV DEBIAN_FRONTEND=noninteractive

RUN rm -f /etc/apt/sources.list.d/yarn.list \
  && apt-get update \
  && apt-get install -y --no-install-recommends \
       libicu-dev \
       libzip-dev \
       libonig-dev \
       libxml2-dev \
       libpng-dev \
       libjpeg-dev \
       libfreetype6-dev \
       zip \
       unzip \
       default-mysql-client \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" \
       intl \
       pdo_mysql \
       mysqli \
       mbstring \
       zip \
       opcache \
       gd \
       bcmath \
       exif \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && rm -rf /var/lib/apt/lists/*

# Playwright/Chromium system libraries (Debian Bookworm)
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
       libnss3 \
       libnspr4 \
       libatk1.0-0 \
       libatk-bridge2.0-0 \
       libcups2 \
       libdrm2 \
       libxkbcommon0 \
       libxcomposite1 \
       libxdamage1 \
       libxfixes3 \
       libxrandr2 \
       libgbm1 \
       libpango-1.0-0 \
       libcairo2 \
       libasound2 \
       libatspi2.0-0 \
       libwayland-client0 \
       libx11-6 \
       libxext6 \
  && rm -rf /var/lib/apt/lists/*

# Shared Playwright browser cache (writable by vscode user)
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
RUN mkdir -p /ms-playwright \
  && chown -R vscode:vscode /ms-playwright

RUN { \
      echo 'memory_limit = 512M'; \
      echo 'upload_max_filesize = 64M'; \
      echo 'post_max_size = 64M'; \
      echo 'max_execution_time = 120'; \
      echo 'date.timezone = UTC'; \
    } > /usr/local/etc/php/conf.d/cakephp.ini
