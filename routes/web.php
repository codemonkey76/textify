<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login.form');

require __DIR__ . '/auth.php';
