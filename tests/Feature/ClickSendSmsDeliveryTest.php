<?php

use App\Enums\MessageStatus;
use App\Models\SmsMessage;
use App\Services\ClickSendApi;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->expectedId = 'expected-id';
    config(['services.clicksend.subaccount_id' => $this->expectedId]);
});

test('reject clicksend post if subaccount_id is missing', function () {

    $payload = [
        'error_code' => '3',
        'error_text' => 'Message sent to the network for delivery',
        // 'subaccount_id' is intentionally missing
        'message_id' => '11111111-1111-1111-1111-111111111111',
        'status' => 'Delivered',
        'status_code' => 200
    ];

    $response = postJson('/delivery', $payload);

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('reject clicksend post with incorrect subaccount_id', function () {
    $payload = [
        'error_code' => '3',
        'error_text' => 'Message sent to the network for delivery',
        'subaccount_id' => 'wrong-id',
        'message_id' => '11111111-1111-1111-1111-111111111111',
        'status' => 'Delivered',
        'status_code' => 200
    ];

    $response = postJson('/delivery', $payload);

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});


test('accepts clicksend post with valid subaccount_id', function () {
    $message = SmsMessage::factory()->create();

    $clickSendApiMock = Mockery::mock(ClickSendApi::class);
    $clickSendApiMock->shouldReceive('checkSmsStatus')
        ->with($message->message_id)
        ->andReturn([
            'http_code' => 200,
            'data' => [
                'status_code' => 201
            ]
        ]);

    $this->app->instance(ClickSendApi::class, $clickSendApiMock);
    $payload = [
        'error_code' => '3',
        'error_text' => 'Message sent to the network for delivery',
        'subaccount_id' => $this->expectedId,
        'messageid' => $message->message_id,
        'status' => 'Delivered',
        'status_code' => 200
    ];

    $response = postJson('/delivery', $payload);

    $response->assertStatus(Response::HTTP_OK);

    $message->refresh();
    expect($message->status)->toBe(MessageStatus::Delivered);
});


test('it fails if messageid is missing', function () {
    $payload = [
        // 'messageid' intentionally missing
        'subaccount_id' => $this->expectedId,
        'status' => 'Delivered'
    ];

    $response = postJson('/delivery', $payload);

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});


test('it fails if messageid is invalid', function () {
    $payload = [
        'messageid' => 'NON_EXISTENT-MESSAGE-ID',
        'subaccount_id' => $this->expectedId,
        'status' => 'Delivered'
    ];

    $response = postJson('/delivery', $payload);

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('it returns error if ClickSendAPI fails', function () {
    SmsMessage::factory()->create([
        'message_id' => 'TEST_MESSAGE_ID',
        'status' => MessageStatus::Pending
    ]);

    $clickSendApiMock = Mockery::mock(ClickSendApi::class);
    $clickSendApiMock->shouldReceive('checkSmsStatus')
        ->with('TEST_MESSAGE_ID')
        ->andReturn([
            'http_code' => 500,
            'data' => null
        ]);

    $this->app->instance(ClickSendApi::class, $clickSendApiMock);

    $payload = [
        'messageid' => 'TEST_MESSAGE_ID',
        'subaccount_id' => $this->expectedId,
        'status' => 'Delivered',
    ];

    $response = postJson('/delivery', $payload);
    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
});
