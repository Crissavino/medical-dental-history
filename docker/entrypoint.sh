#!/bin/sh
set -e

# Copy public assets to the shared volume so nginx can serve them
cp -r /var/www/html/public-staging/* /var/www/html/public/
chown -R www-data:www-data /var/www/html/public

exec php-fpm
