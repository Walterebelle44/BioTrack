<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    /** GET /conversations — liste toutes les conversations de l'utilisateur */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with([
                'participants:id,name,role',
                'lastMessage.sender:id,name',
            ])
            ->get()
            ->map(function (Conversation $conv) use ($user) {
                // lastMessage est une HasMany avec limit(1) — utiliser ->first() sur la collection
                $lastMsg = $conv->lastMessage->first();
                $others  = $conv->participants->where('id', '!=', $user->id)->values();

                return [
                    'id'           => $conv->id,
                    'type'         => $conv->type,
                    'name'         => $conv->name ?? ($others->first()?->name ?? 'Inconnu'),
                    'participants' => $conv->participants->map(fn($p) => [
                        'id'   => $p->id,
                        'name' => $p->name,
                        'role' => $p->role,
                    ]),
                    'other_user'   => $others->first() ? [
                        'id'   => $others->first()->id,
                        'name' => $others->first()->name,
                        'role' => $others->first()->role,
                    ] : null,
                    'last_message' => $lastMsg ? [
                        'body'       => $lastMsg->body,
                        'created_at' => $lastMsg->created_at->toISOString(),
                        'sender'     => $lastMsg->sender?->name,
                        'is_mine'    => $lastMsg->sender_id === $user->id,
                    ] : null,
                    'unread_count' => $conv->unreadCount($user->id),
                    'updated_at'   => $conv->updated_at->toISOString(),
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        $totalUnread = $conversations->sum('unread_count');

        return response()->json([
            'conversations' => $conversations,
            'total_unread'  => $totalUnread,
        ]);
    }

    /** POST /conversations — démarrer ou récupérer une conversation directe */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|different:' . $request->user()->id,
        ]);

        $conv = Conversation::findOrCreateDirect(
            $request->user()->id,
            (int) $request->user_id
        );

        $conv->load('participants:id,name,role');
        $other = $conv->participants->where('id', '!=', $request->user()->id)->first();

        return response()->json([
            'id'           => $conv->id,
            'type'         => $conv->type,
            'name'         => $other?->name ?? 'Inconnu',
            'other_user'   => $other ? ['id' => $other->id, 'name' => $other->name, 'role' => $other->role] : null,
            'participants' => $conv->participants->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'role' => $p->role]),
            'unread_count' => 0,
        ], 201);
    }

    /** GET /conversations/{id} — détail d'une conversation */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();

        $conv = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with('participants:id,name,role,service')
            ->findOrFail($conversationId);

        $other = $conv->participants->where('id', '!=', $user->id)->first();

        return response()->json([
            'id'           => $conv->id,
            'type'         => $conv->type,
            'name'         => $conv->name ?? ($other?->name ?? 'Inconnu'),
            'other_user'   => $other ? [
                'id'      => $other->id,
                'name'    => $other->name,
                'role'    => $other->role,
                'service' => $other->service ?? null,
            ] : null,
            'participants' => $conv->participants->map(fn($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'role' => $p->role,
            ]),
            'unread_count' => $conv->unreadCount($user->id),
            'updated_at'   => $conv->updated_at->toISOString(),
        ]);
    }

    /** GET /conversations/{id}/messages — messages d'une conversation */
    public function messages(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est participant
        $conv = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->findOrFail($conversationId);

        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender:id,name,role')
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $user->id));

        // Marquer comme lus
        $this->markAsRead($conv, $user->id);

        return response()->json($messages);
    }

    /** POST /conversations/{id}/messages — envoyer un message */
    public function send(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'body'     => 'required|string|max:2000',
            'type'     => 'nullable|in:text,alert_ref,maintenance_ref',
            'metadata' => 'nullable|array',
        ]);

        // Vérifier participation
        $conv = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->findOrFail($conversationId);

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $user->id,
            'body'            => $request->body,
            'type'            => $request->type ?? 'text',
            'metadata'        => $request->metadata,
        ]);

        // Mettre à jour le timestamp de la conversation
        $conv->touch();

        // Broadcast via Reverb
        broadcast(new MessageSent($message))->toOthers();

        $message->load('sender:id,name,role');

        return response()->json($this->formatMessage($message, $user->id), 201);
    }

    /** POST /conversations/{id}/read — marquer comme lu */
    public function markRead(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->findOrFail($conversationId);
        $this->markAsRead($conv, $user->id);
        return response()->json(['message' => 'Messages marqués comme lus.']);
    }

    /** DELETE /messages/{id} — supprimer un message (soft delete) */
    public function deleteMessage(Request $request, Message $message): JsonResponse
    {
        if ($message->sender_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        $message->delete();
        return response()->json(['message' => 'Message supprimé.']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function formatMessage(Message $m, int $currentUserId): array
    {
        return [
            'id'              => $m->id,
            'conversation_id' => $m->conversation_id,
            'body'            => $m->body,
            'type'            => $m->type,
            'metadata'        => $m->metadata,
            'is_mine'         => $m->sender_id === $currentUserId,
            'created_at'      => $m->created_at->toISOString(),
            'sender' => [
                'id'   => $m->sender?->id,
                'name' => $m->sender?->name,
                'role' => $m->sender?->role,
            ],
        ];
    }

    private function markAsRead(Conversation $conv, int $userId): void
    {
        $conv->participants()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);
    }
}
