<template>
  <div class="space-y-5 animate-fade-up">
    <!-- Stats bar -->
    <div class="grid grid-cols-3 gap-4">
      <div class="stat-card" style="border-color: rgba(255,77,109,0.2)">
        <div class="text-xs text-navy-500 uppercase tracking-wider mb-1">Critiques ouvertes</div>
        <div class="metric-value text-med-red">{{ stats.open_critical }}</div>
      </div>
      <div class="stat-card" style="border-color: rgba(255,159,67,0.2)">
        <div class="text-xs text-navy-500 uppercase tracking-wider mb-1">Avertissements</div>
        <div class="metric-value text-med-orange">{{ stats.open_warning }}</div>
      </div>
      <div class="stat-card">
        <div class="text-xs text-navy-500 uppercase tracking-wider mb-1">Résolues aujourd'hui</div>
        <div class="metric-value text-med-green">{{ stats.resolved_today }}</div>
      </div>
    </div>

    <!-- Filters + Actions -->
    <div class="flex flex-wrap items-center gap-3">
      <select v-model="filterStatus" class="input w-auto text-xs py-2">
        <option value="">Tous les états</option>
        <option value="open">Ouvertes</option>
        <option value="acknowledged">Prises en charge</option>
        <option value="resolved">Résolues</option>
      </select>
      <select v-model="filterSeverity" class="input w-auto text-xs py-2">
        <option value="">Toutes les sévérités</option>
        <option value="critical">Critique</option>
        <option value="warning">Avertissement</option>
        <option value="info">Info</option>
      </select>
      <div class="flex-1"></div>
      <button v-if="selected.length" @click="bulkAcknowledge" class="btn-secondary text-xs">
        <CheckIcon class="w-3.5 h-3.5" />
        Prendre en charge ({{ selected.length }})
      </button>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-left" style="border-color:var(--color-border)">
            <th class="px-4 py-3 w-8">
              <input type="checkbox" @change="toggleAll" class="rounded accent-med-green" />
            </th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Sévérité</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Alerte</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden md:table-cell">Appareil</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">État</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden lg:table-cell">Date</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y" style="border-color:var(--color-border)">
          <tr v-if="loading">
            <td colspan="7" class="py-12 text-center text-navy-500 text-sm">
              <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
                Chargement...
              </span>
            </td>
          </tr>
          <tr v-else-if="!alerts.length">
            <td colspan="7" class="py-12 text-center text-navy-500 text-sm">
              <CheckCircleIcon class="w-10 h-10 mx-auto mb-2 text-med-green opacity-40" />
              Aucune alerte pour ces filtres
            </td>
          </tr>
          <tr v-for="alert in alerts" :key="alert.id"
            class="hover:bg-navy-800/30 transition"
            :class="alert.severity === 'critical' && alert.status === 'open' ? 'bg-med-red/5' : ''">
            <td class="px-4 py-3">
              <input type="checkbox" :value="alert.id" v-model="selected" class="rounded accent-med-green" />
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full"
                :class="severityClass(alert.severity)">
                <span class="w-1.5 h-1.5 rounded-full" :class="severityDot(alert.severity)"></span>
                {{ severityLabel(alert.severity) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-navy-200">{{ alert.title }}</div>
              <div v-if="alert.message" class="text-xs text-navy-500 mt-0.5 line-clamp-1">{{ alert.message }}</div>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <div class="text-xs text-navy-300">{{ alert.device_name ?? alert.device?.name }}</div>
              <div class="text-xs text-navy-600">{{ alert.device_location ?? alert.device?.location }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(alert.status)">
                {{ statusLabel(alert.status) }}
              </span>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-xs font-mono text-navy-500">
              {{ formatDate(alert.created_at) }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-1">
                <button v-if="alert.status === 'open'"
                  @click="acknowledge(alert)"
                  class="p-1.5 rounded hover:bg-navy-700 text-navy-400 hover:text-med-yellow transition"
                  title="Prendre en charge">
                  <HandRaisedIcon class="w-3.5 h-3.5" />
                </button>
                <button v-if="alert.status !== 'resolved'"
                  @click="openResolveModal(alert)"
                  class="p-1.5 rounded hover:bg-med-green/10 text-navy-400 hover:text-med-green transition"
                  title="Résoudre">
                  <CheckIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Resolve Modal -->
    <div v-if="resolveAlert" class="fixed inset-0 bg-navy-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="card w-full max-w-md">
        <div class="card-header flex items-center justify-between">
          <h3 class="text-sm font-semibold text-navy-200">Résoudre l'alerte</h3>
          <button @click="resolveAlert = null"><XMarkIcon class="w-5 h-5 text-navy-500" /></button>
        </div>
        <div class="p-5 space-y-4">
          <div class="p-3 rounded-lg bg-navy-800/50 text-sm text-navy-300">{{ resolveAlert.title }}</div>
          <div>
            <label class="label">Note de résolution (optionnelle)</label>
            <textarea v-model="resolveNote" class="input h-24 resize-none"
              placeholder="Décrivez les actions correctives prises..."></textarea>
          </div>
          <div class="flex gap-3 justify-end">
            <button @click="resolveAlert = null" class="btn-secondary text-xs">Annuler</button>
            <button @click="resolveConfirm" class="btn-primary text-xs" :disabled="resolving">
              <span v-if="resolving" class="w-3.5 h-3.5 border-2 border-navy-800 border-t-transparent rounded-full animate-spin"></span>
              <CheckIcon v-else class="w-3.5 h-3.5" />
              Résoudre
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckCircleIcon, CheckIcon, HandRaisedIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const api = useApi()

const alerts         = ref<any[]>([])
const loading        = ref(true)
const filterStatus   = ref('open')
const filterSeverity = ref('')
const selected       = ref<number[]>([])
const stats          = ref({ open_critical: 0, open_warning: 0, resolved_today: 0 })
const resolveAlert   = ref<any>(null)
const resolveNote    = ref('')
const resolving      = ref(false)

watch([filterStatus, filterSeverity], loadAlerts)

function severityClass(s: string) {
  return { critical: 'bg-med-red/15 text-med-red', warning: 'bg-med-orange/15 text-med-orange', info: 'bg-navy-700 text-navy-400' }[s] ?? 'bg-navy-700 text-navy-400'
}
function severityDot(s: string) {
  return { critical: 'bg-med-red', warning: 'bg-med-orange', info: 'bg-med-blue' }[s] ?? 'bg-navy-500'
}
function severityLabel(s: string) {
  return { critical: 'Critique', warning: 'Avertissement', info: 'Info' }[s] ?? s
}
function statusClass(s: string) {
  return { open: 'bg-navy-700 text-navy-300', acknowledged: 'bg-med-yellow/15 text-med-yellow', resolved: 'bg-med-green/15 text-med-green' }[s] ?? 'bg-navy-700 text-navy-400'
}
function statusLabel(s: string) {
  return { open: 'Ouverte', acknowledged: 'Prise en charge', resolved: 'Résolue' }[s] ?? s
}
function formatDate(iso: string) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

async function loadAlerts() {
  loading.value = true
  try {
    const params: any = {}
    if (filterStatus.value)   params.status   = filterStatus.value
    if (filterSeverity.value) params.severity = filterSeverity.value

    // Laravel retourne { alerts: [...], data: [...], stats: {...} }
    const res = await api.get<any>('/alerts', params)
    alerts.value = Array.isArray(res?.alerts) ? res.alerts
                 : Array.isArray(res?.data)   ? res.data
                 : Array.isArray(res)         ? res
                 : []
    if (res?.stats) {
      stats.value = {
        open_critical:  Number(res.stats.open_critical  ?? 0),
        open_warning:   Number(res.stats.open_warning   ?? 0),
        resolved_today: Number(res.stats.resolved_today ?? 0),
      }
    }
  } catch (e) { console.error('Erreur chargement alertes', e) }
  finally { loading.value = false }
}

function toggleAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked
  selected.value = checked ? alerts.value.map(a => Number(a.id)) : []
}

async function acknowledge(alert: any) {
  try {
    // Laravel: POST /alerts/{id}/acknowledge
    await api.post(`/alerts/${alert.id}/acknowledge`, {})
    await loadAlerts()
  } catch (e: any) { console.error("[API Error]", e?.response?.status ?? e?.status, e?.data?.message ?? e?.message, e) }
}

async function bulkAcknowledge() {
  try {
    // Laravel: POST /alerts/bulk-acknowledge
    await api.post('/alerts/bulk-acknowledge', { ids: selected.value })
    selected.value = []
    await loadAlerts()
  } catch (e: any) { console.error("[API Error]", e?.response?.status ?? e?.status, e?.data?.message ?? e?.message, e) }
}

function openResolveModal(alert: any) {
  resolveAlert.value = alert
  resolveNote.value  = ''
}

async function resolveConfirm() {
  resolving.value = true
  try {
    // Laravel: POST /alerts/{id}/resolve
    await api.post(`/alerts/${resolveAlert.value.id}/resolve`, {
      resolution_note: resolveNote.value,
    })
    resolveAlert.value = null
    await loadAlerts()
  } catch (e: any) { console.error("[API Error]", e?.response?.status ?? e?.status, e?.data?.message ?? e?.message, e) }
  finally { resolving.value = false }
}

const { $echo } = useNuxtApp()
onMounted(() => {
  loadAlerts()
  if ($echo) {
    $echo.channel('alerts').listen('.alert.triggered', () => loadAlerts())
  }
})
</script>
