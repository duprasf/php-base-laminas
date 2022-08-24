#!/usr/bin/env bash

cd /var/www

# if is DEV env, run gulp to compile postcss
# and join javascript
if [ "$PHP_DEV_ENV" == "1" ]
then
    cp config/autoload/development.local.php.dist config/autoload/development.local.php
    gulp &
fi

apache2-foreground
