<template>
  <div class="animate-fade-up space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-lg font-bold text-navy-100">Messagerie</h1>
      <button @click="showPicker = true" class="btn-primary text-xs">
        <PencilSquareIcon class="w-4 h-4" />
        Nouveau message
      </button>
    </div>

    <!-- Liste conversations -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="py-16 flex items-center justify-center gap-2 text-navy-500 text-sm">
        <span class="w-4 h-4 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
        Chargement...
      </div>

      <div v-else-if="!conversations.length"
        class="py-20 flex flex-col items-center justify-center gap-3 text-navy-500">
        <ChatBubbleLeftRightIcon class="w-12 h-12 opacity-20" />
        <p class="text-sm">Aucune conversation</p>
        <button @click="showPicker = true" class="btn-primary text-xs">
          <PlusIcon class="w-3.5 h-3.5" /> Démarrer une conversation
        </button>
      </div>

      <div v-else>
        <NuxtLink
          v-for="conv in conversations"
          :key="conv.id"
          :to="`/messages/${conv.id}`"
          class="flex items-center gap-4 px-5 py-4 border-b hover:bg-navy-800/30 transition"
          style="border-color:var(--color-border)">
          <!-- Avatar -->
          <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold"
            :class="roleColor(conv.other_user?.role).bg">
            <span :class="roleColor(conv.other_user?.role).text">
              {{ (conv.name || '?').charAt(0).toUpperCase() }}
            </span>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-0.5">
              <span class="text-sm font-semibold" :class="conv.unread_count > 0 ? 'text-navy-50' : 'text-navy-200'">
                {{ conv.name }}
              </span>
              <span class="text-xs text-navy-500 flex-shrink-0 ml-2">
                {{ formatTime(conv.last_message?.created_at) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs truncate" :class="conv.unread_count > 0 ? 'text-navy-300 font-medium' : 'text-navy-500'">
                <span v-if="conv.last_message?.is_mine" class="text-navy-600">Vous : </span>
                {{ conv.last_message?.body || 'Aucun message encore' }}
              </span>
              <span v-if="conv.unread_count > 0"
                class="ml-2 flex-shrink-0 min-w-[1.2rem] h-5 px-1.5 rounded-full bg-med-green text-navy-950 text-xs flex items-center justify-center font-bold">
                {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
              </span>
            </div>
          </div>

          <!-- Chevron -->
          <ChevronRightIcon class="w-4 h-4 text-navy-600 flex-shrink-0" />
        </NuxtLink>
      </div>
    </div>

    <!-- Modal sélection utilisateur -->
    <Teleport to="body">
      <div v-if="showPicker"
        class="fixed inset-0 bg-navy-950/85 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
        @click.self="showPicker = false">
        <div class="card w-full max-w-md animate-fade-up" @click.stop>
          <div class="card-header flex items-center justify-between">
            <h3 class="text-sm font-semibold text-navy-200">Choisir un destinataire</h3>
            <button @click="showPicker = false" class="text-navy-500 hover:text-navy-300">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <div class="p-4 space-y-3">
            <!-- Recherche -->
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-navy-500" />
              <input v-model="search" autofocus placeholder="Rechercher un utilisateur..."
                class="input pl-8 text-sm" />
            </div>
            <!-- Liste utilisateurs -->
            <div class="max-h-72 overflow-y-auto space-y-1">
              <div v-if="usersLoading" class="py-8 text-center text-navy-500 text-sm">Chargement...</div>
              <button
                v-for="u in filteredUsers"
                :key="u.id"
                @click.stop="openConversationWith(u)"
                :disabled="openingId === u.id"
                class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-navy-800 transition text-left disabled:opacity-50">
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold"
                  :class="roleColor(u.role).bg">
                  <span :class="roleColor(u.role).text">{{ u.name.charAt(0) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-navy-200">{{ u.name }}</div>
                  <div class="text-xs text-navy-500">{{ roleLabel(u.role) }}
                    <span v-if="u.service"> · {{ u.service }}</span>
                  </div>
                </div>
                <span v-if="openingId === u.id"
                  class="w-4 h-4 border-2 border-navy-500 border-t-transparent rounded-full animate-spin flex-shrink-0">
                </span>
                <ChevronRightIcon v-else class="w-4 h-4 text-navy-600 flex-shrink-0" />
              </button>
              <div v-if="!filteredUsers.length && !usersLoading"
                class="py-8 text-center text-navy-500 text-sm">
                Aucun utilisateur trouvé
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import {
  PencilSquareIcon, ChatBubbleLeftRightIcon, PlusIcon,
  MagnifyingGlassIcon, XMarkIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const api    = useApi()
const auth   = useAuthStore()
const router = useRouter()

const conversations = ref<any[]>([])
const loading       = ref(true)
const showPicker    = ref(false)
const search        = ref('')
const allUsers      = ref<any[]>([])
const usersLoading  = ref(false)
const openingId     = ref<number | null>(null)

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
  } as any)[r ?? ''] ?? r
}
function formatTime(iso: string) {
  if (!iso) return ''
  const d = new Date(iso), now = new Date()
  const diffMs = now.getTime() - d.getTime()
  if (d.toDateString() === now.toDateString())
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  if (diffMs < 7 * 86400000)
    return d.toLocaleDateString('fr-FR', { weekday: 'short' })
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
}

const filteredUsers = computed(() =>
  allUsers.value.filter(u =>
    u.id !== auth.user?.id &&
    (!search.value ||
      u.name.toLowerCase().includes(search.value.toLowerCase()) ||
      (u.service ?? '').toLowerCase().includes(search.value.toLowerCase()) ||
      u.role.toLowerCase().includes(search.value.toLowerCase()))
  )
)

// ── Load ──────────────────────────────────────────────────────────────────────
async function loadConversations() {
  loading.value = true
  try {
    const res = await api.get<any>('/conversations')
    conversations.value = res?.conversations ?? (Array.isArray(res) ? res : [])
  } catch (e) { console.error('[Messages]', e) }
  finally { loading.value = false }
}

async function loadUsers() {
  usersLoading.value = true
  try {
    const data = await api.get<any[]>('/users')
    allUsers.value = Array.isArray(data) ? data : []
  } catch (e) { console.error('[Users]', e) }
  finally { usersLoading.value = false }
}

// ── Ouvrir conversation avec un user → rediriger vers /messages/{id} ──────────
async function openConversationWith(user: any) {
  openingId.value = user.id
  try {
    const conv = await api.post<any>('/conversations', { user_id: user.id })
    showPicker.value = false
    search.value     = ''
    // Redirection vers la page de la conversation
    router.push(`/messages/${conv.id}`)
  } catch (e: any) {
    console.error('[Messages] openConversationWith:', e?.data?.message ?? e)
  } finally {
    openingId.value = null
  }
}

onMounted(() => {
  loadConversations()
  loadUsers()
})
</script>
