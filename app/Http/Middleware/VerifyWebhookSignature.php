<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->input('signature');
        $timestamp = $request->input('timestamp');
        $token = $request->input('token');

        if (!$signature || !$timestamp || !$token) {
            Log::info("Missing signature, timestamp or token");
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid request');
        }

        if (abs(time() - $timestamp) > 300) { // 5-minute tolerance
            Log::info("Timestamp is too old", ['timestamp' => $timestamp]);
            abort(Response::HTTP_UNAUTHORIZED, 'Request expired');
        }

        $apiKey = config('services.webhook.signing_key');
        $computedSignature = hash_hmac('sha256', $timestamp . $token, $apiKey);

        if (!hash_equals($computedSignature, $signature)) {
            Log::info("Signature doesn't match", ['computed' => $computedSignature, 'signature' => $signature]);
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid signature');
        }

        return $next($request);
    }
}
