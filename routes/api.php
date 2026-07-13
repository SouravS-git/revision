<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\RegisteredUserController;
use App\Http\Controllers\Api\VerifyEmailController;
use App\Http\Controllers\ArticleController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', RegisteredUserController::class);
Route::post('login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');
    Route::delete('logoutAll', [LogoutController::class, 'logoutAll'])->middleware('auth:sanctum');
});

Route::apiResource('articles', ArticleController::class)->middleware(['auth:sanctum', 'throttle:api']);
