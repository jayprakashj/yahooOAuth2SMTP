<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YahooAuthController;
use App\Http\Controllers\MailController;

Route::get('/', function () {
    return view('welcome');
});

// OAuth routes
Route::get('/connect-yahoo', [YahooAuthController::class, 'connect'])->name('yahoo.redirect');
Route::get('/get-yahoo-token', [YahooAuthController::class, 'getToken'])->name('yahoo.callback');
Route::get('/refresh-yahoo-token', [YahooAuthController::class, 'refreshToken'])->name('yahoo.refresh');
Route::get('/disconnect-yahoo', function () {
    // Clear from database
    \App\Models\OAuthDetails::truncate();
    
    // Clear from session
    session()->forget(['yahoo_email', 'yahoo_access_token', 'yahoo_refresh_token', 'yahoo_token_expires_at']);
    
    return redirect('/')->with('status', 'Yahoo disconnected successfully.');
});

Route::get('/send-test', [MailController::class, 'sendTest']);

// Debug route to test callback
Route::get('/debug-callback', function (Illuminate\Http\Request $request) {
    return response()->json([
        'message' => 'Callback received successfully',
        'all_params' => $request->all(),
        'query_string' => $request->getQueryString(),
        'url' => $request->fullUrl(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
    ]);
});