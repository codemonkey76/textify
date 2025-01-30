<?php

use App\Http\Controllers\AwsSnsController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\EnsureSingleWavAttachment;
use App\Http\Middleware\VerifyAwsSnsSignature;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('/inbound', [WebhookController::class, 'handle'])->middleware([
    VerifyWebhookSignature::class,
    EnsureSingleWavAttachment::class
]);

Route::post('/sns', AwsSnsController::class)->middleware([
    VerifyAwsSnsSignature::class
]);
