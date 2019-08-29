FROM php:7.2-apache AS development
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN rm -rfv /var/www/html && ln -s /app/public /var/www/html \
	&& docker-php-ext-install pdo pdo_mysql bcmath sockets \
	&& a2enmod rewrite \
	&& apt-get update \
	&& docker-php-ext-install pdo pdo_mysql bcmath sockets \
	&& a2enmod rewrite \
	&& apt-get install ca-certificates apt-utils gnupg1 wget -y \
	&& curl -sL https://deb.nodesource.com/setup_10.x | bash \
	&& apt-get install nodejs -y \
	&& wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - \
	&& echo 'deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main' | tee /etc/apt/sources.list.d/google-chrome.list \
	&& apt-get update \
	&& apt-get install google-chrome-stable ssh -y

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY . /app

RUN apt-get update \
	&& apt-get install -y --no-install-recommends git zip ssh \
	&& chmod -R 775 /app \
	&& chmod -R 777 /app/temporary \
	&& cd /app \
	&& composer install --prefer-source --no-interaction \
	&& npm i -g prenderer

RUN ln -s /app/vendor/seanmorris/ids/source/Idilic/idilic /usr/local/bin/idilic

RUN apt-get update \
	&& apt install libtidy-dev  -y --no-install-recommends \
	&& docker-php-ext-install tidy \
	&& docker-php-ext-enable tidy \
	&& pecl install redis \
    && docker-php-ext-enable redis

CMD ["idilic", "-vv", "SeanMorris/ThruPut", "warmDaemon"]

FROM development AS production
