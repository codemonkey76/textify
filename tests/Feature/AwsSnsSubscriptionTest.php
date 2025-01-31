<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\postJson;

test('it automatically confirms SNS subscription requests', function () {
    $subscribeUrl = 'https://sns-ap-southeast-2.amazonaws.com/subscribe';
    $topicArn = 'arn:aws:sns:us-west-2:123456789012:transcription-complete';

    Config::set('services.sns.topic_arn', $topicArn);

    Http::fake([
        $subscribeUrl => Http::response('OK', 200),
    ]);

    $response = postJson('/sns', [
        'Type' => 'SubscriptionConfirmation',
        'MessageId' => '9d072d15-212f-48f1-bb5c-435d2c5ee488',
        'TopicArn' => $topicArn,
        'SubscribeURL' => $subscribeUrl,
        'Timestamp' => now()->toIso8601String(),
    ]);

    $response->assertStatus(200)->assertJson(['message' => 'Subscription confirmed']);

    Http::assertSent(fn($request) => $request->url() === $subscribeUrl);
});
