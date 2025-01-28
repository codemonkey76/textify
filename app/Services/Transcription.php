<?php

namespace App\Services;

use Aws\TranscribeService\TranscribeServiceClient;
use Illuminate\Contracts\Config\Repository;

class Transcription
{
    private array $options;
    private $bucket;
    private $defaultLanguage;
    private $defaultMediaFormat;
    private TranscribeServiceClient $client;

    public function __construct(Repository $config)
    {
        $this->options = [
            'version' => 'latest',
            'credentials' => [
                'key' => $config->get('transcription.credentials.key'),
                'secret' => $config->get('transcription.credentials.secret')
            ],
            'region' => $config->get('transcription.region')
        ];

        $this->bucket = $config->get('transcription.bucket');
        $this->vm_folder = $config->get('transcription.folder');

        $this->client = new TranscribeServiceClient($this->options);

        $this->defaultLanguage = $config->get('transcription.language_code');
        $this->defaultMediaFormat = $config->get('transcription.media_format');
    }

    public function start($file, $languageCode = null, $mediaFormat = null)
    {
        $languageCode ??= $this->defaultLanguage;
        $mediaFormat ??= $this->defaultMediaFormat;

        $jobName = 'transcription-' . now()->format('Y-md-h-i-s');
        $jobOptions = [
            'LanguageCode' => $languageCode,
            'Media' => [
                'MediaFileUri' => "s3://{$this->bucket}/$file"
            ],
            'MediaFormat' => $mediaFormat,
            'TranscriptionJobName' => $jobName
        ];

        $this->client->startTranscriptionJob($jobOptions);

        return $jobName;
    }

    public function status($jobName)
    {
        $result = $this->client->getTranscriptionJob([
            'TranscriptionJobName' => $jobName
        ]);
        $status = $result->search('TranscriptionJob.TranscriptionJobStatus');

        if ($status === "COMPLETED") {
            $file = $result->search('TranscriptionJob.Transcript.TranscriptFileUri');
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $file);
            $transcriptObject = json_decode($response->getBody());
            $transcript = data_get($transcriptObject, 'results.transcripts.0.transcript');

            return (object)[
                'status' => $status,
                'transcript' => $transcript
            ];
        }

        return (object)[
            'status' => $status
        ];
    }
}
