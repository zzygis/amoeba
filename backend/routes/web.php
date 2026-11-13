<?php

use Illuminate\Support\Facades\Route;

// API-only app: the root URL just points visitors to the API.
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'api' => url('/api'),
    'health' => url('/api/health'),
]));
