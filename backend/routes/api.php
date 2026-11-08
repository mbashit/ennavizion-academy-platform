<?php

use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

// Public API, called by anonymous site visitors. Nothing here can read submissions or
// change content — that only happens in the signed-in admin panel (/admin).

Route::get('/content', [ContentController::class, 'index'])->middleware('throttle:120,1');

Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('throttle:10,1');

Route::prefix('donations')->group(function () {
    Route::post('/initialize', [DonationController::class, 'initialize'])->middleware('throttle:5,1');
    Route::get('/verify/{reference}', [DonationController::class, 'verify'])
        ->where('reference', '[A-Za-z0-9\-]{6,64}')->middleware('throttle:30,1');
    // Paystack → us. Authenticated by its HMAC signature, not by a session.
    Route::post('/webhook', [DonationController::class, 'webhook'])->middleware('throttle:120,1');
});
