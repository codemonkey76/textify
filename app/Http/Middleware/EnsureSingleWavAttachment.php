<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleWavAttachment
{
    public function handle(Request $request, Closure $next): Response
    {
        $attachmentCount = $request->get('attachment-count', 0);

        if ($attachmentCount != 1) {
            return response()->json([
                'error' => 'The email must contain exactly one attachment.'
            ], 400);
        }

        $attachmentKey = 'attachment-1';

        if (!$request->hasFile($attachmentKey)) {
            return response()->json([
                'error' => 'The attachment is missing.'
            ], 400);
        }

        $attachment = $request->file($attachmentKey);

        if ($attachment->getClientOriginalExtension() !== 'wav') {
            return response()->json(
                [
                    'error' => 'The attachment must be a wav file.'
                ],
                400
            );
        }

        return $next($request);
    }
}
