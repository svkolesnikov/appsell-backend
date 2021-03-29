FROM nginx:1-alpine

WORKDIR /app

COPY ./.docker/balancer/default.conf.tmp /app/default.conf.tmp
COPY ./.docker/balancer/errors /app/errors/.
COPY ./.docker/balancer/start-balancer.sh /start-balancer.sh