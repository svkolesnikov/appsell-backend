envsubst '${DOCKER_BASE_DOMAIN}' < /app/default.conf.tmp > /etc/nginx/conf.d/default.conf

if [ -f "/app/default.conf.tmp.dev" ]; then
  envsubst '${DOCKER_BASE_DOMAIN}' < /app/default.conf.tmp.dev > /etc/nginx/conf.d/default.dev.conf
fi

nginx -g "daemon off;"