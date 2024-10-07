#!/bin/bash
set -ex

composer global require humbug/php-scoper
$(composer config home)/bin/php-scoper completion bash >> /home/vscode/.bash_completion
echo "export PATH=$(composer config home)/vendor/bin/:$PATH" >> ~/.bashrc

sudo cp memory.ini /usr/local/etc/php/conf.d/memory.ini

if [[ "$IS_CI" == "true" ]]; then
    exit 0
fi

sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrcsource


source .devcontainer/configureWordPress.sh