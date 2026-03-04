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
    '    RewriteEngine On' \
    '    RewriteCond %{REQUEST_FILENAME} !-f' \
    '    RewriteCond %{REQUEST_FILENAME} !-d' \
    '    RewriteRule ^ /opt/static/src/index.php [L]' \
    '</Directory>' \
    '' \
    '<Directory /opt/static/src>' \
    '    Require all granted' \
    '</Directory>' \
    >> /etc/apache2/apache2.conf


#
#   workdir
#
WORKDIR /opt/static


#
#   expose
#
EXPOSE 80
