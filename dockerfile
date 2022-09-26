FROM jack.hc-sc.gc.ca/php/php-base:latest
# when local
#FROM php-base:latest

LABEL title = 'Laminas framework with npm/gulp. Apps going in /apps/'
LABEL author = 'Web/Mobile Team (imsd.web-dsgi@hc-sc.gc.ca)'
LABEL source = 'https://github.hc-sc.gc.ca/hs/php-base-laminas'

# update the OS and install common modules
RUN apt-get update -y && \
    rm -rf /var/lib/apt/lists/*

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash - && apt install -y nodejs

WORKDIR /var/www
COPY code/ /var/www/

RUN mkdir -p /var/www/module/Void
RUN git clone https://github.com/duprasf/Void.git /var/www/module/Void/.

RUN npm install --global gulp-cli
RUN npm install
RUN composer update
RUN chown www-data:www-data -R /var/www/*

RUN rm -Rf html && ln -s public html

COPY go-laminas.sh /go-laminas.sh
RUN chmod 774 /go-laminas.sh
CMD ["/go-laminas.sh"]
