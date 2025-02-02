<?php

namespace App\Http\Controllers;

use App\Jobs\TranscribeVoicemail;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // Lookup recipient account
        $account = Account::whereEmail($request->input('to'))->first();
        Log::info("Account lookup:", ['email' => $request->input('to'), 'account' => $account]);

        if (!$account) {
            return response()->json(['error' => 'Associated account not found.'], 404);
        }

        // Get destinations
        $destinations = $account->destinations()->pluck('phone')->toArray();
        if (empty($destinations)) {
            return response()->json(['error' => 'No destination phone number found.'], 404);
        }

        // Transcribe

        logger()->info("Received mailgun email:", [
            'account' => $account->email,
            'destinations' => $destinations
        ]);

        $disk = config('filesystems.default');

        $file = array_values($request->allFiles())[0];
        $filePath = $file->store('attachments', $disk);
        if (!$filePath) {
            Log::error("Failed to store attachment.");
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to store the attachment.');
        }

        if (!Storage::disk($disk)->exists($filePath)) {
            Log::error("Stored file not found: $filePath");
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'File storage error.');
        }

        TranscribeVoicemail::dispatch($filePath, $account->id);

        return response()->json([
            'message' => 'Webhook processed successfully.',
            'attachment' => [
                'original_name' => $file->getClientOriginalName(),
                'path' => $filePath,
                'disk' => $disk
            ],
        ], Response::HTTP_OK);
    }
}
