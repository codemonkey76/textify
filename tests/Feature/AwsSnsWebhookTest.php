<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\postJson;

test('it accepts SNS requests with a valid signature', function () {
    // Mocking the cert Url and the topic_arn
    $topicArn = 'arn:aws:sns:us-west-2:123456789012:transcription-complete';
    $certUrl = 'https://sns.us-west-2.amazonaws.com/cert.pem';

    Config::set('services.sns.topic_arn', $topicArn);

    Cache::shouldReceive('remember')
        ->withArgs(fn($key, $time, $callback) => str_starts_with($key, 'sns_cert:'))
        ->andReturnUsing(fn() => Http::get($certUrl)->body());

    Http::fake([
        $certUrl => Http::response(
            <<<'EOT'
-----BEGIN CERTIFICATE-----
MIIDDzCCAfegAwIBAgIUejEpru5ftbsl13SIW2LJ+gxbYYQwDQYJKoZIhvcNAQEL
BQAwFzEVMBMGA1UEAwwMVGVzdCBBV1MgU05TMB4XDTI1MDEzMDIzMTI1NFoXDTI2
MDEzMDIzMTI1NFowFzEVMBMGA1UEAwwMVGVzdCBBV1MgU05TMIIBIjANBgkqhkiG
9w0BAQEFAAOCAQ8AMIIBCgKCAQEAk3utFy7E1qVeIyTMNVogCBjhjH7O+ncyaZbQ
77/EJSGas8NKxNUFF+QRh8/TacqOSiXWMujBsD9ooSteE2ijXHl9RqIyOQYoTHSa
VYvAGjmJNXremKV9CkQmXBDlZCfLri7qu0gS5S9ZCHhtBOZ/lAEv3A7KkmGu490C
TXYkTbn0a9EwE0RoOhbEQV5YI8x9EqWxC2d0XJCjhyZq4ePS2903287BTdB1S0zI
58N22Kh0Uf0+yAGN2Tcoih+/MmjdLNackbCuNCxv9x87DJ/vTJmq/C/Ju9t8PsOx
9zpHSqIWj7Ge5FWtC6WY9QCDbyYIYrJQI0qDjjsNXcYq8K/VpQIDAQABo1MwUTAd
BgNVHQ4EFgQUsisrRBt3IvBpVOZGj7uQ24MGVWAwHwYDVR0jBBgwFoAUsisrRBt3
IvBpVOZGj7uQ24MGVWAwDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOC
AQEAGflgo5mnMcC2gJ7G+89ncUsaCy29bg8xLSOjrUSKkYQbLmIAVyLSsZ+SI5vR
fqDfgnDCpkwYmo8Qlag/h+zMzG4yfDcO6qtP9dk/gqd4la5VYqyQySAyKCVT37dT
7xKoQCRzC2n8rXJ3OIQ3a7gen8duqKo1HTlVRdecOC9COHp9ZfC/WeU62c4VcyIi
XMHVomgk7bVdPD5hnLdLpfUsz7Vc4fCHZHByvT317xNEJuSDeUO5rEif9hCUe3Dm
5Ksn2TH0HrhBLr7SXUOGsmxkLsHUEQd8KVHppjHcehtNTRXUZl0d017Oy1kaLYMU
sHFtr496dl/VoMMu0pWHYbHKbw==
-----END CERTIFICATE-----
EOT,
            200
        ),
    ]);

    $stringToSign = implode("\n", [
        "Message",
        json_encode(["transcription_job_status" => "COMPLETED"], JSON_UNESCAPED_SLASHES), // Match AWS JSON encoding
        "MessageId",
        "12345678-1234-5678-1234-567812345678",
        "Timestamp",
        now()->toIso8601String(),
        "TopicArn",
        $topicArn,
        "Type",
        "Notification",
        "", // AWS requires a final newline
    ]);

    $privateKeyPem = <<<EOT
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCTe60XLsTWpV4j
JMw1WiAIGOGMfs76dzJpltDvv8QlIZqzw0rE1QUX5BGHz9Npyo5KJdYy6MGwP2ih
K14TaKNceX1GojI5BihMdJpVi8AaOYk1et6YpX0KRCZcEOVkJ8uuLuq7SBLlL1kI
eG0E5n+UAS/cDsqSYa7j3QJNdiRNufRr0TATRGg6FsRBXlgjzH0SpbELZ3RckKOH
Jmrh49Lb3TfbzsFN0HVLTMjnw3bYqHRR/T7IAY3ZNyiKH78yaN0s1pyRsK40LG/3
HzsMn+9Mmar8L8m723w+w7H3OkdKohaPsZ7kVa0LpZj1AINvJghislAjSoOOOw1d
xirwr9WlAgMBAAECggEAFIji19h+Nbi+u7vy5vbOgPId7Xb2kK8qCMOkAc28nMLq
DF+DuJZEaEgsHLHWKOO4HiDYiywXU9fwVIh6R8+I92Y/CCerQH9X+xU9K+4SaFRc
g2LBPZXFLDnC8Yy0s9ZKqM0Fh+AIKXsHNYO3AjipyMzFgilZETqipD1whaKOeXfV
sQG+BqsvdSyzQfut0Yu7YjYUKRCq0eSX8fZUFwqMorpTUYYAv3/EDIAeBsTDceEH
qBmDrkzhGVqcuwZyV9426MrjISemJdHWMwFGzAHDyXmIF1D0POjFwnEudhJn8Xa4
fQm9oEHjKU7CsipLNb7QmkY+FbDTc3uOTEWCMVR/PQKBgQDDxAo2BsOSiqctdG9M
NMKS3CfEn+va1q99q9b3U+1Yazkz6m72u9d3BOAozPJgxHsn+2ZzCkVWlO0dAm57
y7yMN1iHtCDgOt9m05+LiUhv4A9Mvl3axckRu1Ifp8fqO56Dmm3FpEOxA7tYjvGw
iAQ2HPkr9lzx6Vnr8ELphPmLSwKBgQDA3IlBWkPlaffBQZ2NkZKgvSuEItqRgvpu
Bqzmg0GIrx6rbymxq+itlQBKg0VIgNkQC3tHv6CSGgTNre0YBP+sXHzD3r8KVahh
rx04BQ0oiBTUqkAlKrsBsdkRgPBpzJbyfc0hUrZYhaioyupJNXXdIIbildPPhjcf
6uxOx4QczwJ/Xg7S2SJm1QHJUQ2ga/ztf6JHeTFdIMgFiVwG7M5mOxVJZqg8qE+Y
NpchHHlb+yJsCcnAb4V/yxnC0y1X6CL7dGMjJhBlu1aN/9mtzl0ncJk2wKi2b2aY
NOzLiGoUo3YBszl/hHZoD6S7XtFPToILg/Rnw7ea8KAtlC7b91bsjwKBgQCTK/fF
mjczqxC4NfUf4hWNea3qcJpv7g5ixc9NPJ5WyqPR2MttXKz7QTfupIvLTx/VQZ26
272RoC9IMVA7Qx1ED3PaGHGaVlFe8b2PUTOAWY/j1WOLuTbpjSkDVWygn9IUi/Fs
W2zw0lYpMGdmpFgj/T1RTVpMA7SvM5tOZqwnOwKBgQCtBFzyn9azOfnTFNP3CFvS
/T+wUkyBcaAFbJgK2x006BqKA0bHqCcv3qy/uKXa7lNFPiIaFzodghoxitEnikB1
De/Vjm7iwSbk3WtRKztgElsOqVRStiqoH9u66zTwQ4gid2Q0bbUK9d1d26Bv8sJV
xcw1P075ad/CpCcFzMC/xQ==
-----END PRIVATE KEY-----
EOT;
    $privateKey = openssl_pkey_get_private($privateKeyPem);
    openssl_sign($stringToSign, $validSignature, $privateKey, OPENSSL_ALGO_SHA1);
    $validSignatureBase64 = base64_encode($validSignature);

    $response = postJson('/sns', [
        "Type" => "Notification",
        "MessageId" => "12345678-1234-5678-1234-567812345678",
        "TopicArn" => $topicArn,
        "Message" => json_encode(["transcription_job_status" => "COMPLETED"]),
        "Timestamp" => now()->toIso8601String(),
        "SignatureVersion" => "1",
        "Signature" => $validSignatureBase64,
        "SigningCertURL" => $certUrl, // Use the provided URL
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Received']);
});

test('it rejects SNS requests with an invalid signature', function () {
    // Mocking the cert Url and the topic_arn
    $topicArn = 'arn:aws:sns:us-west-2:123456789012:transcription-complete';
    $certUrl = 'https://sns.us-west-2.amazonaws.com/cert.pem';

    Config::set('services.sns.topic_arn', $topicArn);

    Http::fake([
        $certUrl => Http::response('FAKE_PUBLIC_KEY', 200),
    ]);

    $response = postJson('/sns', [
        "Type" => "Notification",
        "MessageId" => "12345678-1234-5678-1234-567812345678",
        "TopicArn" => $topicArn,
        "Message" => json_encode(["transcription_job_status" => "COMPLETED"]),
        "Timestamp" => now()->toIso8601String(),
        "SignatureVersion" => "1",
        "Signature" => base64_encode("fake_signature"),
        "SigningCertURL" => $certUrl, // Use the provided URL
    ]);

    $response->assertStatus(403)
        ->assertJson(['error' => 'Invalid AWS SNS Signature']);
});
