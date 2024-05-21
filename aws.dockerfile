FROM php:8.2-apache

ARG environment

RUN echo $environment

#Update the image
RUN chmod 777 /tmp
RUN apt-get update
RUN apt-get -y upgrade

#Install php module
RUN apt-get install -y openssl
RUN apt-get install -y zip
RUN apt-get install -y unzip
RUN apt-get install -y git
RUN apt-get install -y curl
RUN apt-get install -y build-essential
RUN apt-get install -y libpng-dev
RUN apt-get install -y libonig-dev
RUN apt-get install -y libxml2-dev
RUN apt-get install -y ca-certificates
RUN apt-get install -y gnupg
RUN apt-get install -y libfreetype-dev
RUN apt-get install -y libfreetype6-dev
RUN apt-get install -y libjpeg62-turbo-dev
RUN docker-php-ext-install mbstring pdo pdo_mysql
RUN docker-php-ext-configure gd --with-jpeg --with-freetype
RUN docker-php-ext-install gd
RUN docker-php-ext-install bcmath
RUN apt-get clean && rm -rfv /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#Installing sagex app
RUN mkdir -p /var/www/app

WORKDIR /var/www/app/
COPY . /var/www/app/

RUN mkdir -p /var/www/app/storage
RUN mkdir -p /var/www/app/storage/framework
RUN mkdir -p /var/www/app/storage/framework/cache
RUN mkdir -p /var/www/app/storage/framework/sessions
RUN mkdir -p /var/www/app/storage/framework/views

RUN cp .env.$environment.aws .env

RUN chmod -R o+w /var/www/app/storage
RUN chown -R www-data:www-data ./storage
RUN chgrp -R www-data ./bootstrap/cache
RUN chmod -R ug+rwx ./bootstrap/cache
RUN chmod -R 755 /var/www/app/
RUN find /var/www/app/ -type d -exec chmod 775 {} \;

RUN composer update
RUN php artisan migrate --force
RUN php artisan db:seed --force

RUN chown -R www-data:www-data /var/www

#Facing the laravel cache issue while deploy in AWS , Following command will clear all cache
RUN php artisan view:cache
RUN php artisan view:clear
RUN php artisan config:cache
RUN php artisan config:clear
RUN php artisan route:cache
RUN php artisan route:clear
RUN php artisan event:cache
RUN php artisan event:clear

ADD 000-default.conf /etc/apache2/sites-available/
RUN ln -sf /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf

COPY aws.$environment.uploads.ini /usr/local/etc/php/conf.d/uploads.ini

#RUN a2enmod ssl
RUN a2enmod headers
RUN a2enmod rewrite
RUN a2ensite 000-default.conf
RUN service apache2 restart

#CMD ["apache2", "-DFOREGROUND"]

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
EXPOSE 80

#EXPOSE 80
#CMD php artisan serve --host=0.0.0.0 --port=80