rm -rf vendor/ composer.lock
composer install --no-dev
rm gdata-antivirus.zip 2>/dev/null && zip -r gdata-antivirus.zip * --exclude @.zipignore && ls -lha gdata-antivirus.zip