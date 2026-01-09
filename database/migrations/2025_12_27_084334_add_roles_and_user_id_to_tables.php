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
        // 1. Wir fügen 'role' und 'is_active' zur Users Tabelle hinzu
        Schema::table('users', function (Blueprint $table) {
            
            // Rolle: Unterscheidet zwischen Super Admin, Admin und Creator
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('creator')->after('email');
            }

            // Status: WICHTIG für den Gatekeeper. 
            // Default 'false' bedeutet: Kein Login möglich ohne Freigabe durch Admin.
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('role');
            }
        });

        // 2. Wir fügen die 'user_id' Spalte zur Posts Tabelle hinzu
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Beim Rollback entfernen wir Rolle und Status
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'role')) $columnsToDrop[] = 'role';
            if (Schema::hasColumn('users', 'is_active')) $columnsToDrop[] = 'is_active';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};