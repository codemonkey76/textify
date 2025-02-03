<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\SmsMessage;
use App\Services\ClickSendApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyDestination implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $accountId, public string $phoneNumber, protected string $message) {}

    /**
     * Execute the job.
     */
    public function handle(ClickSendApi $clickSendApi): void
    {
        $response = $clickSendApi->sendSms($this->phoneNumber, $this->message);

        if ($response['http_code'] === 200) {
            Log::info("sendSms response:", ['response' => $response]);
            $messageId = $response['body']['data']['messages'][0]['message_id'] ?? null;

            if ($messageId) {
                SmsMessage::create([
                    'account_id' => $this->accountId,
                    'phone_number' => $this->phoneNumber,
                    'message' => $this->message,
                    'message_id' => $messageId,
                    'status' => MessageStatus::Pending
                ]);
            } else {
                Log::warning("Message ID not found in the response for {$this->phoneNumber}.");
            }
        } else {
            Log::error("Failed to send SMS to {$this->phoneNumber}", [
                'response' => $response
            ]);
        }
    }
}
