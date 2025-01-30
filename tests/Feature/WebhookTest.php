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

    #[Test]
    public function it_processes_a_valid_webhook_request()
    {
        Storage::fake('local');
        Bus::fake();

        $account = Account::factory()->create(['email' => 'test@example.com']);
        $account->destinations()->create(['phone' => '+1234567890']);

        $wavFile = UploadedFile::fake()->create('test.wav', 100, 'audio/wav');

        $timestamp = time();
        $token = 'random_token';
        $apiKey = 'secret_key';
        Config::set('services.webhook.signing_key', $apiKey);
        $signature = hash_hmac('sha256', $timestamp . $token, $apiKey);

        $response = $this->withHeaders([
            'Content-Type' => 'multipart/form-data',
        ])->post('/inbound', [
            'to' => 'test@example.com',
            'signature' => $signature,
            'timestamp' => $timestamp,
            'token' => $token,
            'attachment-1' => $wavFile,
        ]);

        $response->assertStatus(200)->assertJson(['message' => 'Webhook processed successfully.']);

        Storage::assertExists('attachments/' . $wavFile->hashName());
        Bus::assertDispatched(TranscribeVoicemail::class, function ($job) use ($account) {
            return $job->filePath !== null && $job->accountId === $account->id;
        });
    }
}
