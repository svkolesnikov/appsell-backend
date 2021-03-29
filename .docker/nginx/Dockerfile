FROM nginx:1-alpine

RUN apk add bash

COPY ./.docker/nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /app