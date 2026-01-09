<?php

namespace Modules\Content\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Modules\Content\Models\Post;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale();

        // Helper für Fallback-Sprache
        $getTrans = fn($field) => $this->getTranslation($field, $lang, false) 
            ?: $this->getTranslation($field, config('app.fallback_locale', 'de'), false);

        $contentJson = $getTrans('content'); // Rohes Tiptap JSON
        $title = $getTrans('title');
        $slug = $getTrans('slug') ?? $this->slug;
        
        // 1. Text für Lesezeit extrahieren (nutzt Model-Logik)
        $plainText = Post::extractTiptapText($contentJson);

        // Sharing URL
        $shareUrl = config('app.url') . "/" . $lang . "/post/" . $slug;

        return [
            'id' => $this->id,
            'slug' => $slug,
            'title' => $title,
            
            // UX: Lesezeit basierend auf reinem Text
            'reading_time' => ceil(str_word_count($plainText) / 200) . ' min',
            'excerpt' => Str::limit($plainText, 160),
            'image_url' => $this->getFirstMediaUrl('default'),

            // INTERAKTION (Für Frontend Buttons)
            'stats' => [
                'views' => (int) $this->views,
                'likes' => (int) $this->likes,
                'rating' => (float) $this->rating,
                'rating_count' => (int) $this->rating_count,
            ],

            // SOCIAL SHARING LINKS
            'share_links' => [
                'whatsapp' => 'https://api.whatsapp.com/send?text=' . urlencode($title . ' ' . $shareUrl),
                'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl),
                'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($shareUrl),
                'email'    => 'mailto:?subject=' . urlencode($title) . '&body=' . urlencode($shareUrl),
            ],

            // KLICKBARE KATEGORIEN & TAGS
            'category' => $this->category ? [
                'name' => $this->category->getTranslation('name', $lang),
                'slug' => $this->category->slug,
                'url'  => '/category/' . $this->category->slug
            ] : null,

            'tags' => $this->tags->map(fn($t) => [
                'name' => $t->getTranslation('name', $lang),
                'slug' => $t->slug,
                'url'  => '/tag/' . $t->slug
            ]),

            // UX: Inhaltsverzeichnis aus JSON generieren
            'table_of_contents' => $this->when($request->route()->getActionMethod() === 'show', function() use ($contentJson) {
                return $this->extractHeadingsFromJson($contentJson);
            }),

            'published_at' => $this->published_at?->toIso8601String(),
            'readable_date' => $this->published_at?->format('d.m.Y'),
            'author' => ['name' => $this->author->name ?? 'Redaktion'],
            'platforms' => $this->platforms ?? [],

            // Content als JSON (Frontend rendert es)
            'content' => $this->when($request->route()->getActionMethod() === 'show', $contentJson),
        ];
    }

    /**
     * Sucht H2/H3 Überschriften im Tiptap JSON
     */
    private function extractHeadingsFromJson($node): array
    {
        $headings = [];
        if (is_array($node)) {
            // Ist es eine Headline (Level 2 oder 3)?
            if (isset($node['type']) && $node['type'] === 'heading' && isset($node['attrs']['level'])) {
                if (in_array($node['attrs']['level'], [2, 3])) {
                    $text = Post::extractTiptapText($node);
                    if ($text) {
                        $headings[] = [
                            'id' => Str::slug($text),
                            'level' => $node['attrs']['level'],
                            'text' => $text
                        ];
                    }
                }
            }
            // Rekursion
            if (isset($node['content']) && is_array($node['content'])) {
                foreach ($node['content'] as $child) {
                    $headings = array_merge($headings, $this->extractHeadingsFromJson($child));
                }
            } elseif (!isset($node['type'])) { 
                 foreach ($node as $child) if(is_array($child)) $headings = array_merge($headings, $this->extractHeadingsFromJson($child));
            }
        }
        return $headings;
    }
}