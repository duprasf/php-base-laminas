FROM jack.hc-sc.gc.ca/php/php:8-base

LABEL title = 'Laminas framework with npm/gulp. Apps going in /apps/'
LABEL author = 'Web/Mobile Team (imsd.web-dsgi@hc-sc.gc.ca)'
LABEL source = 'https://github.hc-sc.gc.ca/hs/php-base-laminas'

# update the OS and install common modules
RUN apt-get update -y && \
    apt-get upgrade -y nodejs npm && \
    rm -rf /var/lib/apt/lists/*

RUN npm install
RUN npm install -g n && n 11
RUN npm install --global gulp

COPY code/ /var/www/

WORKDIR /var/www

RUN composer update

RUN rm -Rf html && ln -s public html
