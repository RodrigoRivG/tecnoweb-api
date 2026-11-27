<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

DB::select('SELECT id FROM talento.facultad LIMIT 1');
DB::select('SELECT id FROM talento.usuario LIMIT 1');
