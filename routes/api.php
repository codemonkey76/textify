<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return response()->json(['message' => 'Login successful']);
    }

    return response()->json(['error' => 'Unauthorized'], 401);
});


Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    return response()->json(['message' => 'Logged out']);
});
