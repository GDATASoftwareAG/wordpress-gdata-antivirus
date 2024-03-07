#!/usr/bin/env bash

set -eux

# true is shell command and always return 0
# false always return 1
SITE_HOST="http://localhost:8080"

PLUGIN_DIR=/workspaces/wordpress-gdata-antivirus

# Install Composer dependencies.
cd "${PLUGIN_DIR}"
COMPOSER_ALLOW_XDEBUG=0 COMPOSER_MEMORY_LIMIT=-1 composer install

# Setup the WordPress environment.
chown -R www-data:www-data /var/www/html/wp-content/uploads
cd "/var/www/html"
echo "Setting up WordPress at $SITE_HOST"
wp core download || echo "already downloaded"
wp config create --dbname=$WORDPRESS_DB_NAME --dbuser=$WORDPRESS_DB_USER --dbpass=$WORDPRESS_DB_PASSWORD --dbhost=$WORDPRESS_DB_HOST || echo "already configured"
wp core install --url="$SITE_HOST" --title="OpenID Connect Development" --admin_user="admin" --admin_email="admin@example.com" --admin_password="password" --skip-email || echo "already installed"

echo "Done!"
