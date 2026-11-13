<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Deploy check: proves the API is running and can reach its database.
 * Used by the frontend's status page and by Render's health check.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
            $database = 'ok';
        } catch (Throwable $e) {
            // Don't leak connection details; the real error goes to the log.
            report($e);
            $database = 'unreachable';
        }

        $healthy = $database === 'ok';

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'database' => $database,
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
