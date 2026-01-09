<?php

namespace App\Http\Controllers;

use Modules\Content\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    /**
     * Simulator Startseite (Feed)
     * URL: /blog?platform=tags.menu
     */
    public function index(Request $request)
    {
        $platform = $request->query('platform', 'tags.world');
        
        // Sprache manuell setzen via URL Parameter ?locale=hu
        if ($request->has('locale')) {
            app()->setLocale($request->input('locale'));
        }

        $query = Post::query();

        if (Auth::check()) {
            // Eingeloggte sehen alles
        } else {
            $query->where('status', 'published');
        }

        $query->whereJsonContains('platforms', $platform);

        $posts = $query->latest('published_at')
                       ->latest('created_at')
                       ->paginate(9);

        return view('blog.index', compact('posts', 'platform'));
    }

    /**
     * Artikel Detailansicht
     * UPDATE: Mit automatischer Spracherkennung anhand des Slugs!
     */
    public function show(Request $request, $slug)
    {
        // 1. Post suchen (Wir suchen in allen Sprachen)
        $post = Post::where('slug->' . app()->getLocale(), $slug)
            ->orWhere('slug->de', $slug)
            ->orWhere('slug->en', $slug)
            ->orWhere('slug->hu', $slug)
            ->orWhere('slug->sk', $slug)
            ->firstOrFail();

        // 2. NEU: SPRACHERKENNUNG
        // Wir prüfen, zu welcher Sprache dieser Slug exakt gehört.
        $detectedLocale = null;
        foreach(['de', 'en', 'hu', 'sk'] as $lang) {
            // getTranslation(..., ..., false) verhindert Fallbacks auf Deutsch
            if ($post->getTranslation('slug', $lang, false) === $slug) {
                $detectedLocale = $lang;
                break;
            }
        }

        // 3. Wenn Sprache erkannt wurde -> App umschalten!
        if ($detectedLocale) {
            app()->setLocale($detectedLocale);
        }

        // (Optional: Manueller Override via ?locale=de URL Parameter gewinnt immer)
        if ($request->has('locale')) {
            app()->setLocale($request->input('locale'));
        }

        // 4. Vorschau-Schutz
        if ($post->status !== 'published' && !Auth::check()) {
            abort(404);
        }

        // 5. Views zählen
        if (!Auth::check()) {
            $post->increment('views');
        }

        return view('blog.show', compact('post'));
    }
}