#!/bin/bash

if grep --fixed-strings --quiet -- '- ./scoped-code/:/var/www/html/wp-content/plugins/gdata-antivirus:ro,cached' compose.yml;
then
    sed --in-place=.bak 's#/scoped-code##g' compose.yml
    echo "you are now in live development mode"
else
    mv compose.yml.bak compose.yml
    echo "you are now in scoped development mode, make sure to run 'source .devcontainer/configureWordPress.sh' after all your changes"
fi

if grep --fixed-strings --quiet '"/var/www/html/wp-content/plugins/gdata-antivirus": "${workspaceFolder}/scoped-code",' .vscode/launch.json;
then
    sed --in-place=.bak 's#/scoped-code##g' .vscode/launch.json
    echo "you can now debug the root code"
else
    mv .vscode/launch.json.bak .vscode/launch.json
    echo "you can now debug the scoped code"
fi