ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli
RUN apt-get update && apt-get install -y git unzip && rm -Rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN mkdir -p  /.composer/cache/ && chown -R 1000:1000 /.composer/cache/

ENV XDEBUG_MODE=off
COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions xdebug
RUN echo ' \n\
[xdebug] \n\
xdebug.enable=1 \n\
xdebug.idekey=PHPSTORM \n\
xdebug.client_host=host.docker.internal\n ' >> /usr/local/etc/php/conf.d/xdebug.ini

USER www-data
USER 1000:1000
