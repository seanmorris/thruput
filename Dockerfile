FROM php:7.2-apache
MAINTAINER Sean Morris <sean@seanmorr.is>

COPY ./thruput.conf /etc/apache2/sites-available/thruput.conf

RUN apt-get update \
	&& a2enmod rewrite \
	&& a2ensite thruput \
	&& apt-get install ca-certificates apt-utils gnupg1 wget -y \
	&& curl -sL https://deb.nodesource.com/setup_10.x | bash \
	&& apt-get install nodejs -y \
	&& wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - \
	&& echo 'deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main' | tee /etc/apt/sources.list.d/google-chrome.list \
	&& apt-get update \
	&& apt-get install google-chrome-stable composer -y \
	&& npm i -g prenderer

COPY . /app

RUN apt-get update \
	&& chmod -R 775 /app \
	&& chmod -R 777 /app/temporary \
	&& composer install

RUN ln -s /app/vendor/seanmorris/ids/source/Idilic/idilic /usr/local/bin/idilic

WORKDIR /app
