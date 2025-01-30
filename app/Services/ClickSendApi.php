<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

        $response = Http::withBasicAuth($this->username, $this->apiKey)->post($url, $payload);

        return [
            'http_code' => $response->status(),
            'body' => $response->json()
        ];
    }


    public function checkSmsStatus(string $messageId)
    {
        $url = $this->baseUrl . "/sms/receipts/$messageId";

        $response = Http::withBasicAuth($this->username, $this->apiKey)->get($url);

        return [
            'http_code' => $response->status(),
            'body' => $response->json()
        ];
    }
}
