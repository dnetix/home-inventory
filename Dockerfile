FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    redis \
    zip \
    intl \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
