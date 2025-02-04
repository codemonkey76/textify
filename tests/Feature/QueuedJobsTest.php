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
    public function it_starts_transcription_and_queues_check_status()
    {
        $this->mockTranscribeObject
            ->shouldReceive('startTranscriptionJob')->once()
            ->with(\Mockery::on(fn($args) => isset($args['TranscriptionJobName'])))
            ->andReturn([
                'TranscriptionJob' => [
                    'TranscriptionJobStatus' => 'IN_PROGRESS'
                ]
            ]);

        $job = new TranscribeVoicemail($this->filePath, $this->accountId);
        $job->handle(app(TranscribeServiceClient::class));
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
            ->once()
            ->with('+1234567890', 'Test message')
            ->andReturn([
                'http_code' => 200,
                'body' => [
                    'data' => [
                        'messages' => [
                            [
                                'message_id' => $msgId,
                                'message_parts' => 1,
                                'message_price' => 0.07
                            ]

                        ]
                    ]
                ]
            ]);

        $job = new NotifyDestination($account->id, '+1234567890', 'Test message');
        $job->handle(app(ClickSendApi::class));
    }

    #[Test]
    public function it_marks_sms_as_failed_when_not_delivered()
    {

        $msgId = 'msg123';
        $account = Account::create(['email' => 'test@example.com']);
        $errMsg = 'Rejected by the recipient network.';

        $smsMessage = SmsMessage::create([
            'message' => 'Test message',
            'phone_number' => '+1234567890',
            'message_id' => $msgId,
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
                    'status_code' => 301,
                    'status_text' => 'Failed: Message could not be delivered',
                    'error_code' => 15,
                    'error_text' => $errMsg
                ]
            ]);

        $job = new CheckSmsStatus($smsMessage->message_id);
        $job->handle(app(ClickSendApi::class));

        $this->assertDatabaseHas('sms_messages', [
            'message_id' => $smsMessage->message_id,
            'status' => 'FAILED',
            'error_code' => 15,
            'error_message' => $errMsg
        ]);
    }

    #[Test]
    public function it_re_queues_a_message_that_is_still_pending()
    {
        $account = Account::create(['email' => 'test@example.com']);
        $msgId = 'msg123';

        $smsMessage = SmsMessage::create([
            'message' => 'Test message',
            'phone_number' => '+1234567890',
            'message_id' => $msgId,
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
                    'status_code' => 200,
                    'status_text' => 'Message is queued for delivery'
                ]
            ]);

        $job = new CheckSmsStatus($smsMessage->message_id);
        $job->handle(app(ClickSendApi::class));

        Bus::assertDispatched(CheckSmsStatus::class, fn($job) => $job->messageId === $msgId);

        // Assert: SMSMessage should NOT be updated
        $this->assertDatabaseHas('sms_messages', [
            'message_id' => 'msg123',
            'status' => 'PENDING' // ✅ Should remain unchanged
        ]);
    }
}
