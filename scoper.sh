#!/bin/bash
set -e

rm -rf scoped-code/
composer install --no-dev
php-scoper add-prefix --force
composer install

composer dump-autoload --working-dir scoped-code/ --classmap-authoritative
