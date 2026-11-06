#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

# Usage: bash backup-database.sh /etc/handh/backup.cnf handh_hospital \
#   /var/backups/handh GPG_RECIPIENT_FINGERPRINT backup-remote:handh/database
# rclone and the public GPG recipient key must be configured for this OS user.
if [[ $# -ne 5 ]]; then
    echo 'Expected: MYSQL_OPTION_FILE DATABASE BACKUP_DIRECTORY GPG_RECIPIENT RCLONE_DESTINATION' >&2
    exit 2
fi
option_file=$1
database=$2
backup_directory=$3
recipient=$4
destination=$5
[[ $option_file = /* && -r $option_file && $backup_directory = /* && $database =~ ^[a-zA-Z0-9_]+$ ]] || exit 2
for executable in mysqldump gzip gpg rclone flock sha256sum; do
    command -v "$executable" >/dev/null
done
mkdir -p "$backup_directory"
exec 9>"$backup_directory/.backup.lock"
flock -n 9 || exit 1
stamp=$(date -u +%Y%m%dT%H%M%SZ)
name="${database}-${stamp}.sql.gz.gpg"
target="$backup_directory/$name"

# A failed pipeline never produces a completed .gpg backup or success marker.
# No password is passed on the command line. Do not run DDL/migrations during it.
mysqldump --defaults-extra-file="$option_file" --single-transaction --quick \
    --no-tablespaces --set-gtid-purged=OFF --hex-blob --skip-triggers \
    --default-character-set=utf8mb4 "$database" \
    | gzip -c | gpg --batch --yes --encrypt --recipient "$recipient" --output "$target.partial"
mv "$target.partial" "$target"
(cd "$backup_directory" && sha256sum "$name" > "$name.sha256")
rclone copyto "$target" "${destination%/}/$name"
rclone copyto "$target.sha256" "${destination%/}/$name.sha256"
date -u +%FT%TZ > "$backup_directory/last-success"
echo 'Encrypted database backup copied offsite.'
