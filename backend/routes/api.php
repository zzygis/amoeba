<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /api (see bootstrap/app.php).

Route::get('/health', HealthController::class);
