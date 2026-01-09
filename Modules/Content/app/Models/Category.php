<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasTranslations; 

    protected $fillable = [
        'name', 
        'slug', 
        'color', 
        'icon',         // Falls du Icons in der Migration hast
        'sort_order',   // Für die Reihenfolge im Menü
        'is_active'
    ];

    /**
     * WICHTIG: Mache auch den Slug mehrsprachig für optimales SEO!
     */
    public $translatable = ['name', 'slug']; 

    /**
     * Beziehung: Eine Kategorie hat viele Posts (1-zu-n)
     */
    public function posts(): HasMany
    {
        // Wir geben den vollständigen Klassennamen an, um Fehler zu vermeiden
        return $this->hasMany(Post::class);
    }
}