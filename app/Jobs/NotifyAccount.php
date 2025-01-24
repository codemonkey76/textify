<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyAccount implements ShouldQueue
{
    use Queueable;

    public function __construct(protected int $accountId, protected string $transcript) {}

    public function handle(): void
    {
        try {
            $account = Account::find($this->accountId);

            if (!$account) {
                Log::error("Account not found for ID: {$this->accountId}");
                return;
            }

            foreach ($account->destinations as $destination) {
                NotifyDestination::dispatch($destination, $this->transcript);
            }

            Log::info("Notification jobs dispatched for account ID: {$this->accountId}");
        } catch (\Exception $e) {
            Log::error("Failed to notify account {$this->accountId} of transcription: " . $e->getMessage());
        }
    }
}
