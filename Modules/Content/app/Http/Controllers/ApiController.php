<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Content\Models\Post;
use Modules\Content\Http\Resources\PostResource;
use Meilisearch\Endpoints\Indexes;

class ApiController extends Controller
{
    public function index(Request $request)
    {
        // 1. Sprache setzen (Fallback auf 'de' oder erste Config-Sprache)
        $locale = $request->get('locale', 'de');
        app()->setLocale($locale);

        $search = $request->get('search'); 
        $tag = $request->get('tag'); 
        $category = $request->get('category'); 

        // 2. MEILISEARCH SUCHE
        $query = Post::search($search ?: '', function (Indexes $meiliIndex, $query, $options) use ($locale) {
            // Facetten für Sidebar anfordern (passend zur Sprache: tags_de, tags_en...)
            $options['facets'] = ['tags_' . $locale, 'category_' . $locale, 'status'];
            return $meiliIndex->search($query, $options);
        });

        $query->where('status', 'published');

        // Dynamische Filter anwenden
        if ($tag) {
            $query->where('tags_' . $locale, $tag);
        }
        
        if ($category) {
            // Filtern nach dem angezeigten Namen (z.B. "Nachrichten")
            $query->where('category_' . $locale, $category);
        }
        
        $query->orderBy('published_at_timestamp', 'desc');

        $results = $query->paginate(12);

        // 3. SIDEBAR DATEN
        $raw = $query->raw();
        $facets = $raw['facetDistribution'] ?? [];

        return PostResource::collection($results)->additional([
            'meta' => [
                'sidebar' => [
                    'tags' => $facets['tags_' . $locale] ?? [], 
                    'categories' => $facets['category_' . $locale] ?? [],
                ]
            ]
        ]);
    }

    public function show(Request $request, $slug)
    {
        $locale = $request->get('locale', 'de');
        app()->setLocale($locale);

        $post = Post::where('status', 'published')
            ->where(function($q) use ($slug) {
                $q->whereJsonContains('slug->de', $slug)
                  ->orWhereJsonContains('slug->en', $slug)
                  ->orWhere('slug', $slug); 
            })->firstOrFail();

        return new PostResource($post);
    }

    // --- INTERAKTIONEN (Mit Spam-Schutz) ---

    public function like(Request $request, $id)
    {
        $key = 'liked_post_' . $id;
        if (!$request->session()->has($key)) {
            Post::where('id', $id)->increment('likes');
            $request->session()->put($key, true);
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'already_liked'], 429);
    }

    // NEU: Rating Funktion (1-5 Sterne)
    public function rate(Request $request, $id)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5']);
        $post = Post::findOrFail($id);
        
        // Durchschnitt neu berechnen
        $newCount = $post->rating_count + 1;
        $newRating = (($post->rating * $post->rating_count) + $request->rating) / $newCount;
        
        $post->update(['rating' => $newRating, 'rating_count' => $newCount]);
        
        return response()->json(['status' => 'success', 'new_rating' => $newRating]);
    }

    public function recordView(Request $request, $id)
    {
        $key = 'viewed_post_' . $id;
        if (!$request->session()->has($key)) {
            Post::where('id', $id)->increment('views');
            $request->session()->put($key, true);
            return response()->json(['status' => 'counted']);
        }
        return response()->json(['status' => 'skipped']);
    }
}