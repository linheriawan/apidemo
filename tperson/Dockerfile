FROM php:8.1-apache
RUN apt update -y && apt upgrade -y
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN a2enmod rewrite
COPY ./*.php /var/www/html/
COPY ./.htaccess /var/www/html/
RUN chown -R www-data:www-data /var/www/html/*.*
RUN chown -R www-data:www-data /var/www/html/.*
