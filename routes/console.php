<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('vapt:preflight', function () {
    $required = ['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'dom', 'xml', 'xmlwriter', 'curl', 'fileinfo'];
    $missing = array_values(array_filter($required, fn ($extension) => ! extension_loaded($extension)));

    $this->info('VAPT Platform preflight');
    $this->line('PHP: ' . PHP_VERSION);
    $this->line('Environment: ' . config('app.env'));
    $this->line('Debug: ' . (config('app.debug') ? 'ON' : 'OFF'));
    $this->line('Database: ' . config('database.default'));
    $this->line('Queue: ' . config('queue.default'));
    $this->line('Session: ' . config('session.driver'));

    if ($missing) {
        $this->error('Missing PHP extensions: ' . implode(', ', $missing));
        $this->line('Install the missing extensions before running the application or PDF/report tooling.');
    } else {
        $this->info('Required PHP extensions: OK');
    }

    try {
        $this->line('Database connection: ' . (\Illuminate\Support\Facades\DB::connection()->getPdo() ? 'OK' : 'FAILED'));
        $this->line('Migration table: ' . (\Illuminate\Support\Facades\Schema::hasTable('migrations') ? 'OK' : 'NOT INITIALIZED'));
        $this->line('Queue jobs table: ' . (\Illuminate\Support\Facades\Schema::hasTable('jobs') ? 'OK' : 'NOT MIGRATED'));
        $this->line('Session table: ' . (\Illuminate\Support\Facades\Schema::hasTable('sessions') ? 'OK' : 'NOT MIGRATED'));
    } catch (\Throwable $e) {
        $this->error('Database check failed: ' . $e->getMessage());
        $this->line('Run migrations and verify DB_* settings.');
    }

    if (config('app.env') !== 'local' && config('app.debug')) {
        $this->warn('APP_DEBUG is enabled outside local environment. Disable it for production.');
    }

    return $missing ? 1 : 0;
})->purpose('Check PHP extensions and core VAPT runtime prerequisites');
