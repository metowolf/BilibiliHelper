FROM node:alpine

LABEL maintainer="metowolf <i@i-meto.com>"

ENV USERNAME=
ENV PASSWORD=
ENV ACCESS_TOKEN=
ENV REFRESH_TOKEN=
ENV ROOM_ID 3746256
ENV DEBUG true
ENV TZ Asia/Shanghai

WORKDIR /app
COPY package.json yarn.lock .yarnclean ./
RUN apk add --no-cache tzdata \
    && yarn \
    && yarn cache clean
COPY . .

CMD ["node", "index.js"]
