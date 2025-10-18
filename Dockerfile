FROM php:8.4-cli

RUN apt-get -qq update

RUN apt-get -qq install -y zip libzip-dev git --no-install-recommends
RUN docker-php-ext-install zip

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

ENV COMPOSER_HOME=/usr/local/composer
WORKDIR /project
RUN composer global require "laravel/installer"
ENV PATH=$PATH:/usr/local/composer/vendor/bin
RUN chmod a+rwx /usr/local/composer -R
