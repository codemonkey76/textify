<?php

use App\Http\Controllers\AwsSnsController;
use App\Http\Controllers\SmsDeliveryController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\EnsureSingleWavAttachment;
use App\Http\Middleware\VerifyAwsSnsSignature;
use App\Http\Middleware\VerifyClickSendSignature;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login.form');

Route::post('/inbound', WebhookController::class)
    ->middleware([
        VerifyWebhookSignature::class,
        EnsureSingleWavAttachment::class
    ]);

Route::post('/sns', AwsSnsController::class)
    ->middleware([
        VerifyAwsSnsSignature::class
    ]);

Route::post('/delivery', SmsDeliveryController::class)
    ->middleware([
        VerifyClickSendSignature::class
    ]);

require __DIR__ . '/auth.php';
