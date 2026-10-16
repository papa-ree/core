<?php

namespace Bale\Core\Controllers;

use Bale\Core\Models\WpMigrationRun;
use Bale\Core\Services\WpMigrationRunService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * WpMigrationRunStatusController
 *
 * Lightweight JSON endpoint polled by Alpine (no Livewire round-trip) while a
 * WordPress migration batch runs in the background.
 */
class WpMigrationRunStatusController
{
    public function show(string $run, WpMigrationRunService $runs): JsonResponse
    {
        if (! auth()->check()) {
            abort(403);
        }

        $model = WpMigrationRun::find($run);

        if (! $model) {
            throw new NotFoundHttpException('Migration run not found.');
        }

        return response()->json($runs->progress($model));
    }
}
