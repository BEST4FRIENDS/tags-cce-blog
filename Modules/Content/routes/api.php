<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\ApiController; 

/*
|--------------------------------------------------------------------------
| API Routes (Modul: Content)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function() {
    
    // 1. ÖFFENTLICHE DATEN (Lesen)
    // Schnell & Statelos (für Suche & Anzeige)
    Route::middleware('api')->group(function() {
        Route::get('/posts', [ApiController::class, 'index']);
        Route::get('/posts/{slug}', [ApiController::class, 'show']);
    });

    // 2. INTERAKTIONEN (Schreiben)
    // WICHTIG: Wir nutzen 'web', damit die Session für den Spam-Schutz funktioniert.
    // Das Frontend sendet den CSRF-Token mit, daher ist das sicher.
    Route::middleware('web')->group(function() {
        Route::post('/posts/{id}/like', [ApiController::class, 'like']);
        Route::post('/posts/{id}/rate', [ApiController::class, 'rate']);
        Route::post('/posts/{id}/view', [ApiController::class, 'recordView']);
    });

});