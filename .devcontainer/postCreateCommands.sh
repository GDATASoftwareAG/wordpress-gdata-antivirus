#!/bin/bash
set -ex

sudo apt-get update 
sudo apt-get install -y bash-completion vim iputils-ping telnet

sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrcsource

source ./configureWordPress.sh