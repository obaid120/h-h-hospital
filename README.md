# H&H Hospital OPD

A hospital outpatient management application built with **Laravel 13, PHP 8.4, MySQL, Blade, Tailwind CSS and Motion**. It connects online appointment requests with reception, payments, doctor queues and prescriptions in one role-based workflow.

## Features

- Public doctor profiles, availability calendars and online appointment requests.
- Individual Admin, Reception and Doctor accounts with role-based permissions.
- Patient registration, permanent medical record (MR) numbers and returning-patient history.
- Reception arrival confirmation, normal/Priority walk-ins, cash collection and printable receipts.
- Doctor queues, draft prescriptions, explicit OPD completion and prescription reprints.
- Eligible appointment cancellation, Admin-controlled partial/full refunds and audit history.
- Admin appointment, finance, staff, schedule and hospital settings screens.
- Responsive layouts and A4 prescription / 80 mm receipt print styles.

Online bookings remain pending until reception verifies the patient and confirms payment. Payments, tokens, refunds and visit completion follow server-side rules with duplicate-request protection.

## Requirements

- **PHP 8.4** and Composer 2. Required extensions are checked by `composer check-platform-reqs --no-dev`; enable PDO MySQL, mbstring, XML/DOM, cURL, fileinfo and OpenSSL.
- **MySQL 8.4** with InnoDB.
- **Node.js 24** and npm to build frontend assets.
- Git.

## Installation

### 1. Clone and install dependencies

```bash
git clone https://github.com/obaid120/h-h-hospital.git
cd h-h-hospital/h-h-hospital
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci --ignore-scripts
```

The repository contains an inner `h-h-hospital/` directory. Run Composer, npm and Artisan commands from that application directory. The installation above installs the application without development/test packages.

### 2. Create an empty MySQL database

Create `handh_hospital` with `utf8mb4` encoding and an application user with access to that database. Do not connect the app as the MySQL root user.

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead. Edit the connection and initial administrator values:

```dotenv
APP_NAME="H&H Hospital"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=handh_hospital
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

HOSPITAL_TIMEZONE=Asia/Karachi
HOSPITAL_CURRENCY=PKR
QUEUE_CONNECTION=sync

INITIAL_ADMIN_NAME="Hospital Administrator"
INITIAL_ADMIN_EMAIL=admin@your-domain.example
INITIAL_ADMIN_PASSWORD="REPLACE_WITH_A_UNIQUE_STRONG_PASSWORD"
```

Use your actual database port and credentials. The initial Admin password must have at least 12 characters, uppercase and lowercase letters, and numbers. There is no shared default password.

### 3. Initialize and run

```bash
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan staff:bootstrap-admin
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000` and sign in at `/staff/login` using the Admin credentials you configured. After the first successful sign-in, clear `INITIAL_ADMIN_PASSWORD` from `.env` and run `php artisan config:clear`.

Create Reception and Doctor users from **Staff accounts**, then configure doctor profiles, fees, OPD schedules and hospital contact/print settings. Existing patients or sample accounts are not bundled with this repository.

Generate `APP_KEY` only once for a new installation. Keep the same key when updating or restoring an existing deployment.

## Free demo deployment

The prepared deployment uses:

| Service | Purpose |
| --- | --- |
| GitHub | Application source |
| Render Free Web Service | Docker-based Laravel app with Nginx and PHP-FPM |
| Aiven Free MySQL | Persistent application database over TLS |

Follow the [deployment guide](docs/DEPLOYMENT.md) for the Aiven CA certificate, Render environment variables, initial Admin setup, backups and verification. The repository includes a Dockerfile and optional `render.yaml` Blueprint. Use the **repository root** as the Docker build context and `h-h-hospital/Dockerfile` as the Dockerfile path.

Render Free can sleep when idle and does not preserve local uploaded photos across restarts. Aiven stores database records separately. This setup is intended for a demo; durable photo storage, backups, restore checks and operational availability must be verified before live hospital use. The Docker image and hosted deployment have not yet been verified in a running cloud environment.

## Staff and operations

- [Staff handoff guide](docs/STAFF_HANDOFF.md): daily Admin, Reception and Doctor workflows, cancellation/refund handling, recovery and reprints.
- `/up`: application and database connectivity health route.
- `php artisan ops:check --production`: operator checks for deployment configuration, dependencies and scheduler heartbeat.
- Current OPD operations run synchronously; a queue worker is not required. The included Docker supervisor runs the scheduler while the container is awake.

Use HTTPS and `APP_DEBUG=false` on hosted deployments. Keep `.env`, credentials, patient data, uploads and database backups private. Local testing files, internal planning notes, generated dependencies and runtime data are excluded from this public repository.
