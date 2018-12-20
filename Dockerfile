FROM metowolf/php:7.3.0-alpine

ENV USERNAME=
ENV PASSWORD=
ENV ROOMID 3746256

COPY . /app
WORKDIR /app

RUN cp config.example config && \
    composer install

ENTRYPOINT ["sh", "docker/entrypoint.sh"]
