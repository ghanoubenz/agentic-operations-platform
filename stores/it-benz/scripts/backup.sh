#!/usr/bin/env bash
# Database + uploads backup for any host with SSH + WP-CLI (Hostinger, VPS…).
# Usage (from the WordPress root):  bash backup.sh /path/to/backups [days-to-keep]
# Cron example (daily 03:15):       15 3 * * * cd ~/public_html && bash ~/backup.sh ~/backups 14
# Copy the archives OFF the server too (e.g. rclone to Google Drive / S3); a backup kept only
# on the same server does not survive a server loss.
set -euo pipefail
umask 077  # archives contain customer data
DEST="${1:?backup directory required}"; KEEP="${2:-14}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$DEST"; chmod 700 "$DEST"
wp db export "$DEST/db-$STAMP.sql" --single-transaction --quiet
gzip "$DEST/db-$STAMP.sql"
tar -czf "$DEST/uploads-$STAMP.tar.gz" -C wp-content uploads
tar -czf "$DEST/code-$STAMP.tar.gz" -C wp-content themes/itbenz plugins/itbenz-commerce
find "$DEST" -type f -mtime +"$KEEP" -delete
echo "Backup written to $DEST ($STAMP)"
