<?php

use App\Http\Controllers\Api\DonationController;
use Illuminate\Support\Facades\Route;

// Public API — no auth needed, these are called by anonymous site visitors. Only the Donate
// flow is wired up so far (a deliberate first slice); the rest of the static frontend
// (programs/league/gallery/news/products, and the contact/enrol/newsletter forms) still
// reads from js/data.js and the old localStorage/Formspree demo pipeline. Add routes here
// as more of the frontend gets connected to this backend.
Route::prefix('donations')->group(function () {
    Route::post('/initialize', [DonationController::class, 'initialize']);
    Route::get('/verify/{reference}', [DonationController::class, 'verify']);
});
