#!/bin/sh

echo "########################################################################";
echo "#              THIS LOCAL COMMAND START DOCKER CONTAINER               #";
echo "########################################################################";

if [ ! -d "vendor" ]; then
  composer install --optimize-autoloader --no-scripts
fi

chown www-data:www-data ./var -R

php-fpm