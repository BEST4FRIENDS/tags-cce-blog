<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // Mehrsprachige Felder (Spatie Translatable)
            $table->json('name'); // {"de": "Recht", "hu": "Jog", "sk": "Právo"}
            $table->json('slug'); // {"de": "recht", "hu": "jog", "sk": "pravo"}
            
            // Design & Struktur
            $table->string('color')->nullable();      // Für Filament Badges (z.B. 'primary', 'success', '#ff0000')
            $table->string('icon')->nullable();       // Heroicon-Name für das Frontend/Menü
            $table->integer('sort_order')->default(0); // Um Kategorien im Menü zu ordnen
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        // Wir brauchen eine Verknüpfungstabelle zwischen Posts und Categories (Many-to-Many)
        Schema::create('category_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('categories');
    }
};