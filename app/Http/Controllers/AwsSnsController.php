<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AwsSnsController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info("Receieved SNS Notification", ['raw_body' => $request->getContent(), 'headers' => $request->headers->all()]);
        return response()->json(['message' => 'Received'], Response::HTTP_OK);
    }
}
