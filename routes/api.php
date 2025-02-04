<?php

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/accounts', function (Request $request) {
    return Account::with('destinations')
        ->get()
        ->map(fn($account) => [
            'id' => $account->id,
            'email' => $account->email,
            'destinations' => $account->destinations->pluck('phone')->implode(', ')
        ]);
});
