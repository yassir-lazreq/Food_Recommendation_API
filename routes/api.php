<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AiRecommendationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\PlateController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories/{category}/plates', [CategoryController::class, 'plates']);

    Route::get('/plates', [PlateController::class, 'index']);
    Route::get('/plates/{plate}', [PlateController::class, 'show']);
    Route::post('/ai/recommendations', [AiRecommendationController::class, 'recommend']);

    Route::middleware('admin')->group(function (): void {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::post('/plates', [PlateController::class, 'store']);
        Route::put('/plates/{plate}', [PlateController::class, 'update']);
        Route::delete('/plates/{plate}', [PlateController::class, 'destroy']);

        Route::get('/ingredients', [IngredientController::class, 'index']);
        Route::post('/ingredients', [IngredientController::class, 'store']);
        Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update']);
        Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy']);
    });
});
