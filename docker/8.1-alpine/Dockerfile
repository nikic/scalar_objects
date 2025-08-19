FROM php:8.1-alpine

# Install os-level dependencies
RUN apk update \
    && apk --no-cache --virtual add git pcre-dev ${PHPIZE_DEPS}

# Install scalar extension
RUN git clone https://github.com/nikic/scalar_objects.git \
    && cd scalar_objects \
    && phpize \
    && ./configure --enable-scalar_objects \
    && make \
    && make install \
    && php run-tests.php -p `which php` --show-diff -d extension=`pwd`/modules/scalar_objects.so -q \
    && docker-php-ext-enable scalar_objects

# Remove os-level dependencies and src code
RUN apk del git pcre-dev ${PHPIZE_DEPS} \
    && rm -rf scalar_objects \
    && apk del git pcre-dev ${PHPIZE_DEPS} \
    && rm -rf /tmp/* /var/tmp/*

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php", "-a"]
