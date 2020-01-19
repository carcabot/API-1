#!/bin/sh
set -e

confd --onetime --confdir /usr/local/etc/confd --backend env

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p public/uploads public/internal
	mkdir -p var/cache var/log var/tmp
	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var public/uploads public/internal
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var public/uploads public/internal

	composer install --prefer-dist --no-progress --no-suggest --no-interaction
	echo "Waiting for db to be ready..."
	until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
		sleep 1
	done
	if [ "$APP_ENV" != 'prod' ]; then
		php bin/console doctrine:migrations:migrate --no-interaction
	fi
fi

exec docker-php-entrypoint "$@"
