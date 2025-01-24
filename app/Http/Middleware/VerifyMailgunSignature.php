<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMailgunSignature
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
        Log::info("Received Email:", ['signature' => $signature, 'timestamp' => $timestamp, 'token' => $token, 'request' => $request->all()]);

        if (!$signature || !$timestamp || !$token) {
            return response('Invalid request', Response::HTTP_UNAUTHORIZED);
        }

        $apiKey = config('services.mailgun.signing_key');
        $computedSignature = hash_hmac('sha256', $timestamp . $token, $apiKey);

        if (!hash_equals($computedSignature, $signature)) {
            return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
