#!/bin/sh

chown -R www-data:www-data /var/www/app/var
chmod -R +w /var/www/app/var

chown -R www-data:www-data /var/www/app/public/images
chmod -R +w /var/www/app/public/images

exec "$@"