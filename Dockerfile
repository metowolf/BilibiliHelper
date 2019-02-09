FROM metowolf/php:7.3.0-alpine

ENV USERNAME=
ENV PASSWORD=
ENV ROOMID 3362970

COPY . /app
WORKDIR /app

RUN cp config.example config && \
    composer config -g repo.packagist composer https://packagist.phpcomposer.com && \
    composer install

ENTRYPOINT ["sh", "docker/entrypoint.sh"]
