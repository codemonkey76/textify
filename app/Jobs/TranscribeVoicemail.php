<?php

namespace App\Jobs;

use Aws\TranscribeService\TranscribeServiceClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TranscribeVoicemail implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $filePath, public int $accountId) {}

    public function handle(TranscribeServiceClient $transcribeClient): void
    {
        Log::info('in handle');
        $jobName = 'transcription-' . now()->format('Y-md-h-i-s') . '-' . Str::random(5);
        $fileUrl = Storage::url($this->filePath);

        Log::info("Starting AWS Transcription Job", [
            'jobName' => $jobName,
            'fileUrl' => $fileUrl,
            'accountId' => $this->accountId
        ]);

        try {
            $transcribeClient->startTranscriptionJob([
                'TranscriptionJobName' => $jobName,
                'LanguageCode' => config('services.aws.transcription.language_code'),
                'MediaFormat' => config('services.aws.transcription.media_format'),
                'Media' => [
                    'MediaFileUri' => $fileUrl,
                ],
            ]);

            Log::info("Transcription job started successfully", ['jobName' => $jobName]);

            CheckTranscriptionStatus::dispatch($jobName, $this->accountId)
                ->delay(now()->addSeconds(config('services.aws.transcription.delay')));

            Log::info("CheckTranscriptionStatus job dispatched", ['jobName' => $jobName, 'accountId' => $this->accountId]);
        } catch (\Exception $e) {
            Log::error("Failed to start transcription job: " . $e->getMessage());
        }
    }
}
