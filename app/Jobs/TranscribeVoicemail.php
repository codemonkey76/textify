<?php

namespace App\Jobs;

use Aws\TranscribeService\TranscribeServiceClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscribeVoicemail implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $filePath, protected int $accountId) {}

    public function handle(TranscribeServiceClient $transcribeClient): void
    {
        $jobName = 'transcription-' . now()->format('Y-md-h-i-s');
        $fileUrl = Storage::url($this->filePath);

        try {
            $transcribeClient->startTranscriptionJob([
                'TranscriptionJobName' => $jobName,
                'LanguageCode' => config('services.aws.transcription.language_code'),
                'MediaFormat' => config('services.aws.transcription.media_format'),
                'Media' => [
                    'MediaFileUri' => $fileUrl,
                ],
            ]);

            CheckTranscriptionStatus::dispatch($jobName, $this->accountId)->delay(now()->addSeconds(config('services.aws.transcription.delay')));
        } catch (\Exception $e) {
            Log::error("Failed to start transcription job: " . $e->getMessage());
        }
    }
}
