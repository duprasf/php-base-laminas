FROM jack.hc-sc.gc.ca/php/php-base:latest
# when local
#FROM php-8-base:latest

LABEL title = 'Laminas framework with npm/gulp. Apps going in /apps/'
LABEL author = 'Web/Mobile Team (imsd.web-dsgi@hc-sc.gc.ca)'
LABEL source = 'https://github.hc-sc.gc.ca/hs/php-base-laminas'

# update the OS and install common modules
RUN apt-get update -y && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY code/ /var/www/
RUN composer update

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash - && apt-get install -y nodejs
RUN npm install
RUN chown www-data:www-data -R /var/www/*

RUN rm -Rf html && ln -s public html
