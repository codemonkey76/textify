<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleWavAttachment
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get all uploaded files from the request
        $attachments = $request->allFiles();

        // Ensure exactly one attachment is present
        if (count($attachments) !== 1) {
            return $this->errorResponse('The email must contain exactly one attachment.');
        }

        // Get the first (and only) file
        $attachment = array_values($attachments)[0];

        // Validate the file type is .wav
        if (strtolower($attachment->getClientOriginalExtension()) !== 'wav') {
            return $this->errorResponse('The attachment must be a wav file.');
        }

        return $next($request);
    }

    protected function errorResponse(string $message): Response
    {
        return response()->json(['error' => $message], 400);
    }
}
