<?php

namespace App\Http\Controllers;

use App\Jobs\CheckTranscriptionStatus;
use App\Models\Transcription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AwsSnsController extends Controller
{
    public function __invoke(Request $request)
    {
        $rawBody = $request->getContent();
        $decoded = json_decode($rawBody, true) ?? [];
        $decodedMessage = isset($decoded['Message']) ? json_decode($decoded['Message'], true) : null;

        $jobName = data_get($decodedMessage, 'detail.TranscriptionJobName');

        if (!$jobName) {
            Log::error("Invalid SNS message format: missing job details", ['decoded_message' => $decodedMessage]);
            return response()->json(['error' => 'Invalid SNS Message'], Response::HTTP_BAD_REQUEST);
        }

        $transcription = Transcription::whereJobName($jobName)->firstOrFail();

        CheckTranscriptionStatus::dispatch($jobName, $transcription->account_id);
        return response()->json(['message' => 'Received'], Response::HTTP_OK);
    }
}
