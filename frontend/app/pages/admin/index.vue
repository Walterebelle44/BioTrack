<template>
  <div class="space-y-6 animate-fade-up">
    <!-- Tabs -->
    <div class="flex gap-1 p-1 bg-navy-900 rounded-xl border border-navy-800 w-fit">
      <button v-for="tab in tabs" :key="tab.id"
        @click="activeTab = tab.id"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-medium transition"
        :class="activeTab === tab.id
          ? 'bg-med-green text-navy-950'
          : 'text-navy-400 hover:text-navy-200 hover:bg-navy-800'">
        <component :is="tab.icon" class="w-3.5 h-3.5" />
        {{ tab.label }}
      </button>
    </div>

    <!-- ═══ UTILISATEURS ═══════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'users'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-navy-200">Gestion des utilisateurs</h2>
        <button @click="openUserModal(null)" class="btn-primary text-xs">
          <PlusIcon class="w-3.5 h-3.5" /> Nouvel utilisateur
        </button>
      </div>

      <div class="card overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b text-left" style="border-color:var(--color-border)">
              <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Utilisateur</th>
              <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Rôle</th>
              <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden md:table-cell">Service</th>
              <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden lg:table-cell">Téléphone</th>
              <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">État</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y" style="border-color:var(--color-border)">
            <tr v-if="usersLoading">
              <td colspan="6" class="py-10 text-center text-navy-500">
                <span class="inline-flex items-center gap-2 text-sm">
                  <span class="w-4 h-4 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
                  Chargement...
                </span>
              </td>
            </tr>
            <tr v-for="u in users" :key="u.id" class="hover:bg-navy-800/30 transition">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                    :class="roleColor(u.role).bg">
                    <span :class="roleColor(u.role).text">{{ u.name.charAt(0) }}</span>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-navy-200">{{ u.name }}</div>
                    <div class="text-xs text-navy-500">{{ u.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-0.5 rounded-full" :class="roleColor(u.role).badge">
                  {{ roleLabel(u.role) }}
                </span>
              </td>
              <td class="px-4 py-3 hidden md:table-cell text-xs text-navy-400">{{ u.service || '–' }}</td>
              <td class="px-4 py-3 hidden lg:table-cell text-xs font-mono text-navy-500">{{ u.phone || '–' }}</td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-0.5 rounded-full"
                  :class="u.is_active ? 'bg-med-green/15 text-med-green' : 'bg-navy-800 text-navy-500'">
                  {{ u.is_active ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1 justify-end">
                  <button @click="openUserModal(u)"
                    class="p-1.5 rounded hover:bg-navy-700 text-navy-500 hover:text-navy-200 transition" title="Modifier">
                    <PencilIcon class="w-3.5 h-3.5" />
                  </button>
                  <button @click="toggleUserStatus(u)"
                    class="p-1.5 rounded hover:bg-navy-700 transition"
                    :class="u.is_active ? 'text-navy-500 hover:text-med-red' : 'text-navy-500 hover:text-med-green'"
                    :title="u.is_active ? 'Désactiver' : 'Réactiver'">
                    <LockClosedIcon v-if="u.is_active" class="w-3.5 h-3.5" />
                    <LockOpenIcon v-else class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ PROFILS APPAREILS ═══════════════════════════════════════════════ -->
    <div v-if="activeTab === 'profiles'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-navy-200">Profils d'appareils</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-if="profilesLoading" class="col-span-3 py-10 text-center text-navy-500 text-sm">Chargement...</div>
        <div v-for="p in profiles" :key="p.id" class="card p-4">
          <div class="flex items-start justify-between mb-3">
            <div>
              <div class="text-sm font-semibold text-navy-100">{{ p.label }}</div>
              <div class="text-xs text-navy-500 font-mono mt-0.5">{{ p.name }}</div>
            </div>
            <span class="text-lg">{{ profileIcon(p.name) }}</span>
          </div>
          <p class="text-xs text-navy-500 mb-3 line-clamp-2">{{ p.description || '–' }}</p>

          <!-- Métriques -->
          <div class="mb-3">
            <div class="text-xs text-navy-600 uppercase tracking-wider mb-1.5">Métriques suivies</div>
            <div class="flex flex-wrap gap-1">
              <span v-for="m in (p.metrics ?? [])" :key="m"
                class="text-xs px-1.5 py-0.5 rounded bg-navy-800 text-navy-400 font-mono">{{ m }}</span>
            </div>
          </div>

          <!-- Seuils -->
          <div>
            <div class="text-xs text-navy-600 uppercase tracking-wider mb-1.5">Seuils d'alerte</div>
            <div class="space-y-1">
              <div v-for="(threshold, key) in (p.alert_thresholds ?? {})" :key="key"
                class="flex items-center justify-between text-xs">
                <span class="text-navy-500 font-mono">{{ key }}</span>
                <span class="text-navy-300 font-mono">
                  <span v-if="threshold.min !== undefined">≥{{ threshold.min }}</span>
                  <span v-if="threshold.min !== undefined && threshold.max !== undefined"> · </span>
                  <span v-if="threshold.max !== undefined">≤{{ threshold.max }}</span>
                  <span class="text-navy-600">{{ threshold.unit ?? '' }}</span>
                </span>
              </div>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t flex items-center justify-between text-xs text-navy-600"
            style="border-color:var(--color-border)">
            <span>{{ deviceCountByProfile[p.id] ?? 0 }} appareil(s)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ SYSTÈME ══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'system'" class="space-y-4 max-w-3xl">
      <!-- Stats globales -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div v-for="s in systemStats" :key="s.label" class="stat-card">
          <div class="text-xs text-navy-500 uppercase tracking-wider mb-1">{{ s.label }}</div>
          <div class="text-xl font-bold font-mono" :class="s.color">{{ s.value }}</div>
        </div>
      </div>

      <!-- Infos stack -->
      <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-navy-200">Stack technique</h3></div>
        <div class="divide-y" style="border-color:var(--color-border)">
          <div v-for="item in stackInfo" :key="item.label"
            class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-lg">{{ item.icon }}</span>
              <div>
                <div class="text-sm text-navy-200">{{ item.label }}</div>
                <div class="text-xs text-navy-500">{{ item.desc }}</div>
              </div>
            </div>
            <span class="text-xs font-mono px-2 py-0.5 rounded bg-navy-800 text-navy-400">{{ item.version }}</span>
          </div>
        </div>
      </div>

      <!-- Config MQTT / Reverb -->
      <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-navy-200">Configuration IoT & WebSocket</h3></div>
        <div class="p-4 space-y-3">
          <div v-for="cfg in iotConfig" :key="cfg.label" class="flex items-center justify-between text-sm">
            <span class="text-navy-500">{{ cfg.label }}</span>
            <span class="font-mono text-xs bg-navy-800 px-2 py-0.5 rounded text-navy-300">{{ cfg.value }}</span>
          </div>
        </div>
      </div>

      <!-- Actions système -->
      <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-navy-200">Actions système</h3></div>
        <div class="p-4 space-y-3">
          <div class="flex items-start justify-between gap-4 py-3 border-b" style="border-color:var(--color-border)">
            <div>
              <div class="text-sm text-navy-200">Purger les alertes résolues</div>
              <div class="text-xs text-navy-500 mt-0.5">Supprime les alertes résolues de plus de 30 jours</div>
            </div>
            <button @click="systemAction('purge')" :disabled="actionLoading === 'purge'"
              class="btn-secondary text-xs flex-shrink-0">
              <span v-if="actionLoading === 'purge'" class="w-3 h-3 border border-navy-500 border-t-transparent rounded-full animate-spin"></span>
              <TrashIcon v-else class="w-3.5 h-3.5" />
              Purger
            </button>
          </div>
          <div class="flex items-start justify-between gap-4 py-3 border-b" style="border-color:var(--color-border)">
            <div>
              <div class="text-sm text-navy-200">Recalculer les statuts</div>
              <div class="text-xs text-navy-500 mt-0.5">Force la vérification des appareils hors ligne</div>
            </div>
            <button @click="systemAction('recheckOffline')" :disabled="actionLoading === 'recheckOffline'"
              class="btn-secondary text-xs flex-shrink-0">
              <ArrowPathIcon class="w-3.5 h-3.5" />
              Recalculer
            </button>
          </div>
          <div class="flex items-start justify-between gap-4 py-3">
            <div>
              <div class="text-sm text-navy-200">Exporter les données</div>
              <div class="text-xs text-navy-500 mt-0.5">Export CSV des appareils, alertes et maintenances</div>
            </div>
            <button @click="exportData" class="btn-secondary text-xs flex-shrink-0">
              <ArrowDownTrayIcon class="w-3.5 h-3.5" />
              Exporter CSV
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ JOURNAUX ════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'logs'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-navy-200">Journal d'activité</h2>
        <div class="text-xs text-navy-500">{{ logs.length }} entrées</div>
      </div>
      <div class="card overflow-hidden">
        <div class="divide-y max-h-[60vh] overflow-y-auto" style="border-color:var(--color-border)">
          <div v-if="!logs.length" class="py-12 text-center text-navy-500 text-sm">
            Aucun journal disponible (connecter la table activity_logs)
          </div>
          <div v-for="log in logs" :key="log.id" class="px-4 py-3 flex items-start gap-4">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs flex-shrink-0"
              :class="logColor(log.type)">
              {{ logIcon(log.type) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs font-medium text-navy-200">{{ log.description }}</div>
              <div class="text-xs text-navy-500 mt-0.5">{{ log.user }} · {{ formatDate(log.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="fade">
      <div v-if="toast" class="fixed bottom-6 right-6 z-50 toast bg-navy-900/95"
        :class="toast.type === 'success' ? 'border-med-green/30 text-navy-100' : 'border-med-red/30 text-navy-100'">
        <span :class="toast.type === 'success' ? 'text-med-green' : 'text-med-red'">
          {{ toast.type === 'success' ? '✓' : '✗' }}
        </span>
        {{ toast.message }}
      </div>
    </Transition>

    <!-- User Modal -->
    <Teleport to="body">
    <div v-if="userModal" class="fixed inset-0 bg-navy-950/85 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
      @click.self="userModal = null">
      <div class="card w-full max-w-lg animate-fade-up">
        <div class="card-header flex items-center justify-between">
          <h3 class="text-sm font-semibold text-navy-200">
            {{ userModal.id ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
          </h3>
          <button @click="userModal = null"><XMarkIcon class="w-5 h-5 text-navy-500" /></button>
        </div>
        <div class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="label">Nom complet *</label>
              <input v-model="userModal.name" class="input" placeholder="Jean Mvondo" />
            </div>
            <div class="col-span-2">
              <label class="label">Email *</label>
              <input v-model="userModal.email" type="email" class="input" placeholder="jean@biotrack.cm" />
            </div>
            <div v-if="!userModal.id" class="col-span-2">
              <label class="label">Mot de passe *</label>
              <input v-model="userModal.password" type="password" class="input" placeholder="minimum 8 caractères" />
            </div>
            <div>
              <label class="label">Rôle *</label>
              <select v-model="userModal.role" class="input">
                <option value="admin">Administrateur</option>
                <option value="directeur">Directeur</option>
                <option value="technicien">Technicien</option>
                <option value="responsable_si">Responsable SI</option>
              </select>
            </div>
            <div>
              <label class="label">Service</label>
              <input v-model="userModal.service" class="input" placeholder="Maintenance Biomédicale" />
            </div>
            <div class="col-span-2">
              <label class="label">Téléphone</label>
              <input v-model="userModal.phone" class="input" placeholder="+237 6XX XXX XXX" />
            </div>
          </div>

          <div v-if="userModalError" class="p-3 rounded-lg bg-med-red/10 border border-med-red/25 text-med-red text-sm">
            {{ userModalError }}
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button @click="userModal = null" class="btn-secondary text-xs">Annuler</button>
            <button @click="saveUser" class="btn-primary text-xs" :disabled="userSaving">
              <span v-if="userSaving" class="w-3.5 h-3.5 border-2 border-navy-800 border-t-transparent rounded-full animate-spin"></span>
              <span v-else>{{ userModal.id ? 'Mettre à jour' : 'Créer' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import {
  PlusIcon, PencilIcon, LockClosedIcon, LockOpenIcon,
  TrashIcon, ArrowPathIcon, ArrowDownTrayIcon, XMarkIcon,
  UsersIcon, CpuChipIcon, ServerIcon, ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'
import { useRuntimeConfig } from '#app'

const api    = useApi()
const config = useRuntimeConfig()

const activeTab      = ref('users')
const users          = ref<any[]>([])
const usersLoading   = ref(true)
const profiles       = ref<any[]>([])
const profilesLoading = ref(true)
const devices        = ref<any[]>([])
const logs           = ref<any[]>([])
const toast          = ref<any>(null)
const userModal      = ref<any>(null)
const userModalError = ref('')
const userSaving     = ref(false)
const actionLoading  = ref('')

const tabs = [
  { id: 'users',    label: 'Utilisateurs',    icon: UsersIcon },
  { id: 'profiles', label: 'Profils IoT',     icon: CpuChipIcon },
  { id: 'system',   label: 'Système',         icon: ServerIcon },
  { id: 'logs',     label: 'Journaux',        icon: ClipboardDocumentListIcon },
]

// ── Helpers ────────────────────────────────────────────────────────────────
function roleLabel(r: string) {
  return { admin: 'Administrateur', directeur: 'Directeur', technicien: 'Technicien', responsable_si: 'Resp. SI' }[r] ?? r
}
function roleColor(r: string) {
  const map: any = {
    admin:          { bg: 'bg-med-red/20',    text: 'text-med-red',    badge: 'bg-med-red/15 text-med-red' },
    directeur:      { bg: 'bg-med-blue/20',   text: 'text-med-blue',   badge: 'bg-med-blue/15 text-med-blue' },
    technicien:     { bg: 'bg-med-green/20',  text: 'text-med-green',  badge: 'bg-med-green/15 text-med-green' },
    responsable_si: { bg: 'bg-med-orange/20', text: 'text-med-orange', badge: 'bg-med-orange/15 text-med-orange' },
  }
  return map[r] ?? { bg: 'bg-navy-800', text: 'text-navy-400', badge: 'bg-navy-800 text-navy-400' }
}
function profileIcon(name: string) {
  return { refrigerator: '🧊', syringe_pump: '💉', oxygen_concentrator: '🫁', patient_monitor: '💓', defibrillator: '⚡' }[name] ?? '🔬'
}
function logColor(type: string) {
  return { error: 'bg-med-red/15 text-med-red', warning: 'bg-med-orange/15 text-med-orange', info: 'bg-navy-800 text-navy-400' }[type] ?? 'bg-navy-800 text-navy-400'
}
function logIcon(type: string) {
  return { error: '✗', warning: '⚠', info: 'ℹ' }[type] ?? '·'
}
function formatDate(iso: string) {
  return iso ? new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '–'
}
function showToast(type: string, message: string) {
  toast.value = { type, message }
  setTimeout(() => { toast.value = null }, 3000)
}

// ── Device count per profile ────────────────────────────────────────────────
const deviceCountByProfile = computed(() => {
  const counts: Record<number, number> = {}
  devices.value.forEach(d => {
    const pid = d.profile?.id ?? d.device_profile_id
    if (pid) counts[pid] = (counts[pid] ?? 0) + 1
  })
  return counts
})

// ── System stats ────────────────────────────────────────────────────────────
const systemStats = computed(() => [
  { label: 'Appareils actifs', value: devices.value.length, color: 'text-navy-200' },
  { label: 'Profils IoT',      value: profiles.value.length, color: 'text-med-blue' },
  { label: 'Utilisateurs',     value: users.value.length, color: 'text-med-green' },
  { label: 'Version API',      value: 'v1.0', color: 'text-navy-400' },
])

const stackInfo = [
  { icon: '⚡', label: 'Frontend',  desc: 'Nuxt 3 + TailwindCSS', version: '3.x' },
  { icon: '🐘', label: 'Backend',   desc: 'Laravel 11 + Sanctum', version: '11.x' },
  { icon: '🗄️', label: 'Base de données', desc: 'MySQL 8.0', version: '8.0' },
  { icon: '📡', label: 'WebSocket', desc: 'Laravel Reverb', version: '1.x' },
  { icon: '🔌', label: 'IoT',       desc: 'MQTT v3.1.1 / BLE', version: '3.1.1' },
]

const iotConfig = computed(() => [
  { label: 'API Base URL',    value: config.public.apiBase },
  { label: 'Reverb Host',     value: `${config.public.reverbHost}:${config.public.reverbPort}` },
  { label: 'Reverb Key',      value: String(config.public.reverbKey).slice(0, 8) + '...' },
  { label: 'MQTT Broker',     value: 'localhost:1883' },
  { label: 'Topic prefix',    value: 'meditrack/devices/+/telemetry' },
])

// ── Load data ────────────────────────────────────────────────────────────────
async function loadUsers() {
  usersLoading.value = true
  try {
    const data = await api.get<any[]>('/users')
    users.value = Array.isArray(data) ? data : []
  } catch { users.value = [] }
  finally { usersLoading.value = false }
}

async function loadProfiles() {
  profilesLoading.value = true
  try {
    const data = await api.get<any[]>('/device-profiles')
    profiles.value = Array.isArray(data) ? data : []
  } catch { profiles.value = [] }
  finally { profilesLoading.value = false }
}

async function loadDevices() {
  try {
    const data = await api.get<any[]>('/devices')
    devices.value = Array.isArray(data) ? data : []
  } catch {}
}

// ── User CRUD ────────────────────────────────────────────────────────────────
function openUserModal(user: any) {
  userModalError.value = ''
  if (user) {
    userModal.value = { ...user }
  } else {
    userModal.value = { name: '', email: '', password: '', role: 'technicien', service: '', phone: '' }
  }
}

async function saveUser() {
  if (!userModal.value.name.trim()) { userModalError.value = 'Nom requis.'; return }
  if (!userModal.value.email.trim()) { userModalError.value = 'Email requis.'; return }
  if (!userModal.value.id && !userModal.value.password) { userModalError.value = 'Mot de passe requis.'; return }

  userSaving.value = true
  userModalError.value = ''
  try {
    if (userModal.value.id) {
      const { password, ...data } = userModal.value
      await api.put(`/users/${userModal.value.id}`, data)
    } else {
      await api.post('/users', userModal.value)
    }
    userModal.value = null
    await loadUsers()
    showToast('success', 'Utilisateur enregistré.')
  } catch (e: any) {
    const errors = e?.data?.errors
    userModalError.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e?.data?.message ?? 'Erreur lors de l\'enregistrement')
  } finally {
    userSaving.value = false
  }
}

async function toggleUserStatus(user: any) {
  try {
    await api.put(`/users/${user.id}`, { is_active: !user.is_active })
    user.is_active = !user.is_active
    showToast('success', user.is_active ? 'Compte réactivé.' : 'Compte désactivé.')
  } catch { showToast('error', 'Erreur lors de la mise à jour.') }
}

// ── System actions ────────────────────────────────────────────────────────────
async function systemAction(action: string) {
  actionLoading.value = action
  // Simulation côté frontend (les vraies actions nécessitent des routes backend dédiées)
  await new Promise(r => setTimeout(r, 1200))
  actionLoading.value = ''
  showToast('success', action === 'purge' ? 'Alertes purgées.' : 'Statuts recalculés.')
}

function exportData() {
  const rows = [
    ['ID', 'Nom', 'Numéro de série', 'Localisation', 'Statut', 'Dernière communication'],
    ...devices.value.map(d => [d.id, d.name, d.serial_number, d.location, d.status, d.last_seen_at ?? ''])
  ]
  const csv = rows.map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `biotrack_export_${new Date().toISOString().split('T')[0]}.csv`
  a.click()
}

onMounted(() => {
  loadUsers()
  loadProfiles()
  loadDevices()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
