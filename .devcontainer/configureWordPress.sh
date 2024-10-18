source scoper.sh

docker compose kill
docker compose rm --force --stop --volumes
docker compose up --build --quiet-pull --wait -d --force-recreate --renew-anon-volumes --remove-orphans

until docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp core install --url=http://127.0.0.1:8080 --title=testsite --admin_user=admin --admin_email=vaas@gdata.de --admin_password=admin"
do
    echo "waiting for WordPress to be installed"
    sleep 2
done

docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin uninstall hello"
docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin uninstall akismet"
docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin install wp-crontrol"
docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin activate wp-crontrol"
docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin install plugin-check"
docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin activate plugin-check"
docker exec -it --user www-data gdata-antivirus-app-1 bash -c "wp plugin activate gdata-antivirus"

svn co https://plugins.svn.wordpress.org/gdata-antivirus/ svn/gdata-antivirus
