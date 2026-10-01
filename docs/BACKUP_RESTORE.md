# Backup and restore

Before every code/content update: export Hub JSON, back up the full MariaDB database and `wp-content`, and keep a private copy of `wp-config.php` and server routing configuration. Hub JSON alone is not a full-site backup: it excludes users, settings beyond curated data, drafts/revisions, credentials, logs and TOTP material.

Preferred low-maintenance option: encrypted Swiss-host backups with a short retention you can control. Minimum: daily full backup, 7 rolling copies, and a restore test on an access-restricted staging domain. Keep TOTP recovery codes separately. Store backups outside the document root, with restricted access and encryption. Do not commit them to GitHub.

For a manual backup on a host supporting WP-CLI and gpg, `scripts/backup.sh /private/backups` exports the DB and encrypts the archive with AES-256 through gpg. It prompts for an encryption passphrase; do not put passwords in shell history. This manual script is not an unattended cron solution. If using scheduled provider backups, verify that encryption/retention actually apply. Missing `.htaccess` on Nginx: adapt the file list to your server.

Restore procedure:

1. Restore into a **closed staging** location first. Disable indexing/outgoing AI and ensure the staging is access-restricted; no production emails are sent by Hub.
2. Decrypt the archive to a private temporary directory: `gpg --output site.tar.gz --decrypt backup.tar.gz.gpg`. Extract it there. Never place the encrypted/decrypted backup itself under a public URL.
3. Restore `wp-content` and server configuration. Set new DB credentials in `wp-config.php` if the target DB changed. Import `database.sql` using the host UI or `wp db import /private/database.sql`.
4. For a new URL run `wp search-replace 'https://OLD' 'https://NEW' --skip-columns=guid --all-tables-with-prefix`. Use a backup first; this mutates the restored DB.
5. Run `wp rewrite flush --hard`, clear caches, and run `wp cron event run oh_daily` so restored stale logs are purged. Run WordPress updates only after checking backup compatibility.
6. Check four languages, source counts, search, waste validity, ICS, local drafts, private admin, TOTP login, noindex, HTTPS and private file restrictions. Confirm the operator/host details are correct for the target.
7. Switch production only after the staging check and take a fresh backup. Protect recovery codes and remove private decrypted temporary copies.

Request/IP retention applies to the live application. Backups can temporarily contain older logs: keep encrypted snapshots for at most the defined 7-day window, limit restoration access, and purge immediately after restoration. If the operator needs longer backups, export a copy excluding personal technical logs or establish/disclose a justified exception. The product must not silently retain IP data indefinitely through backups. Host logs require their own purge settings.
