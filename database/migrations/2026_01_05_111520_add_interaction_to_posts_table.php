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
        Schema::table('posts', function (Blueprint $table) {
            // "Like" Button Zähler
            // ->index() sorgt dafür, dass SQL-Abfragen wie "Sortiere nach Likes" blitzschnell sind
            $table->unsignedBigInteger('likes')->default(0)->after('views')->index();
            
            // Durchschnittliche Bewertung (z.B. 4.85)
            // decimal(3,2) erlaubt Werte von 0.00 bis 9.99
            $table->decimal('rating', 3, 2)->default(0.00)->after('likes')->index();
            
            // Anzahl der Bewertungen (wichtig, um den Durchschnitt später neu zu berechnen)
            $table->unsignedBigInteger('rating_count')->default(0)->after('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Entfernt die Spalten (und automatisch auch die Indizes)
            $table->dropColumn(['likes', 'rating', 'rating_count']);
        });
    }
};