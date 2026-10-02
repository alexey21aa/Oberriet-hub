#!/usr/bin/env bash
set -euo pipefail
# Run from installed WordPress root, with WP-CLI and gpg available.
# Usage: /safe/path/backup.sh /private/backup-directory
backup_destination=${1:?Provide a private destination outside the document root}
umask 077
mkdir -p "$backup_destination"
backup_stamp=$(date -u +%Y%m%dT%H%M%SZ)
backup_temp=$(mktemp -d)
trap 'rm -rf "$backup_temp"' EXIT
wp db export "$backup_temp/database.sql" --quiet
# Live logs are included; keep encrypted backups short-lived and purge on restore.
# For unattended backups use the host's encrypted service with the retention policy.
tar -czf "$backup_temp/site.tar.gz" wp-content wp-config.php .htaccess -C "$backup_temp" database.sql
gpg --symmetric --cipher-algo AES256 --output "$backup_destination/oberhub-$backup_stamp.tar.gz.gpg" "$backup_temp/site.tar.gz"
find "$backup_destination" -type f -name 'oberhub-*.tar.gz.gpg' -mtime +6 -delete
