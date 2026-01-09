<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\BlogController;

Route::middleware('web')->group(function () {
    // Die Übersichtsseite: tags.world/blog
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    
    // Die Detailseite: tags.world/blog/mein-artikel
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
});