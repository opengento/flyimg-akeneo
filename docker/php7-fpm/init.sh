#!/bin/sh

echo "Check composer autoload generated"

if [ ! -f /var/www/html/vendor/autoload.php ]; then
    cd /var/www/html && \
    composer install
else
    echo "vendor/autoload.php exist"
fi

php-fpm