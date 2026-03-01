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
RUN sed -i 's,DocumentRoot /var/www/html,DocumentRoot /opt/static/public,g' '/etc/apache2/sites-available/000-default.conf'
RUN printf '%s\n' \
    '<Directory />' \
    '    Options None' \
    '    AllowOverride None' \
    '    Require all denied' \
    '</Directory>' \
    '' \
    '<Directory /opt/static/public>' \
    '    Options -Indexes +SymLinksIfOwnerMatch' \
    '    AllowOverride None' \
    '    Require all granted' \
    '    DirectoryIndex index.php' \
    '    FallbackResource /index.php' \
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
