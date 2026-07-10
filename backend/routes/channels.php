<?php

use Illuminate\Support\Facades\Broadcast;

// Canal public des appareils (dashboard global)
Broadcast::channel('devices', function () {
    return true; // Accessible à tous les utilisateurs authentifiés
});

// Canal public des alertes
Broadcast::channel('alerts', function () {
    return true;
});

// Canal par appareil (données spécifiques)
Broadcast::channel('device.{serialNumber}', function ($user, string $serialNumber) {
    return true; // En prod: vérifier les permissions par service
});

// Canal privé de conversation — vérifier que l'utilisateur est participant
Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});
