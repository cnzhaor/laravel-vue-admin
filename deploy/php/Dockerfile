FROM php:8.3-fpm-alpine

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add --no-cache $PHPIZE_DEPS oniguruma-dev unzip \
    && docker-php-ext-install mbstring pdo_mysql pcntl \
    && apk del $PHPIZE_DEPS oniguruma-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/backend
