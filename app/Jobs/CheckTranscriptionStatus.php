<?php

namespace App\Jobs;

use Aws\TranscribeService\TranscribeServiceClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckTranscriptionStatus implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $jobName, protected int $accountId) {}

    public function handle(TranscribeServiceClient $transcribeClient): void
    {
        try {
            $result = $transcribeClient->getTranscriptionJob([
                'TranscriptionJobName' => $this->jobName
            ]);

            $status = $result['TranscriptionJob']['TranscriptionJobStatus'];

            if ($status === "COMPLETED") {
                $transcriptionUri = $result['TranscriptionJob']['Transcript']['TranscriptFileUri'];
                $transcription = file_get_contents($transcriptionUri);

                Log::info("Transcription completed successfully for job {$this->jobName}.", [
                    'transcription' => $transcription,
                ]);
                NotifyAccount::dispatch($this->accountId, $transcription);
            } elseif ($status === "FAILED") {
                Log::error("Transcription job {$this->jobName} failed.", [
                    'reason' => $result['TranscriptionJob']['FailureReason'],
                ]);
            } else {
                self::dispatch($this->jobName, $this->accountId)->delay(now()->addSeconds(config('services.aws.transcription.delay')));
            }
        } catch (\Exception $e) {
            Log::error("Failed to check transcription status for job {$this->jobName}: " . $e->getMessage());
        }
    }
}
