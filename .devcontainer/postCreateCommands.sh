#!/bin/bash
set -ex

sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"
echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrcsource

composer global require humbug/php-scoper
/home/vscode/.composer/vendor/bin/php-scoper completion bash >> /home/vscode/.bash_completion
echo 'export PATH=/home/vscode/.composer/vendor/bin/:$PATH' >>~/.bashrc

sudo cp memory.ini /usr/local/etc/php/conf.d/memory.ini

source .devcontainer/configureWordPress.sh