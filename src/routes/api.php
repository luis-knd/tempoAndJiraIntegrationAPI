<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('v1:')->group(
    base_path('routes/v1/routes.php')
);


Route::any(
    '{version}/{any}',
    static function ($version, $any) {
        //TODO: if you add any version, please add here too and check the HealthCheckApplicationTest.
        $availableVersions = ['v1'];
        if (!in_array($version, $availableVersions, true)) {
            return redirect("/api/" . max($availableVersions) . "/$any");
        }
        abort(404);
    }
)->where('any', '.*');

Route::fallback(
    static function () {
        return response()->json(['message' => 'Not Found'], 404);
    }
);
