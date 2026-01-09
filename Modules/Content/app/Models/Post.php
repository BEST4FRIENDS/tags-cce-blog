<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;
use Laravel\Scout\Searchable;
use App\Models\User;

class Post extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, Searchable, HasTags;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'content',
        'status', 'published_at', 'platforms', 'views', 
        'likes', 'rating', 'rating_count', // Wichtig für Interaktionen
        'meta_title', 'meta_description', 'ai_suggestions',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'platforms' => 'array',
        'views' => 'integer',
        'category_id' => 'integer',
        'user_id' => 'integer',
        'likes' => 'integer',
        'rating' => 'decimal:2',
        'ai_suggestions' => 'array',
    ];

    public $translatable = ['title', 'slug', 'content', 'meta_title', 'meta_description'];

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    /**
     * MEILISEARCH INDEXIERUNG (FINAL)
     */
    public function toSearchableArray(): array
    {
        // Performance: Verhindert N+1 Problem beim Import
        $this->loadMissing(['tags', 'category']);

        $array = [
            'id' => (int) $this->id,
            'title' => $this->getTranslations('title'),
            // Tiptap JSON zu reinem Text wandeln für die Volltextsuche
            'content' => collect($this->getTranslations('content'))
                ->map(fn($c) => self::extractTiptapText($c))
                ->toArray(),
            'status' => $this->status,
            'platforms' => $this->platforms ?? [],
            'category_id' => (int) $this->category_id,
            
            // Kategorie-Name für die Anzeige in Suchergebnissen
            'category' => $this->category ? $this->category->getTranslations('name') : [],
            
            // WICHTIG: Timestamp für Sortierung "Neueste zuerst"
            'published_at_timestamp' => $this->published_at?->timestamp ?? 0,
            
            'views' => (int) $this->views,
            'likes' => (int) $this->likes ?? 0,
            'rating' => (float) $this->rating ?? 0.0,
            'created_at' => $this->created_at->timestamp,
            'image' => $this->getFirstMediaUrl('default'),
        ];

        // DYNAMISCHE FELDER FÜR FILTER (tags_de, tags_en...)
        // Wir holen die Sprachen aus der Config oder nutzen Standardwerte
        $locales = config('app.available_locales', ['de', 'en', 'hu', 'sk']);
        
        foreach ($locales as $lang) {
            $array['tags_' . $lang] = $this->tags
                ->map(fn($t) => $t->getTranslation('name', $lang))
                ->filter()->values()->toArray();
            
            // Auch Kategorie für Filter dynamisch ablegen
            $array['category_' . $lang] = $this->category 
                ? $this->category->getTranslation('name', $lang) 
                : null;
        }

        return $array;
    }

    /**
     * Helper: Wandelt Tiptap-JSON in reinen Text (Static für Wiederverwendung)
     */
    public static function extractTiptapText($node): string
    {
        if (is_string($node)) return strip_tags($node);
        if (empty($node)) return '';
        $text = '';

        if (is_array($node)) {
            if (isset($node['text']) && is_string($node['text'])) $text .= $node['text'] . ' ';
            if (isset($node['content']) && is_array($node['content'])) {
                foreach ($node['content'] as $child) $text .= self::extractTiptapText($child);
            }
            // Fallback für unbekannte Strukturen
            if (!isset($node['type']) && !isset($node['text'])) {
                foreach ($node as $val) {
                    if (is_string($val) || is_array($val)) {
                        if(strlen($text) > 20000) break; // Sicherheitsbremse
                        $text .= self::extractTiptapText($val);
                    }
                }
            }
        }
        return trim($text);
    }
}