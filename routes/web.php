<?php

use App\Http\Controllers\WebhookController;
use App\Http\Middleware\EnsureSingleWavAttachment;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('/inbound', [WebhookController::class, 'handle'])->middleware([
    VerifyWebhookSignature::class,
    EnsureSingleWavAttachment::class
]);
