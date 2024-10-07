#!/bin/bash

VERSION=${1:-"0.0.1"}
PATH_PREFIX=scoped-code

echo "Setting version to $VERSION"

echo "Setting version in gdata-antivirus.php"
echo "Before: $(grep "Version: " gdata-antivirus.php)"
sed -i "s/Version: .*/Version: $VERSION/" $PATH_PREFIX/gdata-antivirus.php
echo "After: $(grep "Version: " gdata-antivirus.php)"

echo "Setting version in Readme.txt"
echo "Before: $(grep "Stable tag: " Readme.txt)"
sed -i "s/Stable tag: .*/Stable tag: $VERSION/" $PATH_PREFIX/Readme.txt
echo "After: $(grep "Stable tag: " Readme.txt)"

echo "Setting version in composer.json"
echo "Before: $(grep "\"version\": " composer.json)"
sed -i "s/\"version\": .*/\"version\": \"$VERSION\",/" $PATH_PREFIX/composer.json
echo "After: $(grep "\"version\": " composer.json)"