# Free deployment: Render + Aiven + public GitHub

Prepared: 6 November 2026. Pehli planning ke mutabiq **free demo** ke liye Render + Aiven use honge. Abhi koi cloud service/database create ya deploy nahi hui. User khud neeche ke dashboard steps karega.

## 1. Kya kahan rahega?

| Service | Kaam | Selected plan |
| --- | --- | --- |
| GitHub | Public source code, Dockerfile, guides | Public repository |
| Render | Laravel frontend/backend, Nginx + PHP 8.4 FPM container | Free Web Service |
| Aiven | Persistent MySQL database: patients, bookings, payments, prescriptions | Free MySQL |

Flow: **browser → HTTPS Render app → TLS Aiven MySQL**. Website online chalne ke liye aap ka PC/XAMPP running hona zaroori nahi. GitHub Pages PHP/Laravel server nahi chalata.

Verified current limits: Render free app 15 minutes inactivity ke baad sleep hoti hai; next request ko startup mein takreeban one minute lag sakta hai. Free instance local uploads/files restart/redeploy/sleep par lose karti hai. Aiven free service 1 CPU, 1 GB RAM, 1 GB disk aur 76 connection limit deti hai; unused database power off ho sakta hai. Cloud dashboards mein **Free** plan select karein; paid/trial upgrade select na karein. No paid VPS is required for this demo.

**Demo constraint:** current doctor photos local disk par upload hoti hain; Render free par woh durable nahi. Restart ke baad photos re-upload karni hongi. Clinical/financial records Aiven mein rahenge. Persistent external photo storage abhi configure nahi; real hospital operation ki acceptance is free demo se complete nahi hoti. Render Free ko production hospital operation ke liye approved na samjhein. Separate scheduler/backup/restore/uptime acceptance pending rahegi.

## 2. Public GitHub repository banayein (Windows)

Git repository root **D:\laravel-project** rakhein, inner h-h-hospital nahi, taake docs aur render.yaml clone mein saath aayein. Is checkout mein Git abhi initialized nahi hai.

GitHub → New repository → name `h-h-hospital` → **Public**. Auto README/.gitignore/license select na karein. PowerShell:

```powershell
Set-Location D:\laravel-project
git init -b main
git config user.name "Your Name"
git config user.email "YOUR_GITHUB_NOREPLY_EMAIL"
git check-ignore h-h-hospital/.env h-h-hospital/.env.testing h-h-hospital/storage/app/private/initial-admin.txt h-h-hospital/storage/app/private/mysql84/credentials.json
git add .
git diff --cached --stat
git diff --cached --name-only
```

Chaar private paths ignored show hon. Staged filenames/content inspect karein: passwords, actual `.env`, patient records, dumps, uploads, runtime storage, vendor/node_modules publish nahi hone chahiye. Storage ki `.gitignore` files aur blank-secret `.example` templates theek hain. Ab:

```powershell
git commit -m "Prepare hospital OPD demo and operational handoff"
git remote add origin https://github.com/YOUR_USERNAME/h-h-hospital.git
git push -u origin main
```

Browser/Git Credential Manager se login karein; password/token remote URL mein na lagayein. GitHub secret scanning/push protection available ho to enable karein. `.gitignore` already committed secret history se nahi hatati: leaked credential revoke/rotate first, phir GitHub sensitive-data removal follow karein. License apne code ownership ke decision ke mutabiq add karein.

## 3. Aiven par FREE MySQL banayein

1. Aiven account/project create karein → MySQL service → **Free** plan. Paid trial ke bajaye actual Free service select karein. Region Render region ke qareeb choose karein.
2. MySQL **8.4** available ho to select/verify karein; local business-critical tests MySQL 8.4/InnoDB par hue hain. Agar service doosra major de to compatibility acceptance ko pending rakhein; local tests ka result us version ka proof nahi.
3. Service running hone par Overview/Connection information se **host, port, database name, username, password** privately note karein. Port 3306 assume na karein. Dashboard ka actual `defaultdb` ya apna separate empty demo DB name use karein.
4. Aiven project **CA certificate** download karein. Isay GitHub mein commit na karein; Render par Secret File banegi.
5. Service user ko sirf selected demo DB ke required data + migration permissions dein; database schema recreate/import manually nahi karna. App startup migrations create karengi. Root/OS login ki zaroorat nahi.
6. Aiven service network/IP filter available ho to Render service ke **all outbound IP ranges** allow karein; sirf aik DNS-resolved IP allow na karein. Local backup ke liye apna current public IP separately allow karein. TLS verification disable karke connection error solve na karein.

No local development dump/demo passwords import karein. Online environment ke individual staff accounts Admin se create honge. Aiven free mein one MySQL service limit hai; demo ko later real production ke saath share na karein. Separate DB/users sharing same service bhi full isolation/SLA nahi dete.

## 4. Render par FREE Docker Web Service banayein

Render account → **New → Web Service** → GitHub public repository connect karein. Settings:

| Field | Value |
| --- | --- |
| Branch | `main` |
| Language/runtime | **Docker** |
| Root Directory | **blank** (repository root) |
| Dockerfile Path | `h-h-hospital/Dockerfile` |
| Docker Build Context | `.` |
| Instance Type | **Free** |
| Health Check Path | `/up` |
| Port | `10000` (included Nginx config) |
| Auto Deploy | Initially off/manual |

Repo root ka `render.yaml` optional Blueprint alternative hai. Manual Web Service setup secret file/env fill karne ke liye straightforward hai. Render ka Postgres/database create nahi karna: MySQL **Aiven** par hai. Free Render shell/SSH, one-off job aur paid cron available assume na karein.

**Before first deploy:** Environment → Secret Files → filename **`aiven-ca.pem`** → Aiven CA certificate ka exact content paste karein. Container mein path `/etc/secrets/aiven-ca.pem` hoga. Dockerfile explicit source allowlist use karti hai; secret files ko image mein COPY nahi karti.

## 5. Render Environment variables

`docs/hosting/.env.render.example` mein complete sample list hai. Dashboard mein values add karein; actual `.env` upload/commit nahi karni.

```dotenv
APP_NAME="H&H Hospital"
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://YOUR-SERVICE.onrender.com
APP_KEY=YOUR_STABLE_LARAVEL_KEY
DB_CONNECTION=mysql
DB_HOST=YOUR_AIVEN_HOST
DB_PORT=YOUR_AIVEN_PORT
DB_DATABASE=YOUR_AIVEN_DATABASE
DB_USERNAME=YOUR_AIVEN_USER
DB_PASSWORD=YOUR_AIVEN_PASSWORD
MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem
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

Render env input mein value ke around example quotes manually na add karein; dashboard plain value store karta hai. `TRUSTED_PROXIES=*` is Render's reverse-proxy-only setup ke liye hai; direct public VPS par all proxies trust na karein. HTTPS forms/session correct scheme par rahengi.

**APP_KEY ek baar generate** karein, dashboard mein set karein aur password manager mein save karein. Existing local key ko print/share karne ke bajaye new online environment ka key banayein. Local PHP 8.4 terminal mein:

```powershell
C:\php84\php.exe artisan key:generate --show
```

Command **h-h-hospital directory** se run karein. `--show` local `.env` overwrite nahi karta. Output privately Render `APP_KEY` mein paste karein. Har deploy par key regenerate na karein. Initial Admin password 12+ characters, uppercase/lowercase/numbers ho; woh GitHub/VITE variables mein na daalein.

## 6. Deploy aur first Admin login

Create Web Service / **Manual Deploy → Deploy latest commit**. Build Node 24 se assets aur PHP 8.4 se locked production dependencies install karega. Start script:

1. Required key/URL/Aiven CA config check karti hai.
2. Existing migrations idempotently run karti hai (`migrate --force`); destructive reset/demo seeding nahi karti.
3. First active Admin absent ho to `staff:bootstrap-admin` chalati hai. Existing Admin ko overwrite/reset nahi karti.
4. One-time password process environment se remove karke config/routes/views cache banati hai.
5. Nginx, PHP-FPM aur scheduler supervisor start karti hai; stdout/stderr Render logs mein jate hain.

Free Render pre-deploy shell nahi deta, is liye migrations startup mein hain. Demo mein one instance/manual deployment rakhein. Future production migrations ke liye coordinated release/backup strategy chahiye. Migration/DB/CA failure par container request serve karne se pehle exit karega; logs mein failure resolve karke redeploy karein.

Render assigned HTTPS URL kholein → `/staff/login` → configured Admin se login. **Successful login ke baad dashboard se `INITIAL_ADMIN_PASSWORD` remove** karke redeploy karein. APP_KEY/DB secrets preserve karein. Admin se real demo doctors/Reception/Doctor accounts, fee/hours aur hospital settings add karein. `/_design` preview routes staging mein inaccessible honi chahiye.

Public domain ki zaroorat nahi: free `onrender.com` subdomain/managed HTTPS use ho sakta hai. Later custom domain add karein to APP_URL update aur redeploy karein.

## 7. Free host scheduler, backups aur monitoring

Supervisor container awake hone par `schedule:work` chalata hai; har minute private heartbeat banti hai. **App sleep ho to scheduler bhi stop hota hai.** Current business rules ko daily reset/no-show cron ki zaroorat nahi: absent online Pending aur paid unseen Confirmed rehte hain, reserved seats/tokens retain hote hain. Core operations synchronous hain, queue worker required nahi.

`/up` application + DB availability probe hai. Local/Docker operator `php artisan ops:check --production` se DB/cache, asset manifest, heartbeat, HTTPS/secure sessions/config checks kar sakta hai. Free dashboard mein shell nahi, is command ko public admin endpoint na banayein. Startup heartbeat ko continuous cron verification ka proof na samjhein; Render logs mein later scheduler output inspect karein. Render/Aiven dashboard health/error/usage notifications operator email ko configure karein. Free idle cold start ko normal behavior ke taur par record karein; pings se always-on uptime claim na karein.

Aiven free documentation backups include karti hai, lekin user-controlled restore/PITR/retention ko paid plan jaisa assume na karein. Go-live/acceptance se pehle independent encrypted export aur separate restore drill chahiye. Local MySQL **8.4 client** se private mode-protected option file use karein, actual Aiven port/CA aur `ssl-mode=VERIFY_IDENTITY` configure karein. Database name actual demo name ho. Dump credentials command line/GitHub mein na daalein. `--single-transaction --quick --no-tablespaces --set-gtid-purged=OFF --skip-triggers` current InnoDB schema ke liye hain; migrations ke dauran dump na lein. PowerShell par binary SQL ko pipeline ke bajaye `--result-file` mein export karein.

`hosting/backup-database.sh` Linux backup runner ke liye ready hai: GPG-encrypted dump + checksum independent rclone remote par copy karti hai, pipeline/upload failure par nonzero exit hota hai. Isay free Render sleeping filesystem/cron par durable backup na samjhein. User ke trusted Linux runner/PC/independent backup host par daily **02:15 Pakistan = previous day 21:15 UTC** schedule karein; runner ka online rehna backup ke liye zaroori hai. `hosting/mysql-backup.cnf.example` private copy mein actual Aiven host/port/credentials aur SSL CA options supply karein. Cron example **self-managed Linux runner/reference** hai, Render dashboard command nahi. No paid runner/account create kiya gaya hai.

Private password manager mein APP_KEY, cloud account recovery aur backup GPG private key/passphrase server se alag save karein. Doctor photos free disk par durable nahi: demo ke liye originals private backup rakhein; full persistent upload acceptance external storage configuration tak pending hai. Actual records ka restore test isolated DB/app mein karein, public stage mein real patients expose na karein.

Alert acceptance: DB unavailable, app error, stale backups >26h, Aiven disk >80%, certificate issue aur scheduler failure while container awake. Actual notification delivery test karein; dashboard configuration/alerts abhi user ko karni hain. Logs/references public GitHub issues mein paste karte waqt patient data redact karein.

## 8. Backup restore aur staged workflow verification

Offsite se encrypted dump + checksum **download** karke hash verify karein, decrypt karke separate restore database/user mein import karein. Production/current demo DB overwrite na karein. Restored app ko original backed-up APP_KEY dein; sessions invalidate karne ka recovery decision record karein. Count/history, MR identity, payments/refunds, draft/final prescriptions aur reprint compare karein. Timestamp/hash/result aur measured recovery duration private evidence mein save karein. Daily export ki initial recovery loss window up to 24h hai; actual recovery time drill se pata chalega.

Phir fabricated booking → Reception payment/token → Priority → Doctor prescription/completion → print/reprint → Admin refund/report flow run karein. Role permissions, duplicate-submit recovery aur no-show preservation verify karein. 80 mm receipt aur A4 prescription PDF/native output inspect karein. Staff ke liye [STAFF_HANDOFF.md](STAFF_HANDOFF.md) available hai. Internal testing files aur planning notes public repository mein shamil nahi hain.

**Hosted acceptance deployment ke baad:** selected scheduler/backup/alerts/restore aur actual staged journeys verify karein. Docker configuration prepared hai; local image build ya Render deploy success abhi claim nahi kiya gaya.

## 9. Subsequent updates / common errors

Code change → local checks → Git commit/push → Render Manual Deploy. APP_KEY, DB credentials aur Aiven data preserve karein. Schema change se pehle backup lein; one free demo instance par operations temporarily pause karein. Failed update mein compatible previous image rollback ho sakta hai, lekin database automatically reverse nahi hota; blind `migrate:rollback`/reset na karein. Old migration ke saath incompatible DB ke liye reviewed forward fix ya coordinated restore required hai.

| Error | Check |
| --- | --- |
| PHP runtime not found | Docker runtime select ho, native PHP runtime nahi |
| Docker COPY missing file | Root Directory blank aur build context repository root ho |
| SQL connection refused / timeout | Aiven running, correct host/port, outbound IPs allowed |
| TLS/CA verification failed | Correct project CA Secret File/path; verification disable na karein |
| 419 / insecure form URLs | APP_URL HTTPS, proxy setting, stable APP_KEY, database sessions |
| 500 / migration failed | Render logs, Aiven permissions/version, latest migration; no reset |
| First request slow | Render free cold start; presentation se pehle app kholein |
| Photo missing after restart | Free ephemeral disk limit; original re-upload, persistent storage pending |

Official references: [Render PHP/Docker](https://render.com/docs/deploy-php-laravel-docker), [free limits](https://render.com/docs/free), [runtime secrets](https://render.com/docs/docker-secrets), [Aiven free MySQL](https://aiven.io/docs/products/mysql/concepts/mysql-free-tier), [Aiven TLS](https://aiven.io/docs/platform/concepts/tls-ssl-certificates), [Laravel deployment](https://laravel.com/docs/13.x/deployment), [GitHub publishing](https://docs.github.com/en/migrations/importing-source-code/using-the-command-line-to-import-source-code/adding-locally-hosted-code-to-github), [MySQL dumps](https://dev.mysql.com/doc/refman/8.4/en/mysqldump.html).
