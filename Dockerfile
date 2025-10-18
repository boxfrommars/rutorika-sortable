FROM php:8.4-cli

RUN apt-get -qq update

RUN apt-get -qq install -y zip libzip-dev --no-install-recommends
RUN docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

ENV COMPOSER_HOME=/usr/local/composer
WORKDIR /project
RUN composer global require "laravel/installer"
ENV PATH=$PATH:/usr/local/composer/vendor/bin
RUN chmod a+rwx /usr/local/composer -R
