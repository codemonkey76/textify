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
        Log::info('files', ['files' => $request->allFiles()]);

        // Ensure exactly one attachment is present
        if (count($attachments) !== 1) {
            Log::info("Wrong number of attachments", ['attachment-count' => count($attachments)]);
            abort(Response::HTTP_BAD_REQUEST, 'The email must contain exactly one attachment.');
        }

        // Get the first (and only) file
        $attachment = array_values($attachments)[0];


        // Validate the file upload
        if (!$attachment->isValid()) {
            Log::info("Invalid attachment upload", ['error' => $attachment->getErrorMessage()]);
            abort(Response::HTTP_BAD_REQUEST, 'Invalid attachment upload.');
        }

        // Validate the file type is (MIME type check is more secure)
        if ($attachment->getMimeType() !== 'audio/wav') {
            Log::info("Invalid MIME type", ['mime_type' => $attachment->getMimeType()]);
            abort(Response::HTTP_BAD_REQUEST, 'The attachment must be a valid WAV file.');
        }

        return $next($request);
    }
}
