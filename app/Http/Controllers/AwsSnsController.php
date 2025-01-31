<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AwsSnsController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info("Receieved SNS Notification", ['request' => $request->all()]);
        return response()->json(['message' => 'Received'], Response::HTTP_OK);
    }
}
