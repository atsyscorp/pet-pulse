#!/bin/sh
# Nightly PostgreSQL dump for PetPulse. Run from the repository directory on the server:
#
#   ./deploy/backup.sh                 # writes deploy/backups/petpulse-YYYYMMDD-HHMM.sql.gz
#   crontab: 15 2 * * * cd /opt/petpulse && ./deploy/backup.sh >> deploy/backups/backup.log 2>&1
#
# Keeps the last $KEEP dumps. Copy the directory off-site (object storage, rsync)
# — a backup on the same disk as the database is not a backup.
set -eu

KEEP="${KEEP:-14}"
STAMP="$(date +%Y%m%d-%H%M)"
FILE="deploy/backups/petpulse-${STAMP}.sql.gz"

docker compose exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --no-owner' | gzip > "$FILE"
echo "wrote $FILE ($(du -h "$FILE" | cut -f1))"

ls -1t deploy/backups/petpulse-*.sql.gz | tail -n +"$((KEEP + 1))" | xargs -r rm -f
