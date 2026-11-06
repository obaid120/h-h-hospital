<?php

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\InitialAdminSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('staff:bootstrap-admin {--email=} {--name=}', function (): int {
    if (User::query()->where('role', StaffRole::Admin)->where('is_active', true)->exists()) {
        $this->info('An active administrator already exists. No account or password was changed.');

        return 0;
    }

    $local = app()->environment('local', 'testing');
    $email = $this->option('email') ?: config('staff.initial_admin.email') ?: ($local ? 'admin@hh-hospital.test' : null);
    $name = $this->option('name') ?: config('staff.initial_admin.name');
    $password = config('staff.initial_admin.password');
    $credentialsPath = null;

    if (! $password) {
        if (! $local) {
            $this->error('Set INITIAL_ADMIN_EMAIL and INITIAL_ADMIN_PASSWORD securely before bootstrapping outside development.');

            return 1;
        }

        $credentialsPath = storage_path('app/private/initial-admin.txt');
        if (File::exists($credentialsPath)) {
            $this->error('A local initial-admin credentials file already exists. Inspect it before bootstrapping again.');

            return 1;
        }

        $password = Str::password(24);
        File::ensureDirectoryExists(dirname($credentialsPath));
        File::put($credentialsPath, "H&H Hospital — local administrator\nEmail: {$email}\nPassword: {$password}\n\nKeep this private. Change the password from Staff accounts if needed.\n");
    }

    config(['staff.initial_admin' => compact('name', 'email', 'password')]);

    try {
        app(InitialAdminSeeder::class)->run();
        $createdAdmin = User::query()->where('email', Str::lower((string) $email))->first();
        if (! $createdAdmin?->isActiveAdmin() || ! Hash::check($password, $createdAdmin->password)) {
            throw new RuntimeException('An administrator was created by another operation. No existing credentials were changed.');
        }
    } catch (Throwable $exception) {
        if ($credentialsPath) {
            File::delete($credentialsPath);
        }

        throw $exception;
    }

    $this->info('Initial administrator created.');
    if ($credentialsPath) {
        $this->info('Private local credentials: '.$credentialsPath);
    }

    return 0;
})->purpose('Create the first administrator without a fixed default password');

Artisan::command('ops:heartbeat', function (): int {
    File::replace(storage_path('framework/schedule-heartbeat'), (string) now()->timestamp, 0640);
    $this->info('Scheduler heartbeat recorded.');

    return 0;
})->purpose('Record scheduler liveness without changing any OPD records');

Schedule::command('ops:heartbeat')->everyMinute()->evenInMaintenanceMode();

Artisan::command('ops:database', function (): int {
    if (config('hosting.allow_self_signed_demo_tls') && ! app()->environment('staging')) {
        $this->error('Self-signed demo TLS is allowed only in staging.');

        return 1;
    }

    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->error('Hosted database startup requires MySQL.');

        return 1;
    }

    try {
        $status = DB::selectOne("SHOW SESSION STATUS LIKE 'Ssl_cipher'");
        if (blank($status?->Value)) {
            $this->error('MySQL connection is not encrypted. Startup stopped before migrations.');

            return 1;
        }
    } catch (Throwable $exception) {
        report($exception);
        $this->error('Encrypted MySQL connection check failed. Review the private server logs.');

        return 1;
    }

    $this->info(config('hosting.allow_self_signed_demo_tls')
        ? 'MySQL TLS encryption confirmed. Demo mode does not verify server identity.'
        : 'MySQL TLS encryption confirmed.');

    return 0;
})->purpose('Require an encrypted hosted MySQL session before schema or data writes');

Artisan::command('ops:check {--production : Also require safe staging/production settings}', function (): int {
    $checks = [];

    try {
        DB::select('SELECT 1');
        DB::table('migrations')->limit(1)->get();
        $checks['Database connection and migration table'] = true;
    } catch (Throwable $exception) {
        report($exception);
        $checks['Database connection and migration table'] = false;
    }

    $probe = 'ops:probe:'.Str::uuid();

    try {
        Cache::put($probe, 'ok', 30);
        $checks['Shared cache read/write'] = Cache::get($probe) === 'ok';
        Cache::forget($probe);
    } catch (Throwable $exception) {
        report($exception);
        $checks['Shared cache read/write'] = false;
    }

    $checks['Private storage and bootstrap cache writable'] = is_writable(storage_path('app/private')) && is_writable(base_path('bootstrap/cache'));
    $checks['Production asset manifest exists'] = File::exists(public_path('build/manifest.json')) && ! File::exists(public_path('hot'));
    $heartbeat = storage_path('framework/schedule-heartbeat');
    $timestamp = File::exists($heartbeat) ? trim(File::get($heartbeat)) : '';
    $checks['Scheduler heartbeat within 3 minutes'] = ctype_digit($timestamp) && (int) $timestamp <= now()->timestamp && (int) $timestamp >= now()->timestamp - 180;

    if ($this->option('production')) {
        $checks['Production or staging environment'] = app()->environment('production', 'staging');
        $checks['Debug disabled'] = config('app.debug') === false;
        $checks['HTTPS application URL'] = str_starts_with((string) config('app.url'), 'https://');
        $checks['Secure encrypted staff sessions'] = config('session.secure') === true && config('session.encrypt') === true;
        $checks['MySQL database driver'] = DB::connection()->getDriverName() === 'mysql';
        $checks['Application key configured'] = filled(config('app.key'));
        $checks['Bootstrap password removed'] = blank(config('staff.initial_admin.password'));
        $checks['Configuration cached'] = app()->configurationIsCached();
    }

    foreach ($checks as $label => $passed) {
        $this->line(($passed ? 'PASS' : 'FAIL').' '.$label);
    }

    return in_array(false, $checks, true) ? 1 : 0;
})->purpose('Check deployment dependencies and scheduler; never print credentials or patient data');
