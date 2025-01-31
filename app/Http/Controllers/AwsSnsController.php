<?php

namespace App\Http\Controllers;

use App\Jobs\CheckTranscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AwsSnsController extends Controller
{
    public function __invoke(Request $request)
    {
        $rawBody = $request->getContent();
        $decoded = json_decode($rawBody, true) ?? [];

        // Extract & decode the actual SNS Message
        if (isset($decoded['Message'])) {
            $decodedMessage = json_decode($decoded['Message'], true);
        } else {
            $decodedMessage = null;
        }

        Log::info("Received SNS Notification", [
            'raw_body' => $rawBody,
            'decoded' => $decoded,
            'decoded_message' => $decodedMessage,
            'headers' => $request->headers->all()
        ]);
        //     $jobName = '';
        Log::info('Decoded', ['decoded' => $decoded]);
        Log::info('Decoded Message', ['message' => $decodedMessage]);
        //        $accountId = 1;

        //      CheckTranscriptionStatus::dispatch($jobName, $accountId);
        Log::info("Receieved SNS Notification", ['raw_body' => $request->getContent(), 'headers' => $request->headers->all()]);
        return response()->json(['message' => 'Received'], Response::HTTP_OK);
    }
}
