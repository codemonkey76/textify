<?php

namespace App\Http\Controllers;

use App\Enums\MessageStatus;
use App\Models\SmsMessage;
use App\Services\ClickSendApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SmsDeliveryController extends Controller
{

    public function __construct(protected ClickSendApi $clickSendApi) {}
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $message_id = $request->input('messageid');
        if (empty($message_id)) {
            return response()->json(['error' => 'Missing `messageid` field in payload'], Response::HTTP_BAD_REQUEST);
        }

        $message = SmsMessage::whereMessageId($message_id)->first();
        if (!$message) {
            return response()->json(['error', "Message with message_id: $message_id not found"], Response::HTTP_NOT_FOUND);
        }

        /*$response = $this->clickSendApi->checkSmsStatus($message_id);*/
        /**/
        /**/
        /*if ($response['http_code'] !== 200 || empty($response['data'])) {*/
        /*    Log::warning("Failed to fetch SMS status for {$message_id}", ['response' => $response]);*/
        /*    return response()->json(['error' => "Failed to fetch SMS status for $message_id"], Response::HTTP_INTERNAL_SERVER_ERROR);*/
        /*}*/
        /**/
        /*$messageData = $response['data'];*/
        /*Log::channel('clicksend')->info("Delivery Report:", ['data' => $messageData]);*/
        /*$statusCode = (int) ($messageData['status_code'] ?? 0);*/

        $statusCode = $request->input('status_code') ?? 0;

        $status = match ($statusCode) {
            200 => MessageStatus::Pending,
            201 => MessageStatus::Delivered,
            301 => MessageStatus::Failed,
            default => MessageStatus::Unknown
        };

        $message->update([
            'status' => $status,
            'error_code' => $messageData['error_code'] ?? null,
            'error_message' => $messageData['error_text'] ?? null
        ]);
    }
}
