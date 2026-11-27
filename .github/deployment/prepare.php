<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'pgsql'
    || config('database.connections.pgsql.search_path') !== 'talento'
    || config('database.migrations.table') !== 'talento.migrations') {
    fwrite(STDERR, "Configura DB_SEARCH_PATH=talento y DB_MIGRATIONS_TABLE=talento.migrations.\n");
    exit(1);
}

DB::statement('CREATE SCHEMA IF NOT EXISTS talento');
