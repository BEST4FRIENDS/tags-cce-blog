<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController; // <--- Import oben definiert

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// SIMULATOR ROUTEN für cce.tags.world
// Die Middleware 'web' ist in routes/web.php zwar meist Standard, 
// schadet hier aber nicht, um sicherzugehen, dass Sessions/CSRF funktionieren.
Route::middleware(['web'])->group(function () {
    
    // Feed Simulation (z.B. cce.tags.world/blog?platform=tags.menu)
    // Überschreibt den Controller aus dem Modul durch deinen App-Controller
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    
    // Detailansicht
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    // VIP Route für Creator (Leitet auf den Simulator, aber explizit)
    Route::get('/creator/preview', [BlogController::class, 'index'])
        ->middleware('auth') // Nur eingeloggte User
        ->name('creator.preview');
});