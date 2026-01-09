<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('locale')) {
            app()->setLocale($request->input('locale'));
        }

        $posts = Post::where('status', 'published')
                     ->orderBy('published_at', 'desc')
                     ->get();

        return view('content::index', compact('posts'));
    }

    public function show(Request $request, $slug)
    {
        // 1. HARDCORE SUCHE: Wir suchen den Slug explizit in allen Sprachen.
        // Das verhindert 404-Fehler, wenn die JSON-Suche spinnt.
        $post = Post::where('status', 'published')
            ->where(function ($query) use ($slug) {
                $query->where('slug->de', $slug)
                      ->orWhere('slug->en', $slug)
                      ->orWhere('slug->hu', $slug)
                      ->orWhere('slug->sk', $slug);
            })
            ->firstOrFail(); // Wenn hier nichts kommt, gibt es den Artikel wirklich nicht.

        // 2. Automatische Spracherkennung für den Inhalt
        $currentLocale = $request->get('locale', app()->getLocale());
        
        // Prüfen ob Titel in der aktuellen Sprache existiert
        $titleTranslation = $post->getTranslation('title', $currentLocale, false);
        
        if (empty($titleTranslation)) {
            // Wenn leer, nimm die Sprache, in der der Slug gefunden wurde oder die erste verfügbare
            $availableLocales = array_keys($post->getTranslations('title'));
            if (!empty($availableLocales)) {
                app()->setLocale($availableLocales[0]);
            }
        } else {
            app()->setLocale($currentLocale);
        }

        return view('content::show', compact('post'));
    }
}