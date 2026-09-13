# ParbaTo Recovery Guide

Honest recovery procedures for operators. This is **not** point-in-time recovery unless MySQL binary logging is separately configured.

## Prerequisites

- Access to encrypted off-host copies of `storage/app/backups/*.sql.gz`
- Working MySQL/`mysqldump` tooling
- Application maintenance window

## Backup verification

1. List backups: `ls -lh storage/app/backups`
2. Confirm newest file size is non-trivial
3. Test decompress: `gzip -t storage/app/backups/parbato_YYYY-MM-DD_HH-MM-SS.sql.gz`

## Restore procedure

1. Put the app in maintenance mode:

```bash
php artisan down
```

2. Stop queue workers (Supervisor):

```bash
sudo supervisorctl stop parbato-worker:*
```

3. Restore database (destroys current DB contents):

```bash
gunzip < storage/app/backups/parbato_YYYY-MM-DD_HH-MM-SS.sql.gz | mysql -u USER -p DATABASE
```

4. Verify migrations match restored schema:

```bash
php artisan migrate:status
```

5. Clear/rebuild caches:

```bash
php artisan optimize:clear
php artisan optimize
```

6. Restart workers and exit maintenance:

```bash
sudo supervisorctl start parbato-worker:*
php artisan up
```

## Environment recovery

Restore `.env` from secure secret storage (never from public backups). Regenerate `APP_KEY` only if it was lost — regenerating invalidates encrypted sessions/cookies.

## Storage recovery

Restore `storage/app` private files from your off-host backup strategy if used. `storage/app/backups` must remain private (not web-accessible).

## Post-restore checks

- Login as teacher + student demo accounts (non-production only)
- Confirm ClassTwin attendance, LearnQuest, assessments, notifications
- Run `php artisan xp:recalculate` if XP totals look stale
- Confirm queue processes jobs: `php artisan queue:work --once`

## Remote backup strategy

Copy compressed dumps off-host daily (S3/object storage or secure backup appliance). Retain according to institutional policy (default local retention is 14 backups via `parbato:backup --keep=14`).
