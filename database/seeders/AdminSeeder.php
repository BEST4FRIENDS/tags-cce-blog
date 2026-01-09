<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Der Super-Admin (IT / Owner)
        // WICHTIG: Rolle muss 'super_admin' sein, nicht nur 'admin'
        User::updateOrCreate(
            ['email' => 'info@tags.world'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('nMaster2021TAGSmc'),
                'role' => User::ROLE_SUPER_ADMIN, // Nutzt die Konstante aus User.php
                'is_active' => true,              // WICHTIG: Sofort freischalten
            ]
        );

        // 2. Ein Test-Blogger (Creator)
        User::updateOrCreate(
            ['email' => 'blogger@tags.world'], 
            [
                'name' => 'Blogger Max',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CREATOR,
                'is_active' => true,              // Auch zum Testen freischalten
            ]
        );
        
        // 3. Ein normaler Manager (Admin) - Optional zum Testen
        User::updateOrCreate(
            ['email' => 'manager@tags.world'], 
            [
                'name' => 'Manager Lisa',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
    }
}