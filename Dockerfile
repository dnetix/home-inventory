FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    redis \
    zip \
    intl \
    opcache \
    gd \
    exif

# The code lives on a slow Windows bind mount (9p): keep OPcache big enough for
# the whole app + vendor and cache realpath lookups so file stats stay rare.
RUN { \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=32'; \
        echo 'opcache.max_accelerated_files=32531'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/zz-perf.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
