FROM php:7-fpm

RUN apt-get update
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN apt-get -y install \
    imagemagick \
    graphicsmagick \
    gcc nasm build-essential make wget \
    git vim


# install MozJPEG
RUN \
    wget "https://github.com/mozilla/mozjpeg/releases/download/v3.1/mozjpeg-3.1-release-source.tar.gz" && \
    tar xvf "mozjpeg-3.1-release-source.tar.gz" && \
    cd mozjpeg && \
    ./configure && \
    make && \
    make install

CMD ["php-fpm"]