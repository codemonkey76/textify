<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AwsSnsController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json(['message' => 'Received'], Response::HTTP_OK);
    }
}
