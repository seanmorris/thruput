FROM php:7.2-apache as development
MAINTAINER Sean Morris <sean@seanmorr.is>

COPY ./data/global/apache/thruput.conf /etc/apache2/sites-available/thruput.conf

RUN apt-get update \
	&& docker-php-ext-install pdo pdo_mysql bcmath sockets \
	&& a2enmod rewrite \
	&& a2ensite thruput

RUN apt-get install -y --no-install-recommends git zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY ./ /app

RUN chmod -R 775 /app \
	&& chmod -R 777 /app/temporary \
	&& cd /app \
	&& composer install --prefer-source --no-interaction

RUN ln -s /app/vendor/seanmorris/ids/source/Idilic/idilic /usr/local/bin/idilic \
	&& rm -rf /var/www/html \
	&& ln -s /app/public /var/www/html

RUN apt-get update \
	&& apt install libtidy-dev  -y --no-install-recommends \
	&& docker-php-ext-install tidy \
	&& docker-php-ext-enable tidy \
	&& pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /app/public

FROM development as production
