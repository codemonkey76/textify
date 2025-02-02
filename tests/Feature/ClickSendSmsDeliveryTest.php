<?php

use function Pest\Laravel\postJson;

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

    $response->assertStatus(403);
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

    $response->assertStatus(403);
});


test('accepts clicksend post with valid subaccount_id', function () {
    $payload = [
        'error_code' => '3',
        'error_text' => 'Message sent to the network for delivery',
        'subaccount_id' => $this->expectedId,
        'message_id' => '11111111-1111-1111-1111-111111111111',
        'status' => 'Delivered',
        'status_code' => 200
    ];

    $response = postJson('/delivery', $payload);

    $response->assertStatus(200);
});
