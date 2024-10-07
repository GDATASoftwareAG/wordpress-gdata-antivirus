#!/bin/bash
set -e

rm -rf scoped-code/
php-scoper add-prefix
mv scoped-code/vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php scoped-code/vendor/netresearch/jsonmapper/src/JsonMapper/JsonMapper_Exception.php
mv scoped-code/vendor/netresearch/jsonmapper/src/JsonMapper.php scoped-code/vendor/netresearch/jsonmapper/src/JsonMapper/JsonMapper.php
composer dump-autoload --working-dir scoped-code/ --classmap-authoritative