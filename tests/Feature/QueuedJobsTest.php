<?php

namespace Tests\Feature;

use App\Jobs\CheckSmsStatus;
use App\Jobs\CheckTranscriptionStatus;
use App\Jobs\NotifyAccount;
use App\Jobs\NotifyDestination;
use App\Jobs\TranscribeVoicemail;
use App\Models\Account;
use App\Models\SmsMessage;
use App\Services\ClickSendApi;
use Aws\TranscribeService\TranscribeServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Pest\Plugins\Only;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueuedJobsTest extends TestCase
{
    use RefreshDatabase;
    protected string $filePath;
    protected int $accountId;
    protected string $jobName;
    protected $mockTranscribeObject;
    protected $mockClickSendObject;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Bus::fake();
        $this->filePath = 'attachments/test.wav';
        $this->accountId = 1;
        $this->jobName = 'test-job';
        $this->mockTranscribeObject = $this->mock(TranscribeServiceClient::class);
        $this->mockClickSendObject = $this->mock(ClickSendApi::class);

        app()->instance(TranscribeServiceClient::class, $this->mockTranscribeObject);
        app()->instance(ClickSendApi::class, $this->mockClickSendObject);
    }

    #[Test]
    #[Only]
    public function it_starts_transcription_and_queues_check_status()
    {
        $this->mockTranscribeObject
            ->shouldReceive('startTranscriptionJob')
            ->with(\Mockery::on(fn($args) => isset($args['TranscriptionJobName'])))
            ->andReturn([
                'TranscriptionJob' => [
                    'TranscriptionJobStatus' => 'IN_PROGRESS'
                ]
            ]);

        $job = new TranscribeVoicemail($this->filePath, $this->accountId);
        $job->handle(app(TranscribeServiceClient::class));

        Bus::assertDispatched(CheckTranscriptionStatus::class, fn($job) => $job->accountId === $this->accountId);
    }


    #[Test]
    public function it_handles_transcription_completion()
    {
        $this->mockTranscribeObject
            ->shouldReceive('getTranscriptionJob')
            ->with(\Mockery::on(fn($args) => isset($args['TranscriptionJobName'])))
            ->andReturn([
                'TranscriptionJob' => [
                    'TranscriptionJobStatus' => 'COMPLETED',
                    'Transcript' => [
                        'TranscriptFileUri' => 'https://example.com/transcript.json'
                    ],
                ],
            ]);
        Http::fake([
            'https://example.com/transcript.json' => Http::response(json_encode([
                'results' => ['transcripts' => [['transcript' => 'This is a test transcription.']]]
            ]), 200)
        ]);

        $job = new CheckTranscriptionStatus($this->jobName, $this->accountId);
        $job->handle(app(TranscribeServiceClient::class));

        Bus::assertDispatched(NotifyAccount::class, fn($job) => $job->accountId === $this->accountId);
    }

    #[Test]
    public function it_handles_transcription_failure()
    {
        $this->mockTranscribeObject
            ->shouldReceive('getTranscriptionJob')
            ->with(['TranscriptionJobName' => $this->jobName])
            ->andReturn([
                'TranscriptionJob' => [
                    'TranscriptionJobStatus' => 'FAILED',
                    'FailureReason' => 'Audio quality too low'
                ]
            ]);

        $job = new CheckTranscriptionStatus($this->jobName, $this->accountId);
        $job->handle(app(TranscribeServiceClient::class));

        Bus::assertNotDispatched(NotifyAccount::class, fn($job) => $job->accountId === $this->accountId);
    }

    #[Test]
    public function it_dispatches_notify_destination_jobs()
    {
        $account = Account::factory()->create(['email' => 'test@example.com']);
        $account->destinations()->createMany([
            ['phone' => '+1234567890'],
            ['phone' => '+0987654321']
        ]);

        $job = new NotifyAccount($account->id, 'Test transcription');
        $job->handle();

        Bus::assertDispatched(NotifyDestination::class, 2);
        Bus::assertDispatched(NotifyDestination::class, fn($job) => in_array($job->phoneNumber, ['+1234567890', '+0987654321']));
    }

    #[Test]
    public function it_sends_sms_via_clicksend()
    {
        $msgId = 'msg123';
        $account = Account::factory()->create(['email' => 'test@example.com']);

        $this->mockClickSendObject
            ->shouldReceive('sendSms')
            ->with('+1234567890', 'Test message')
            ->andReturn([
                'http_code' => 200,
                'body' => [
                    'data' => [
                        'messages' => [
                            ['message_id' => 'msg123']
                        ]
                    ]
                ]
            ]);

        $job = new NotifyDestination($account->id, '+1234567890', 'Test message');
        $job->handle(app(ClickSendApi::class));

        Bus::assertDispatched(CheckSmsStatus::class, fn($job) => $job->messageId === $msgId);
    }

    #[Test]
    public function it_updates_sms_status_from_clicksend()
    {
        $account = Account::create(['email' => 'test@example.com']);

        $smsMessage = SmsMessage::create([
            'message' => 'Test message',
            'phone_number' => '+1234567890',
            'message_id' => 'msg123',
            'status' => 'PENDING',
            'message_price' => 0.01,
            'message_parts' => 1,
            'account_id' => $account->id
        ]);

        $this->mockClickSendObject
            ->shouldReceive('checkSmsStatus')
            ->with($smsMessage->message_id)
            ->andReturn([
                'http_code' => 200,
                'response_code' => 'SUCCESS',
                'data' => [
                    'status_code' => 201,
                    'status_text' => 'Success: Message received on handset'
                ]
            ]);

        $job = new CheckSmsStatus($smsMessage->message_id);
        $job->handle(app(ClickSendApi::class));

        $this->assertDatabaseHas('sms_messages', [
            'message_id' => $smsMessage->message_id,
            'status' => 'SENT'
        ]);
    }
}
