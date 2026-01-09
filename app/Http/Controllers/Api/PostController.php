<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Content\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // 1. Liste aller Posts für die Apps (z.B. tags.menu)
    public function index(Request $request)
    {
        $platform = $request->query('platform'); // z.B. ?platform=tags.menu
        $limit = $request->query('limit', 10);

        $posts = Post::query()
            ->where('status', 'published') // Nur veröffentlichte!
            ->when($platform, function ($query, $platform) {
                // Filtert das JSON-Array 'platforms'
                return $query->whereJsonContains('platforms', $platform);
            })
            ->with(['media', 'author:id,name']) // Bilder und Autor laden
            ->latest('published_at')
            ->paginate($limit);

        return response()->json($posts);
    }

    // 2. Einzelseite + Zähler
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with(['media', 'author:id,name'])
            ->firstOrFail();

        return response()->json($post);
    }

    // 3. Der Zähler-Endpunkt (wird vom Frontend aufgerufen)
    public function recordView($id)
    {
        // Wir nutzen find(), damit wir keinen 404 Fehler werfen, falls der Post gelöscht ist
        $post = Post::find($id);

        if ($post) {
            // Zählt die 'views' Spalte hoch
            $post->increment('views');
        }

        return response()->json(['status' => 'ok']);
    }
}