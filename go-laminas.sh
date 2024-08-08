#!/usr/bin/env bash

cd /var/www

# if is DEV env, display errors and starting gulp to compile postcss and join javascript
if [ "$PHP_DEV_ENV" == "1" ]
then
    echo "In dev environment, app will display errors"
    cp /var/www/config/autoload/development.local.php.dist /var/www/config/autoload/development.local.php
    chown www-data:www-data /var/www/config/autoload/development.local.php

    sed -i "s@display_errors = Off@display_errors = On@g" /usr/local/etc/php/conf.d/php.ini

    echo "In dev environment, starting gulp"
    gulp &

    echo 'alias phpunit="/var/www/vendor/bin/phpunit"' >> ~/.bashrc
fi

if [[ ${#LAMINAS_CRONJOB} -gt 34 ]]; then
    echo "* * * * * curl http://127.0.0.1/cronjob-${LAMINAS_CRONJOB}" > /etc/cron.d/cron
    crontab /etc/cron.d/cron
fi


/go-php.sh
