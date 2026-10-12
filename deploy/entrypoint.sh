#!/bin/sh
# PetPulse container entrypoint.
#
# Every service (php-fpm, reverb, queue, scheduler) starts through here. Only the
# php-fpm container (ROLE=web) runs migrations, so the others never race it.
set -eu

ROLE="${ROLE:-web}"

wait_for_postgres() {
    tries=0
    until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-petpulse}" -q; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "PostgreSQL at ${DB_HOST:-db}:${DB_PORT:-5432} not reachable after ${tries} attempts" >&2
            exit 1
        fi
        echo "Waiting for PostgreSQL (${tries}/30)..."
        sleep 2
    done
}

wait_for_postgres

# Config/route/view/event caches are rebuilt on every start so a changed .env
# is always honoured. They live in storage/bootstrap, which is per container.
php artisan optimize

if [ "$ROLE" = "web" ]; then
    php artisan migrate --force --no-interaction
    php artisan storage:link --force >/dev/null 2>&1 || true
fi

exec "$@"
