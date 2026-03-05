#
#   Dockerfile
#
FROM php:8.2-apache


#
#   enable rewrite module
#
RUN a2enmod rewrite


#
#   apache configuration
#
COPY etc/apache2/conf-available/directories.conf /etc/apache2/conf-available/directories.conf
COPY etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enconf directories


#
#   workdir
#
WORKDIR /opt/static


#
#   expose
#
EXPOSE 80
