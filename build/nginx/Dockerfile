FROM nginx:1.15-alpine

COPY ./build/nginx/default.conf /etc/nginx/conf.d/default.conf

CMD ["nginx", "-g", "daemon off;"]