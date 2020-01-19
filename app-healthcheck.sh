#!/bin/sh
set -a
[ -f .healthcheck-env ] && . ./.healthcheck-env
set +a

if [ -z "$AUTH_TOKEN" ] && [ -z "$HEALTHCHECK_URL" ]
then
	echo "NO app health-check."
else
	echo "Waiting for app to be ready..."
	until [ $(curl -sIL -w "%{http_code}" -H "Authorization: Bearer $AUTH_TOKEN" "$HEALTHCHECK_URL" -o /dev/null --connect-timeout 3 --max-time 10) = "200" ]
	do
		sleep 1
	done
	echo "OK"
fi
