#!/usr/bin/env bash

cd /var/www

# if is DEV env, run gulp to compile postcss
# and join javascript
if [ "$PHP_DEV_ENV" == "1" ]
then
    cp /var/www/config/autoload/development.local.php.dist /var/www/config/autoload/development.local.php
    chown www-data:www-data /var/www/config/autoload/development.local.php
    gulp &
fi

/go-php.sh
