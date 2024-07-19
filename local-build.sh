#!/bin/bash
rm -rf wordpress/ || echo "no wordpress folder found"
rm -rf vendor/ || echo "no vendor folder found"
rm composer.lock || echo "no composer.lock found"
composer install --no-dev

rm gdata-antivirus.zip || echo "no gdata-antivirus.zip found"
zip -r gdata-antivirus.zip * --exclude @.zipignore
ls -lha gdata-antivirus.zip