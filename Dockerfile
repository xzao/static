#
#   Dockerfile
#
FROM php:8.2-apache


#
#   enable rewrite module
#
RUN a2enmod rewrite


#
#   docroot
#
RUN sed -i 's,DocumentRoot /var/www/html,DocumentRoot /opt/static/var,g' '/etc/apache2/sites-available/000-default.conf'
RUN printf '%s\n' \
    '<Directory />' \
    '    Options None' \
    '    AllowOverride None' \
    '    Require all denied' \
    '</Directory>' \
    '' \
    '<Directory /opt/static/var>' \
    '    Options -Indexes +FollowSymLinks' \
    '    AllowOverride None' \
    '    Require all granted' \
    '</Directory>' \
    '' \
    '<Directory /opt/static/src>' \
    '    Options -Indexes' \
    '    Require all granted' \
    '</Directory>' \
    >> /etc/apache2/apache2.conf

RUN sed -i '/<\/VirtualHost>/i \    RewriteEngine On\n    RewriteCond %{REQUEST_FILENAME} !-f\n    RewriteCond %{REQUEST_FILENAME} !-d\n    RewriteRule ^ /opt/static/src/index.php [L]' /etc/apache2/sites-available/000-default.conf


#
#   workdir
#
WORKDIR /opt/static


#
#   expose
#
EXPOSE 80
