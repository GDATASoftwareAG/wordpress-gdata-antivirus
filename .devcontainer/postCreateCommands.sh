#!/bin/bash
set -ex

sudo apt-get update 
sudo apt-get install -y bash-completion vim
sudo bash -c "docker completion bash > /usr/share/bash-completion/completions/docker"
sudo bash -c "composer completion bash > /usr/share/bash-completion/completions/composer"

echo ". /usr/share/bash-completion/bash_completion" >> /home/vscode/.bashrcsource

pwd
docker compose rm --force --stop --volumes
docker compose up --quiet-pull --wait -d --force-recreate --renew-anon-volumes --remove-orphans

docker exec -it wordpress-gdata-antivirus-app-1 apt update
docker exec -it wordpress-gdata-antivirus-app-1 apt install -y less
docker exec -it wordpress-gdata-antivirus-app-1 bash -c "curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
docker exec -it wordpress-gdata-antivirus-app-1 bash -c "curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
docker exec -it wordpress-gdata-antivirus-app-1 bash -c "chmod +x wp-cli.phar"
docker exec -it wordpress-gdata-antivirus-app-1 bash -c "mv wp-cli.phar /usr/local/bin/wp"
until docker exec -it --user www-data wordpress-gdata-antivirus-app-1 bash -c "wp core install --url=http://127.0.0.1:8080 --title=testsite --admin_user=admin --admin_email=vaas@gdata.de --admin_password=admin"
do
    echo "waiting for nextcloud to be installed"
    sleep 2
done
composer install

docker exec -it --user www-data wordpress-gdata-antivirus-app-1 bash -c "wp plugin uninstall hello"
docker exec -it --user www-data wordpress-gdata-antivirus-app-1 bash -c "wp plugin uninstall akismet"
docker exec -it --user www-data wordpress-gdata-antivirus-app-1 bash -c "wp plugin install wp-control"
docker exec -it --user www-data wordpress-gdata-antivirus-app-1 bash -c "wp plugin activate wp-crontrol"
docker exec -it --user www-data wordpress-gdata-antivirus-app-1 bash -c "wp plugin activate wordpress-gdata-antivirus"
