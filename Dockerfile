FROM php:7.2
WORKDIR /root/blive
ENV BLIVE_PATH /root/blive
USER root
COPY . .
RUN \
    curl -sS https://getcomposer.org/installer | php &&\
    mv composer.phar /usr/local/bin/composer &&\
    echo "deb http://mirrors.163.com/debian stretch main\ndeb http://mirrors.163.com/debian-security stretch/updates main\ndeb http://mirrors.163.com/debian stretch-updates main" > /etc/apt/sources.list &&\
    apt update &&\
    apt install -y git zip &&\
    composer config -g repo.packagist composer https://packagist.phpcomposer.com &&\
    composer install
CMD ["${BLIVE_PATH}/start.sh"]
