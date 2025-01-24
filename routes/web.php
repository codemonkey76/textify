<?php

use App\Http\Controllers\MailgunWebhookController;
use App\Http\Middleware\EnsureSingleWavAttachment;
use App\Http\Middleware\VerifyMailgunSignature;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/inbound', [MailgunWebhookController::class, 'handle'])->middleware([
    VerifyMailgunSignature::class,
    EnsureSingleWavAttachment::class
]);
