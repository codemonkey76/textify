<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleWavAttachment
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get all uploaded files from the request
        $attachments = $request->allFiles();

        // Ensure exactly one attachment is present
        if (count($attachments) !== 1) {
            Log::info("Wrong number of attachments", ['attachment-count' => count($attachments)]);
            return $this->errorResponse('The email must contain exactly one attachment.');
        }

        // Get the first (and only) file
        $attachment = array_values($attachments)[0];

        // Validate the file type is .wav
        if (strtolower($attachment->getClientOriginalExtension()) !== 'wav') {
            Log::info("Expected a wav file", ['extension' => $attachment->getClientOriginalExtension()]);
            return $this->errorResponse('The attachment must be a wav file.');
        }

        return $next($request);
    }

    protected function errorResponse(string $message): Response
    {
        return response()->json(['error' => $message], 400);
    }
}
