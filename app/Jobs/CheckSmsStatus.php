<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\ClickSendApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckSmsStatus implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $messageId) {}

    public function handle(ClickSendApi $clickSendApi): void
    {
        Log::info("Checking SMS status for message: {$this->messageId}");


        $response = $clickSendApi->checkSmsStatus($this->messageId);

        if ($response['http_code'] !== 200 || empty($response['data'])) {
            Log::warning("Failed to fetch SM status for {$this->messageId}", ['response' => $response]);
            return;
        }

        $messageData = $response['data'];
        $statusCode = (int) ($messageData['status_code'] ?? 0);

        if ($statusCode === 200) {
            Log::info("SMS {$this->messageId} is still pending, re-queuing.");
            self::dispatch($this->messageId)->delay(now()->addSeconds(30));
            return;
        }

        $status = match ($statusCode) {
            201 => 'SENT',
            301 => 'FAILED',
            default => 'UNKNOWN'
        };


        SmsMessage::where('message_id', $this->messageId)
            ->update([
                'status' => $status,
                'error_code' => $messageData['error_code'] ?? null,
                'error_message' => $messageData['error_text'] ?? null
            ]);
    }
}
