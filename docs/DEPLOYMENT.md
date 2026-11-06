# Render deployment with a hosted MySQL database

Updated 6 November 2026. Source is published at [obaid120/h-h-hospital](https://github.com/obaid120/h-h-hospital). The selected demo database is **Filess.io Shared MySQL 8.0**; the web application will run on **Render Free**. The database has been created by the user, but authenticated connection, schema migration, TLS identity verification and the hosted app have not yet been verified.

## 1. Hosting and limits

| Component | Location |
| --- | --- |
| Source and installation README | Public GitHub repository |
| Laravel, Nginx and PHP 8.4 FPM | Render Docker Web Service |
| Persistent database records | Separately hosted MySQL |
| Credentials and trusted CA certificate | Render Environment / Secret Files |

The application's source is in the inner `h-h-hospital/` directory. The Docker build context is the repository root. GitHub does not run the PHP application.

Filess.io currently lists Shared Free with 2 databases and **10 MB per database**. Check schema + indexes + data size after migration; fitting the full application is not yet proven. Its dashboard may append a suffix to a requested database name: copy the **actual complete name**, host, port and username from the connection screen. MySQL 8.0 must be selected instead of 5.7; business-critical verification previously used 8.4/InnoDB, so compatibility still needs verification.

Render Free sleeps after inactivity and loses local filesystem changes after restarts/redeploys/sleep. Current doctor photos are local uploads and are not durable on that plan. Staff sessions/cache and clinical/financial records use MySQL. A sleeping container also stops its scheduler. This is a small demo setup, not completed live-hospital operational acceptance.

Aiven was considered earlier. Its currently fetched MySQL pricing starts with the paid Developer plan; older free-tier documentation conflicts with the current console. A new-account trial is temporary and is not an ongoing free MySQL plan. The configuration is provider-neutral; no account-specific host, username, password or certificate is committed.

## 2. Resolve database TLS before deploying

The included startup script requires a readable `MYSQL_ATTR_SSL_CA` file. It does not assume an Aiven-specific filename and does not silently disable certificate verification.

A password-free TLS handshake against the user's selected Filess endpoint succeeded far enough to reveal a **self-signed certificate chain**, which is not trusted by the normal system CA bundle. This proves TLS is offered, not that the server identity has been authenticated. No database login, queries or writes were performed.

Before entering database credentials into a running deployment:

1. Obtain the database provider's CA certificate through its authenticated dashboard or another independently trusted provider channel. Look for SSL/TLS, CA certificate or connection security settings. If absent, ask the provider how to verify that particular MySQL endpoint.
2. Confirm the provider's certificate setup supports verification for the actual database hostname. A CA file alone does not repair a hostname mismatch.
3. In Render, add a Secret File named **`mysql-ca.pem`** containing the provider CA. Set `MYSQL_ATTR_SSL_CA=/etc/secrets/mysql-ca.pem`.
4. If a valid, hostname-verifiable provider certificate cannot be obtained, keep deployment pending; do not remove TLS checks or copy an unrelated Aiven CA to get past startup.

Do not treat a certificate merely downloaded from an unverified connection as independently trusted. Do not paste passwords, full connection URIs or private credentials into GitHub/chat. Public CA files are trust configuration, not database passwords, but keep the selected trust material in Render Secret Files rather than the application image.

## 3. Render Web Service settings

Render → **New → Web Service** → connect the existing GitHub repository. Configure:

| Field | Value |
| --- | --- |
| Repository | `obaid120/h-h-hospital` |
| Branch | `main` |
| Language/runtime | **Docker** |
| Root Directory | **blank** |
| Dockerfile Path | `h-h-hospital/Dockerfile` |
| Docker Build Context | `.` |
| Instance Type | **Free** |
| Health Check Path | `/up` |
| Port | `10000` |
| Auto Deploy | Initially off/manual |

`render.yaml` is an optional Blueprint alternative. No Render Postgres database is needed. Free services have no interactive shell/SSH or one-off jobs; the start script runs migrations idempotently before serving requests.

The image builds frontend assets with Node 24 and installs PHP 8.4 production dependencies from the lockfile. Secrets are supplied at runtime. Docker source COPY paths are explicit, so local credentials, database files, tests and uploads do not enter the image.

## 4. Render Environment variables

Use the dashboard's actual database values. `docs/hosting/.env.render.example` contains a blank-secret reference template:

```dotenv
APP_NAME="H&H Hospital"
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://YOUR-SERVICE.onrender.com
APP_KEY=YOUR_STABLE_LARAVEL_KEY
DB_CONNECTION=mysql
DB_HOST=YOUR_DATABASE_HOST
DB_PORT=YOUR_DATABASE_PORT
DB_DATABASE=YOUR_COMPLETE_DATABASE_NAME
DB_USERNAME=YOUR_DATABASE_USERNAME
DB_PASSWORD=YOUR_DATABASE_PASSWORD
MYSQL_ATTR_SSL_CA=/etc/secrets/mysql-ca.pem
TRUSTED_PROXIES=*
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
HOSPITAL_TIMEZONE=Asia/Karachi
HOSPITAL_CURRENCY=PKR
MAIL_MAILER=log
INITIAL_ADMIN_EMAIL=YOUR_ADMIN_EMAIL
INITIAL_ADMIN_PASSWORD=YOUR_UNIQUE_STRONG_PASSWORD
```

Render's individual environment inputs take plain values; don't add the example's wrapping quotes to the dashboard value. `TRUSTED_PROXIES=*` applies to Render's reverse-proxy-only ingress, not an arbitrary directly exposed server.

Generate an online-environment APP_KEY **once**, privately, from the inner application directory:

```powershell
C:\php84\php.exe artisan key:generate --show
```

`--show` does not replace the local `.env` key. Save the output privately as Render APP_KEY and in your password manager; preserve it across deployments/restores. Do not put secrets in `VITE_*` variables, which enter browser assets.

The initial Admin password must have at least 12 characters, upper/lowercase letters and numbers. There are no fixed demo credentials in the public repository.

## 5. First deployment

Only after the provider CA and environment are ready, create/deploy the Web Service. Startup validates required configuration, runs existing migrations with `--force`, and bootstraps one Admin if no active Admin exists. It never resets tables or seeds local demo records. It removes the one-time bootstrap password from its child-process environment, caches configuration/routes/views, records a startup heartbeat and starts Nginx/PHP-FPM/scheduler under supervisor.

If migration, TLS or connection fails, startup exits before serving. Resolve the actual error and redeploy; do not use `migrate:fresh`, overwrite the existing database or disable certificate verification. SQL permissions must allow schema migrations in the selected database, not unrelated databases.

Visit the assigned HTTPS host and `/staff/login`, then sign in using your configured Admin. After successful first login, remove `INITIAL_ADMIN_PASSWORD` from Render Environment and redeploy. The existing Admin stays intact. Create Reception/Doctor accounts and profiles through the Admin screens, then configure fees, OPD hours and hospital settings. `_design` routes are local-only.

## 6. Compatibility, space and workflow checks

Before calling the demo ready, verify on the actual MySQL 8.0 service:

- All migrations complete using InnoDB; no unsupported SQL or missing permissions.
- Provider quota still has room after tables/indexes; database size includes sessions, cache and audit growth. The 10 MB limit is small, and a successful empty migration does not establish long-term capacity.
- The MySQL session reports a nonempty `Ssl_cipher` and the configured certificate validation succeeds. Never display credentials in an operator health endpoint.
- Fabricated online booking → Reception identity verification/payment/token → normal/Priority walk-in → Doctor queue/draft/explicit completion → print/reprint → Admin reports/cancellation/refund works.
- Duplicate requests, uncertain network responses and same-account session recovery do not duplicate collection/refund/clinical writes.
- A4 prescription and 80 mm receipt output are actually inspected. Existing receipt/prescription reprints create no new payment or completion.

These checks are pending until a verified authenticated database connection and a deployed app exist. Native print/PDF and operational acceptance are not satisfied by local regression tests alone.

## 7. Scheduler, backups and recovery

The container supervisor runs `schedule:work` while awake. Heartbeats never reset OPD statuses, seats or tokens. Core operations are synchronous (`QUEUE_CONNECTION=sync`); no queue worker is required. `/up` checks application bootstrap + DB connectivity. A trusted operator/container can use `php artisan ops:check --production` to check dependencies, assets, deployment settings and heartbeat. Render Free has no interactive dashboard shell; do not expose the CLI check publicly to work around that.

Filess's free-plan table lists weekly backups. Verify actual access, retention and restore capability in the dashboard; no independent restore has been demonstrated. Keep separate encrypted database exports and original photo files plus the APP_KEY. Use the actual provider port and trusted CA with MySQL client verification. Do not dump while running migrations/DDL.

`hosting/backup-database.sh` is for an independent Linux backup runner: a consistent InnoDB dump is compressed, GPG-encrypted, hashed and copied to an rclone remote. Failure exits nonzero without updating the success marker. Configure real private MySQL/GPG/rclone values outside the checkout. `hosting/cron.example` is a self-managed runner example, not a cron facility supplied by Render Free. A daily 02:15 Pakistan backup runs at 21:15 UTC on the previous UTC date. No runner/remote/account has been created.

A restore drill must download the offsite copy, verify the hash, decrypt/import into a separate isolated database, restore the original key/files, and compare clinical/financial records and reprints. Record the result and measured recovery time privately. Do not overwrite the current demo DB while testing restore. Daily exports imply a potential loss window up to 24 hours; weekly provider backups alone may lose more recent data.

Configure dashboard/operator notifications for app/DB failure, quota/disk growth, stale backups and certificate issues. Keep logs private; redact patient data before sharing an error publicly. [STAFF_HANDOFF.md](STAFF_HANDOFF.md) covers staff workflows and recovery.

## 8. Updates and troubleshooting

Code change → local checks → Git commit/push → Render Manual Deploy. Preserve APP_KEY, credentials and database records. Before schema updates, take a verified backup and pause demo operations. Reverting the app image does not automatically revert the schema; use a reviewed forward fix or coordinated recovery if versions are incompatible.

| Error | Check |
| --- | --- |
| Docker COPY missing file | Root Directory blank; repository-root build context |
| Database timeout | Correct provider host/port; network access allowed |
| Self-signed CA / hostname error | Obtain the provider trust chain and correct certificate hostname; keep verification enabled |
| Migration denied / quota exceeded | Provider permissions, MySQL version and actual quota; no reset |
| 419 or wrong URL scheme | HTTPS APP_URL, stable APP_KEY, proxy setting, DB sessions |
| Slow first request | Render Free idle cold start |
| Missing doctor photos after restart | Ephemeral free disk; persistent external uploads still pending |

Official references: [Render PHP/Docker](https://render.com/docs/deploy-php-laravel-docker), [Render free limits](https://render.com/docs/free), [Render secret files](https://render.com/docs/docker-secrets), [Filess Shared plans](https://docs.filess.io/docs/pricing/plans/), [Filess supported engines](https://docs.filess.io/docs/engines/supported-engines/), [Aiven current MySQL pricing](https://aiven.io/pricing/mysql), [MySQL encrypted connections](https://dev.mysql.com/doc/refman/8.0/en/using-encrypted-connections.html), [Laravel deployment](https://laravel.com/docs/13.x/deployment).
