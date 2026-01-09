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
            // Wir prüfen, ob die Spalte existiert, um Fehler bei Re-Runs zu vermeiden
            if (!Schema::hasColumn('posts', 'views')) {
                $table->unsignedBigInteger('views')->default(0)->after('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'views')) {
                $table->dropColumn('views');
            }
        });
    }
};