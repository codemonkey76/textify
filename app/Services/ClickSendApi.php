<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickSendApi
{

    public function __construct(protected string $username, protected string $apiKey, protected $baseUrl) {}

    public function sendSms(string $to, string $message)
    {
        $url = $this->baseUrl . '/sms/send';
        $payload = [
            'messages' => [
                [
                    'to' => $to,
                    'source' => 'php',
                    'body' => $message
                ]
            ],
        ];
        Log::channel('clicksend')->info("Sending request to $url with payload", ['payload' => $payload]);

        $response = Http::withBasicAuth($this->username, $this->apiKey)->post($url, $payload);
        Log::channel('clicksend')->info("Got response", ['response' => $response]);

        return [
            'http_code' => $response->status(),
            'body' => $response->json()
        ];
    }


    public function checkSmsStatus(string $messageId)
    {
        $url = $this->baseUrl . "/sms/receipts/$messageId";
        Log::channel('clicksend')->info("Sending request to $url");

        $response = Http::withBasicAuth($this->username, $this->apiKey)->get($url);

        Log::channel('clicksend')->info('Got response', ['response' => $response]);

        return [
            'http_code' => $response->status(),
            'body' => $response->json()
        ];
    }
}
