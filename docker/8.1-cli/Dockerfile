FROM php:8.1-cli 

# Install os-level dependencies
RUN apt-get update && apt-get install -y git

# Install scalar extension
RUN git clone https://github.com/nikic/scalar_objects.git \
    && cd scalar_objects \
    && phpize \
    && ./configure --enable-scalar_objects \
    && make \
    && make install \
    && php run-tests.php -p `which php` --show-diff -d extension=`pwd`/modules/scalar_objects.so -q \
    && docker-php-ext-enable scalar_objects

# Delete src code and os-level dependencies
RUN rm -rf scalar_objects \
    && apt-get purge -y git \
    && apt-get clean -y \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php", "-a"]
