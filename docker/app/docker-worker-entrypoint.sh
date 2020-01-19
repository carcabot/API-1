#!/bin/sh
set -e

confd --onetime --confdir /usr/local/etc/confd --backend env

mkdir -p public/uploads public/internal
mkdir -p var/cache var/log var/tmp
setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var public/uploads public/internal
setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var public/uploads public/internal

composer install --prefer-dist --no-progress --no-suggest --no-interaction
echo "Waiting for db to be ready..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
	sleep 1
done

if [ -z "$WORKER_MEMORY_LIMIT" ]
then
	exec php $COMMAND "$@"
else
	exec php -d memory_limit=$WORKER_MEMORY_LIMIT $COMMAND "$@"
fi
