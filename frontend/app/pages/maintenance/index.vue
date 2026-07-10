<template>
  <div class="space-y-5 animate-fade-up">
    <!-- Actions bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3 flex-wrap">
        <select v-model="filterStatus" class="input w-auto text-xs py-2">
          <option value="">Tous les états</option>
          <option value="planifiee">Planifiée</option>
          <option value="en_cours">En cours</option>
          <option value="terminee">Terminée</option>
          <option value="annulee">Annulée</option>
        </select>
        <select v-model="filterType" class="input w-auto text-xs py-2">
          <option value="">Tous les types</option>
          <option value="preventive">Préventive</option>
          <option value="corrective">Corrective</option>
          <option value="predictive">Prédictive</option>
        </select>
      </div>
      <button @click="showModal = true" class="btn-primary">
        <PlusIcon class="w-4 h-4" />
        Planifier maintenance
      </button>
    </div>

    <!-- Weekly calendar -->
    <div class="card p-4">
      <h2 class="text-sm font-semibold text-navy-300 mb-4">Semaine en cours</h2>
      <div class="grid grid-cols-7 gap-2">
        <div v-for="day in currentWeek" :key="day.date"
          class="text-center rounded-lg p-2 border transition"
          :class="day.isToday
            ? 'border-med-green/40 bg-med-green/5'
            : 'border-navy-800'">
          <div class="text-xs text-navy-500">{{ day.dayName }}</div>
          <div class="text-sm font-bold mt-0.5" :class="day.isToday ? 'text-med-green' : 'text-navy-300'">
            {{ day.dayNum }}
          </div>
          <div v-if="day.count > 0"
            class="mt-1 w-5 h-5 rounded-full bg-med-orange/20 text-med-orange text-xs flex items-center justify-center mx-auto">
            {{ day.count }}
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-left" style="border-color:var(--color-border)">
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Appareil</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Titre / Type</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">État</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden md:table-cell">Date planifiée</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden lg:table-cell">Technicien</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden xl:table-cell">Coût total (XAF)</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y" style="border-color:var(--color-border)">
          <tr v-if="loading">
            <td colspan="7" class="py-12 text-center text-navy-500">
              <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
                Chargement...
              </span>
            </td>
          </tr>
          <tr v-else-if="!filteredRecords.length">
            <td colspan="7" class="py-12 text-center text-navy-500">Aucune maintenance enregistrée</td>
          </tr>
          <tr v-for="r in filteredRecords" :key="r.id" class="hover:bg-navy-800/30 transition">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-navy-200">{{ r.device_name }}</div>
              <div class="text-xs font-mono text-navy-500">{{ r.device_reference }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="text-sm text-navy-200 truncate max-w-48">{{ r.title }}</div>
              <span class="text-xs px-1.5 py-0.5 rounded bg-navy-800 text-navy-400 mt-0.5 inline-block">
                {{ typeLabels[r.type] ?? r.type }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(r.status)">
                {{ statusLabels[r.status] ?? r.status }}
              </span>
            </td>
            <td class="px-4 py-3 hidden md:table-cell text-xs font-mono text-navy-400">
              {{ r.scheduled_date || '–' }}
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-xs text-navy-400">
              {{ r.technicien || '–' }}
            </td>
            <td class="px-4 py-3 hidden xl:table-cell text-xs font-mono text-navy-400">
              {{ r.cost > 0 ? Number(r.cost).toLocaleString('fr-FR') : '–' }}
            </td>
            <td class="px-4 py-3 text-right">
              <button
                v-if="r.status !== 'terminee' && r.status !== 'annulee'"
                @click="cycleStatus(r)"
                class="text-xs px-2 py-1 rounded hover:bg-navy-700 text-navy-500 hover:text-navy-200 transition"
              >
                {{ r.status === 'planifiee' ? '▶ Démarrer' : '✓ Terminer' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Feedback toast -->
    <Transition name="fade">
      <div v-if="toast" class="fixed bottom-6 right-6 z-50 toast bg-navy-900/95"
        :class="toast.type === 'success' ? 'border-med-green/30 text-navy-100' : 'border-med-red/30 text-navy-100'">
        <span :class="toast.type === 'success' ? 'text-med-green' : 'text-med-red'">
          {{ toast.type === 'success' ? '✓' : '✗' }}
        </span>
        {{ toast.message }}
      </div>
    </Transition>

    <!-- Plan modal -->
    <MaintenanceModal
      v-if="showModal"
      @close="showModal = false"
      @saved="onSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const api = useApi()

const records      = ref<any[]>([])
const loading      = ref(true)
const saving       = ref(false)
const showModal    = ref(false)
const filterStatus = ref('')
const filterType   = ref('')
const toast        = ref<{ type: string; message: string } | null>(null)

const typeLabels: Record<string, string>   = { preventive: 'Préventive', corrective: 'Corrective', predictive: 'Prédictive' }
const statusLabels: Record<string, string> = { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }

watch([filterStatus, filterType], loadRecords)

function statusClass(s: string) {
  return {
    planifiee: 'bg-navy-700 text-navy-300',
    en_cours:  'bg-med-yellow/15 text-med-yellow',
    terminee:  'bg-med-green/15 text-med-green',
    annulee:   'bg-navy-800 text-navy-500',
  }[s] ?? 'bg-navy-700 text-navy-400'
}

const filteredRecords = computed(() => {
  return records.value.filter(r => {
    const matchStatus = !filterStatus.value || r.status === filterStatus.value
    const matchType   = !filterType.value   || r.type === filterType.value
    return matchStatus && matchType
  })
})

const currentWeek = computed(() => {
  const today  = new Date()
  const monday = new Date(today)
  monday.setDate(today.getDate() - today.getDay() + 1)
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)
    const dateStr = d.toISOString().split('T')[0]
    return {
      date:    dateStr,
      dayName: d.toLocaleDateString('fr-FR', { weekday: 'short' }),
      dayNum:  d.getDate(),
      isToday: dateStr === today.toISOString().split('T')[0],
      count:   records.value.filter(r => r.scheduled_date === dateStr).length,
    }
  })
})

async function loadRecords() {
  loading.value = true
  try {
    const data = await api.get<any[]>('/maintenance')
    records.value = Array.isArray(data) ? data : []
  } catch { records.value = [] }
  finally { loading.value = false }
}

async function cycleStatus(r: any) {
  const next = r.status === 'planifiee' ? 'en_cours' : 'terminee'
  try {
    await api.put(`/maintenance/${r.id}`, { status: next })
    const idx = records.value.findIndex(x => x.id === r.id)
    if (idx !== -1) records.value[idx] = { ...records.value[idx], status: next }
    showToast('success', next === 'terminee' ? 'Intervention terminée.' : 'Intervention démarrée.')
  } catch {
    showToast('error', 'Erreur lors de la mise à jour')
  }
}

async function onSaved() {
  showModal.value = false
  await loadRecords()
  showToast('success', 'Intervention planifiée avec succès.')
}

function showToast(type: string, message: string) {
  toast.value = { type, message }
  setTimeout(() => { toast.value = null }, 3000)
}

onMounted(loadRecords)
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
