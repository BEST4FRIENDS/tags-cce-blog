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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Inhalt (Mehrsprachig als JSON gespeichert)
            $table->json('title'); 
            $table->json('slug');
            $table->json('content')->nullable(); // Tiptap JSON Struktur
            $table->json('excerpt')->nullable(); // Kurzbeschreibung für Listenansicht

            // Hub & Spoke Steuerung (Distribution)
            $table->json('platforms')->nullable(); // Array: ['tags.menu', 'best4friends']
            
            // Status & Publishing
            $table->string('status')->default('draft'); // draft, review, published
            $table->timestamp('published_at')->nullable();

            // Autoren-Zuordnung
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            // SEO (Auch mehrsprachig JSON, wichtig für internationale Rankings)
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->string('seo_image')->nullable(); // Pfad zum OG-Image

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};