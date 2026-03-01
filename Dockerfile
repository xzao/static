#
#   Dockerfile
#
FROM php:8.2-apache


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
    '    Options Indexes -FollowSymLinks' \
    '    AllowOverride None' \
    '    Require all granted' \
    '</Directory>' \
    >> /etc/apache2/apache2.conf


#
#   copy application
#
WORKDIR /opt/static
COPY public /opt/static/public


#
#   expose
#
EXPOSE 80
