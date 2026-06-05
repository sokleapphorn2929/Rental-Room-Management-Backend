<?php

use App\Http\Controllers\Api\GoogleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
    // return view('testing');
});

// Route::prefix('auth')->group(function () {
//     Route::get('google/url', [GoogleController::class, 'getGoogleUrl']);
//     Route::post('google/callback', [GoogleController::class, 'handleGoogleCallback']);
// });