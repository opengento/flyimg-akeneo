FROM nginx:1.9

ADD nginx.conf /etc/nginx/nginx.conf
ADD site.conf /etc/nginx/conf.d/default.conf


RUN \
    mkdir -p /var/www/html/var && \
    chmod -R 777 /var/www/html/var

