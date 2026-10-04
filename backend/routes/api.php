<?php

use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

// Public API — no auth needed, these are called by anonymous site visitors. The static
// frontend loads all its content from /content (falling back to its bundled js/data.js if
// this API is unreachable) and posts its contact/enrolment/newsletter forms to /submissions.
Route::get('/content', [ContentController::class, 'index']);

Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('throttle:10,1');

Route::prefix('donations')->group(function () {
    Route::post('/initialize', [DonationController::class, 'initialize']);
    Route::get('/verify/{reference}', [DonationController::class, 'verify']);
});
