<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;



class VerifyAwsSnsSignature
{
    private const SIGNING_CERT_KEY = 'SigningCertURL';
    private const SIGNATURE_KEY = 'Signature';
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $message = $request->json()->all();

        // Ensure the required fields are present
        if (!isset($message['SignatureVersion'], $message[self::SIGNATURE_KEY], $message[self::SIGNING_CERT_KEY])) {
            Log::error('AWS SNS Validation Failed: Missing required fields', $message);
            return response()->json(['error' => 'Invalid AWS SNS message format'], Response::HTTP_BAD_REQUEST);
        }

        // Validate the SigningCertURL is from AWS
        if (!preg_match('/^https:\/\/sns\.[a-zA-Z0-9-]+\.amazonaws\.com\/.*\.pem$/', $message[self::SIGNING_CERT_KEY])) {
            Log::error('AWS SNS Validation Failed: Invalid SigningCertURL', ['url' => $message[self::SIGNING_CERT_KEY]]);
            return response()->json(['error' => 'Invalid SigningCertURL'], Response::HTTP_FORBIDDEN);
        }

        // Cache the AWS certificate for 24 hours
        $certContents = Cache::remember("sns_cert:" . md5($message[self::SIGNING_CERT_KEY]), now()->addHours(24), function () use ($message) {
            try {
                return Http::get($message[self::SIGNING_CERT_KEY])->body();
            } catch (\Exception $e) {
                Log::error('AWS SNS Validation failed: Unable to fetch AWS signing certificate', ['error' => $e->getMessage()]);
                return null;
            }
        });

        if (!$certContents) {
            return response()->json(['error' => 'Unable to fetch AWS signing certificate'], Response::HTTP_FORBIDDEN);
        }

        // Construct the string to sign
        $stringToSign = $this->buildStringToSign($message);

        // Verify the signature
        Log::info('AWS SNS Certificate Contents:', ['cert' => $certContents]);

        $publicKey = openssl_pkey_get_public($certContents);
        $decodedSignature = base64_decode($message[self::SIGNATURE_KEY]);

        Log::info('AWS SNS Debug Info', [
            'StringToSign' => bin2hex($stringToSign),
            'Expected StringToSign' => $this->buildStringToSign($message),
            'SignatureBase64' => $message[self::SIGNATURE_KEY],
            'DecodedSignature' => bin2hex($decodedSignature),
            'PublicKey' => $publicKey ? 'Loaded' : 'Failed to Load',
        ]);

        if (!$publicKey || openssl_verify($stringToSign, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA1) !== 1) {
            Log::error('AWS SNS Validation Failed: Invalid Signature');
            return response()->json(['error' => 'Invalid AWS SNS Signature'], Response::HTTP_FORBIDDEN);
        }

        // Save the certificate temporarily
        return $next($request);
    }

    private function buildStringToSign(array $message): string
    {
        $fields = [
            "Message",
            json_encode(json_decode($message["Message"], true), JSON_UNESCAPED_SLASHES), // Ensure AWS-compliant JSON encoding
            "MessageId",
            $message["MessageId"],
            "Timestamp",
            $message["Timestamp"],
            "TopicArn",
            $message["TopicArn"],
            "Type",
            $message["Type"],
        ];

        if (isset($message["Subject"])) {
            array_splice($fields, 2, 0, ["Subject", $message["Subject"]]); // AWS places Subject before MessageId
        }

        return implode("\n", $fields) . "\n"; // AWS requires a final newline
    }
}
