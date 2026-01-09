<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    // --- KONSTANTEN FÜR ROLLEN (Vermeidet Tippfehler) ---
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_CREATOR = 'creator';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',      // Rolle: super_admin, admin, creator
        'is_active', // Gatekeeper: Muss true sein, damit Login geht
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean', // Wichtig: Cast als Boolean
        ];
    }

    // --- HILFSMETHODEN ---

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // --- FILAMENT ACCESS CONTROL (DER TÜRSTEHER) ---
    
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. GOD MODE: Super Admin darf immer und überall rein
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2. SICHERHEITSSCHLOSS: Wenn User nicht aktiv ist -> Rauswurf
        // (Gilt für Admin und Creator gleichermaßen)
        if (! $this->is_active) {
            return false;
        }

        // 3. REGELN FÜR DAS ADMIN-PANEL (Missionszentrale)
        if ($panel->getId() === 'admin') {
            // Nur Admins dürfen hier rein (SAs sind oben schon durch)
            return $this->isAdmin();
        }

        // 4. REGELN FÜR DAS CREATOR-PANEL (Studio)
        if ($panel->getId() === 'creator') {
            // Jeder, der aktiv ist (auch Admins), darf ins Creator Studio
            return true;
        }

        return false;
    }
}