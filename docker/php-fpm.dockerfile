ARG PHP_VERSION=${PHP_VERSION}

FROM php:${PHP_VERSION}-fpm
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apt update
RUN apt-get install -y zlib1g-dev libzip-dev libpng-dev libwebp-dev libjpeg62-turbo-dev zip unzip bash

RUN ln -sf /bin/bash /bin/sh

RUN docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install gd
RUN docker-php-ext-install exif
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo_mysql

ARG PHP_XDEBUG
RUN if [ "$PHP_XDEBUG" = "1" ]; then \
      case "$PHP_VERSION" in \
        "7.4"*) \
          yes | pecl install xdebug-3.1.6 \
          ;; \
        "8.5"*) \
          apt-get update && apt-get install -y autoconf g++ make git \
            && cd /tmp \
            && git clone https://github.com/xdebug/xdebug.git \
            && cd xdebug \
            && phpize \
            && ./configure --enable-xdebug \
            && make \
            && make install \
            && rm -rf /tmp/xdebug \
          ;; \
        *) \
          yes | pecl install xdebug \
          ;; \
      esac \
      && echo "zend_extension=xdebug" > /usr/local/etc/php/conf.d/xdebug.ini \
      && echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
      && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini \
      && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
      && echo "xdebug.log_level=0" >> /usr/local/etc/php/conf.d/xdebug.ini; \
    fi
