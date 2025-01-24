<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class MailgunWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Lookup recipient account
        $account = Account::whereEmail($request->input('receipient'))->first();

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

        $attachmentKey = "attachment-1";
        $file = $request->file($attachmentKey);
        $filePath = $file->store('attachments', $disk);
        logger()->info("WAV Attachment saved to $filePath");

        return response()->json([
            'message' => 'Webhook processed successfully.',
            'attachment' => [
                'original_name' => $file->getClientOriginalName(),
                'path' => $filePath,
                'disk' => $disk
            ],
        ], 200);
    }
}
