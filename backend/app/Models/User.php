<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'service', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ── Relations existantes ────────────────────────────────────────────────
    public function acknowledgedAlerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'acknowledged_by');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'performed_by');
    }

    // ── Relations messagerie ────────────────────────────────────────────────
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    public function isAdmin(): bool       { return $this->role === 'admin'; }
    public function isTechnicien(): bool  { return $this->role === 'technicien'; }
    public function isDirecteur(): bool   { return $this->role === 'directeur'; }

    public function getRoleLabel(): string
    {
        return match($this->role) {
            'admin'          => 'Administrateur',
            'directeur'      => 'Directeur de Clinique',
            'responsable_si' => 'Responsable SI',
            'technicien'     => 'Technicien de Maintenance',
            default          => $this->role,
        };
    }
}
