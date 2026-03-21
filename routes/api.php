<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Training API endpoints
Route::post('/contacts/{contact}/trainings', [\App\Http\Controllers\Api\TrainingController::class, 'store']);
Route::put('/trainings/{id}', [\App\Http\Controllers\Api\TrainingController::class, 'update']);
Route::delete('/trainings/{id}', [\App\Http\Controllers\Api\TrainingController::class, 'destroy']);
