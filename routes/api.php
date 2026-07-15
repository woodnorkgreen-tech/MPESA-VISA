<?php

use App\Http\Controllers\Api\EventStateController;
use App\Http\Controllers\Api\PlayerApiController;
use Illuminate\Support\Facades\Route;

// ── Polled by all clients every 1.5 s ────────────────────────────────────────
Route::get('/state', [EventStateController::class, 'show']);

// ── Player ────────────────────────────────────────────────────────────────────
Route::post('/players', [PlayerApiController::class, 'store'])->middleware('throttle:20,1');
Route::post('/players/lookup', [PlayerApiController::class, 'lookup'])->middleware('throttle:10,1');
Route::post('/answers', [PlayerApiController::class, 'submitAnswer'])->middleware('throttle:60,1');
Route::get('/answers/result', [PlayerApiController::class, 'answerResult'])->middleware('throttle:60,1');
Route::post('/predictions', [PlayerApiController::class, 'submitPrediction'])->middleware('throttle:30,1');
Route::get('/predictions/current', [PlayerApiController::class, 'currentPrediction'])->middleware('throttle:60,1');
Route::get('/leaderboard', [PlayerApiController::class, 'leaderboard']);
