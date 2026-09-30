#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p public/uploads/images var

	echo "Attente de la base de données…"
	tries=60
	until php bin/console dbal:run-sql -q "SELECT 1" >/dev/null 2>&1; do
		tries=$((tries - 1))
		if [ "$tries" -le 0 ]; then
			echo "Base de données injoignable." >&2
			php bin/console dbal:run-sql -q "SELECT 1"
			exit 1
		fi
		sleep 1
	done

	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	php bin/console app:seed --no-interaction
	php bin/console app:admin --no-interaction
	php bin/console cache:warmup
fi

exec docker-php-entrypoint "$@"
