<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MapsController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('listings', [ListingController::class, 'index']);
Route::get('listings/{listing}', [ListingController::class, 'show'])->whereNumber('listing');
Route::post('listings/{listing}/inquiry', [ListingController::class, 'inquiry'])->whereNumber('listing')->middleware('throttle:30,1');

Route::prefix('auth')->group(function () {
    Route::post('signup', [AuthController::class, 'signup'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot', [AuthController::class, 'forgotRequest'])->middleware('throttle:5,1');
    Route::post('forgot/verify', [AuthController::class, 'forgotVerify'])->middleware('throttle:10,1');
    Route::post('forgot/reset', [AuthController::class, 'forgotReset'])->middleware('throttle:10,1');
});

// Signed-in users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me', [AuthController::class, 'updateMe']);
    Route::get('me/listings', [ListingController::class, 'mine']);

    Route::post('listings', [ListingController::class, 'store']);
    Route::put('listings/{listing}', [ListingController::class, 'update'])->whereNumber('listing');
    Route::patch('listings/{listing}/status', [ListingController::class, 'setStatus'])->whereNumber('listing');
    Route::delete('listings/{listing}', [ListingController::class, 'destroy'])->whereNumber('listing');

    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::put('favorites/{listing}', [FavoriteController::class, 'store'])->whereNumber('listing');
    Route::delete('favorites/{listing}', [FavoriteController::class, 'destroy'])->whereNumber('listing');

    Route::post('uploads/image', [UploadController::class, 'image'])->middleware('throttle:60,1');
    Route::post('uploads/video', [UploadController::class, 'video'])->middleware('throttle:10,1');
    Route::post('maps/resolve', [MapsController::class, 'resolve'])->middleware('throttle:30,1');
});
