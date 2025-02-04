<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login.form');

require __DIR__ . '/auth.php';
