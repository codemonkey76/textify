<?php

use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Horizon;
use Laravel\Telescope\Telescope;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__ . '/auth.php';
