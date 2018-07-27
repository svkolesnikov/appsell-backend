#!/bin/sh

chown -R www-data:www-data /var/www/app/var
chmod -R +w /var/www/app/var

exec "$@"