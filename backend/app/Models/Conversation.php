<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = ['type', 'name'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Relation lastMessage — retourne une collection avec un seul élément (le plus récent).
     * Utilisé avec ->with('lastMessage') puis ->first() dans le Controller.
     */
    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /** Trouver ou créer une conversation directe entre 2 utilisateurs */
    public static function findOrCreateDirect(int $userA, int $userB): self
    {
        // Chercher une conversation directe existante entre ces 2 users
        $conv = self::where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $userA))
            ->whereHas('participants', fn($q) => $q->where('user_id', $userB))
            ->first();

        if (!$conv) {
            $conv = self::create(['type' => 'direct']);
            $conv->participants()->attach([$userA, $userB]);
        }

        return $conv;
    }

    /** Nombre de messages non lus pour un utilisateur donné */
    public function unreadCount(int $userId): int
    {
        $participant = $this->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant) return 0;

        $lastRead = $participant->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }
}
