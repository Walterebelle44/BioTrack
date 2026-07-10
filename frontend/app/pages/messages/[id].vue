<template>
  <div class="flex flex-col animate-fade-up" style="height: calc(100vh - 3.5rem - 3rem)">

    <!-- ── Header ──────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3 px-4 py-3 border-b bg-navy-900/80 flex-shrink-0"
      style="border-color:var(--color-border)">
      <NuxtLink to="/messages"
        class="p-1.5 rounded-lg text-navy-400 hover:text-navy-200 hover:bg-navy-800 transition flex-shrink-0">
        <ArrowLeftIcon class="w-4 h-4" />
      </NuxtLink>

      <div v-if="otherUser"
        class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
        :class="roleColor(otherUser.role).bg">
        <span :class="roleColor(otherUser.role).text">{{ otherUser.name.charAt(0) }}</span>
      </div>

      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold text-navy-100">{{ otherUser?.name ?? 'Conversation' }}</div>
        <div class="text-xs text-navy-500">
          {{ roleLabel(otherUser?.role) }}
          <span v-if="otherUser?.service"> · {{ otherUser.service }}</span>
        </div>
      </div>
    </div>

    <!-- ── Zone messages ───────────────────────────────────────────────── -->
    <div ref="messagesEl"
      class="flex-1 overflow-y-auto px-4 py-4 space-y-1"
      style="background: linear-gradient(180deg, var(--navy-950) 0%, rgba(10,22,40,0.6) 100%)">

      <!-- Loading -->
      <div v-if="loading" class="h-full flex items-center justify-center">
        <div class="flex items-center gap-2 text-navy-500 text-sm">
          <span class="w-5 h-5 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
          Chargement de la conversation...
        </div>
      </div>

      <!-- Vide -->
      <div v-else-if="!messages.length"
        class="h-full flex flex-col items-center justify-center gap-2 text-navy-500">
        <ChatBubbleLeftEllipsisIcon class="w-10 h-10 opacity-20" />
        <p class="text-sm">Aucun message — démarrez la conversation !</p>
      </div>

      <!-- Messages -->
      <template v-else>
        <template v-for="(group, date) in groupedMessages" :key="date">
          <!-- Séparateur date -->
          <div class="flex items-center gap-3 py-3">
            <div class="flex-1 h-px bg-navy-800/60"></div>
            <span class="text-[11px] text-navy-600 px-3 py-1 rounded-full bg-navy-900 border"
              style="border-color:var(--color-border)">
              {{ formatGroupDate(date) }}
            </span>
            <div class="flex-1 h-px bg-navy-800/60"></div>
          </div>

          <!-- Bulles -->
          <div v-for="(msg, idx) in group" :key="msg.id"
            class="flex items-end gap-2 mb-1"
            :class="msg.is_mine ? 'flex-row-reverse' : 'flex-row'">

            <!-- Avatar (seulement sur le dernier message du groupe consécutif) -->
            <div v-if="!msg.is_mine"
              class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold mb-0.5"
              :class="[roleColor(otherUser?.role).bg, isLastInSequence(group, idx) ? 'opacity-100' : 'opacity-0']">
              <span :class="roleColor(otherUser?.role).text">{{ otherUser?.name?.charAt(0) }}</span>
            </div>

            <!-- Bulle + heure -->
            <div class="flex flex-col max-w-[72%]" :class="msg.is_mine ? 'items-end' : 'items-start'">
              <div
                class="px-4 py-2.5 text-sm leading-relaxed relative group"
                :class="[
                  msg.is_mine
                    ? 'bg-med-green text-navy-950 rounded-2xl rounded-br-md'
                    : 'bg-navy-800 text-navy-100 rounded-2xl rounded-bl-md',
                  sending && msg.id === 'pending' ? 'opacity-60' : ''
                ]">
                {{ msg.body }}

                <!-- Bouton supprimer (hover, seulement ses messages) -->
                <button v-if="msg.is_mine && msg.id !== 'pending'"
                  @click="deleteMsg(msg)"
                  class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-navy-900 border border-navy-700
                         text-navy-500 hover:text-med-red hover:border-med-red/50
                         hidden group-hover:flex items-center justify-center transition shadow-sm">
                  <XMarkIcon class="w-2.5 h-2.5" />
                </button>
              </div>
              <!-- Heure -->
              <span class="text-[10px] text-navy-600 mt-1 px-1">
                {{ formatHour(msg.created_at) }}
                <span v-if="msg.id === 'pending'" class="ml-1">⏳</span>
              </span>
            </div>
          </div>
        </template>

        <!-- Indicateur "est en train d'écrire" -->
        <div v-if="otherTyping" class="flex items-end gap-2">
          <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold"
            :class="roleColor(otherUser?.role).bg">
            <span :class="roleColor(otherUser?.role).text">{{ otherUser?.name?.charAt(0) }}</span>
          </div>
          <div class="px-4 py-2.5 bg-navy-800 rounded-2xl rounded-bl-md">
            <span class="flex gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-navy-500 animate-bounce" style="animation-delay:0ms"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-navy-500 animate-bounce" style="animation-delay:150ms"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-navy-500 animate-bounce" style="animation-delay:300ms"></span>
            </span>
          </div>
        </div>
      </template>
    </div>

    <!-- ── Zone saisie ─────────────────────────────────────────────────── -->
    <div class="flex-shrink-0 border-t px-4 py-3 bg-navy-900/80" style="border-color:var(--color-border)">
      <div v-if="error" class="mb-2 text-xs text-med-red px-1">{{ error }}</div>
      <div class="flex items-end gap-2">
        <textarea
          v-model="draft"
          ref="inputEl"
          rows="1"
          placeholder="Écrire un message..."
          class="input flex-1 resize-none text-sm py-2.5 leading-relaxed"
          style="min-height:42px; max-height:140px"
          @input="handleInput"
          @keydown.enter.exact.prevent="send"
          @keydown.enter.shift.exact.prevent="draft += '\n'"
        ></textarea>
        <button
          @click="send"
          :disabled="!draft.trim() || sending"
          class="btn-primary h-[42px] px-4 flex-shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
          <PaperAirplaneIcon v-if="!sending" class="w-4 h-4" />
          <span v-else class="w-4 h-4 border-2 border-navy-800 border-t-transparent rounded-full animate-spin"></span>
        </button>
      </div>
      <p class="text-[10px] text-navy-600 mt-1.5 ml-1">Entrée pour envoyer · Maj+Entrée pour saut de ligne</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  ArrowLeftIcon, PaperAirplaneIcon, XMarkIcon,
  ChatBubbleLeftEllipsisIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const route = useRoute()
const api   = useApi()
const auth  = useAuthStore()

const convId = computed(() => Number(route.params.id))

// ── State ────────────────────────────────────────────────────────────────────
const messages    = ref<any[]>([])
const otherUser   = ref<any>(null)
const loading     = ref(true)
const sending     = ref(false)
const error       = ref('')
const draft       = ref('')
const otherTyping = ref(false)
const messagesEl  = ref<HTMLElement | null>(null)
const inputEl     = ref<HTMLTextAreaElement | null>(null)
let typingTimer: ReturnType<typeof setTimeout> | null = null

// ── Helpers ──────────────────────────────────────────────────────────────────
function roleColor(r: string) {
  return ({
    admin:          { bg: 'bg-med-red/20',    text: 'text-med-red' },
    directeur:      { bg: 'bg-med-blue/20',   text: 'text-med-blue' },
    technicien:     { bg: 'bg-med-green/20',  text: 'text-med-green' },
    responsable_si: { bg: 'bg-med-orange/20', text: 'text-med-orange' },
  } as any)[r ?? ''] ?? { bg: 'bg-navy-800', text: 'text-navy-400' }
}
function roleLabel(r: string) {
  return ({
    admin: 'Administrateur', directeur: 'Directeur',
    technicien: 'Technicien', responsable_si: 'Resp. SI',
  } as any)[r ?? ''] ?? (r ?? '')
}
function formatGroupDate(date: string) {
  const d = new Date(date), now = new Date()
  const diff = Math.floor((now.getTime() - d.getTime()) / 86400000)
  if (diff === 0) return "Aujourd'hui"
  if (diff === 1) return 'Hier'
  return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })
}
function formatHour(iso: string) {
  return iso ? new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : ''
}
function isLastInSequence(group: any[], idx: number) {
  return idx === group.length - 1 || group[idx + 1]?.is_mine !== group[idx].is_mine
}

// Grouper par date
const groupedMessages = computed(() => {
  const g: Record<string, any[]> = {}
  for (const m of messages.value) {
    const d = (m.created_at ?? new Date().toISOString()).split('T')[0]
    if (!g[d]) g[d] = []
    g[d].push(m)
  }
  return g
})

// ── Scroll ───────────────────────────────────────────────────────────────────
async function scrollBottom(smooth = false) {
  await nextTick()
  if (messagesEl.value) {
    messagesEl.value.scrollTo({
      top: messagesEl.value.scrollHeight,
      behavior: smooth ? 'smooth' : 'instant',
    })
  }
}

// ── Auto-resize textarea ──────────────────────────────────────────────────────
function handleInput(e: Event) {
  const el = e.target as HTMLTextAreaElement
  el.style.height = 'auto'
  el.style.height = Math.min(el.scrollHeight, 140) + 'px'
}

// ── Load conversation ─────────────────────────────────────────────────────────
async function loadConversation() {
  loading.value = true
  try {
    // Charger en parallèle : détail de la conv + messages
    const [convData, msgData] = await Promise.all([
      api.get<any>(`/conversations/${convId.value}`),
      api.get<any[]>(`/conversations/${convId.value}/messages`),
    ])

    messages.value = Array.isArray(msgData) ? msgData : []

    if (convData?.other_user) {
      otherUser.value = convData.other_user
    } else if (messages.value.length) {
      // Fallback : déduire depuis les messages
      const other = messages.value.find((m: any) => !m.is_mine)
      if (other?.sender) otherUser.value = other.sender
    }

    // Marquer comme lus (fire-and-forget)
    api.post(`/conversations/${convId.value}/read`, {})

    await scrollBottom()
  } catch (e: any) {
    console.error('[Chat] loadConversation:', e?.data?.message ?? e)
    error.value = 'Impossible de charger la conversation.'
  } finally {
    loading.value = false
  }
}

// ── Envoyer un message ────────────────────────────────────────────────────────
async function send() {
  const body = draft.value.trim()
  if (!body || sending.value) return

  error.value  = ''
  sending.value = true
  draft.value  = ''
  if (inputEl.value) inputEl.value.style.height = 'auto'

  // Affichage optimiste
  const pending: any = {
    id: 'pending',
    body,
    is_mine: true,
    created_at: new Date().toISOString(),
    sender: { id: auth.user?.id, name: auth.user?.name, role: auth.user?.role },
    type: 'text',
  }
  messages.value.push(pending)
  await scrollBottom(true)

  try {
    const msg = await api.post<any>(`/conversations/${convId.value}/messages`, {
      body,
      type: 'text',
    })
    // Remplacer le message pending par le vrai
    const idx = messages.value.findIndex(m => m.id === 'pending')
    if (idx !== -1) messages.value.splice(idx, 1, msg)
  } catch (e: any) {
    // Retirer le pending en cas d'erreur
    messages.value = messages.value.filter(m => m.id !== 'pending')
    draft.value    = body
    error.value    = e?.data?.message ?? 'Erreur lors de l\'envoi. Réessayez.'
    console.error('[Chat] send:', e)
  } finally {
    sending.value = false
    inputEl.value?.focus()
  }
}

// ── Supprimer un message ──────────────────────────────────────────────────────
async function deleteMsg(msg: any) {
  try {
    await api.del(`/messages/${msg.id}`)
    messages.value = messages.value.filter(m => m.id !== msg.id)
  } catch (e) { console.error('[Chat] deleteMsg:', e) }
}

// ── WebSocket (Reverb) ────────────────────────────────────────────────────────
const { $echo } = useNuxtApp()

function setupWebSocket() {
  if (!$echo) return

  $echo.private(`conversation.${convId.value}`)
    .listen('.message.sent', (data: any) => {
      // Ne pas ajouter si c'est notre propre message (déjà ajouté en optimiste)
      if (data.sender?.id === auth.user?.id) return
      // Éviter les doublons
      if (messages.value.find(m => m.id === data.id)) return

      messages.value.push({
        ...data,
        is_mine: false,
      })
      scrollBottom(true)

      // Marquer comme lu puisqu'on est sur la page
      api.post(`/conversations/${convId.value}/read`, {})

      // Effacer l'indicateur "typing"
      otherTyping.value = false
      if (typingTimer) clearTimeout(typingTimer)
    })
    .listenForWhisper('typing', () => {
      otherTyping.value = true
      if (typingTimer) clearTimeout(typingTimer)
      typingTimer = setTimeout(() => { otherTyping.value = false }, 2500)
    })
}

// Whisper "typing" quand l'utilisateur écrit
watch(draft, (val) => {
  if (!$echo || !val.trim()) return
  $echo.private(`conversation.${convId.value}`).whisper('typing', {
    user: auth.user?.name,
  })
})

onMounted(async () => {
  await loadConversation()
  setupWebSocket()
  inputEl.value?.focus()
})

onUnmounted(() => {
  if ($echo) {
    $echo.leave(`conversation.${convId.value}`)
  }
  if (typingTimer) clearTimeout(typingTimer)
})
</script>
