<?php

namespace Tests\Feature;

use App\Jobs\TranscribeVoicemail;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected $wavFile;
    protected $xwavFile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage:
        fake('local');
        Bus::fake();
        Config::set('services.webhook.signing_key', 'secret_key');

        $this->account = Account::factory()->create(['email' => 'test@example.com']);
        $this->account->destinations()->create(['phone' => '+1234567890']);

        $this->wavFile = UploadedFile::fake()->create('test.wav', 100, 'audio/wav');
        $this->xwavFile = UploadedFile::fake()->create('test2.wav', 100, 'audio/x-wav');
    }

    #[Test]
    public function it_accepts_an_xwav_attachment()
    {
        $timestamp = time();
        $token = 'random_token';
        $signature = hash_hmac('sha256', $timestamp . $token, config('services.webhook.signing_key'));

        $response = $this->withHeaders([
            'Content-Type' => 'multipart/form-data',
        ])->post('/inbound', [
            'to' => 'test@example.com',
            'signature' => $signature,
            'timestamp' => $timestamp,
            'token' => $token,
            'attachment-1' => $this->xwavFile,
        ]);

        $response->assertStatus(200)->assertJson(['message' => 'Webhook processed successfully.']);

        Storage::assertExists('attachments/' . $this->xwavFile->hashName());
        Bus::assertDispatched(TranscribeVoicemail::class, fn($job) => $job->filePath !== null && $job->accountId === $this->account->id);
    }

    #[Test]
    public function it_processes_a_valid_webhook_request()
    {
        $timestamp = time();
        $token = 'random_token';
        $signature = hash_hmac('sha256', $timestamp . $token, config('services.webhook.signing_key'));

        $response = $this->withHeaders([
            'Content-Type' => 'multipart/form-data',
        ])->post('/inbound', [
            'to' => 'test@example.com',
            'signature' => $signature,
            'timestamp' => $timestamp,
            'token' => $token,
            'attachment-1' => $this->wavFile,
        ]);

        $response->assertStatus(200)->assertJson(['message' => 'Webhook processed successfully.']);

        Storage::assertExists('attachments/' . $this->wavFile->hashName());
        Bus::assertDispatched(TranscribeVoicemail::class, fn($job) => $job->filePath !== null && $job->accountId === $this->account->id);
    }
}
