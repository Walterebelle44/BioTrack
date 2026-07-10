<template>
  <div class="space-y-5 animate-fade-up">
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <div class="relative flex-1 max-w-sm">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-navy-500" />
        <input v-model="search" type="text" placeholder="Rechercher un appareil..." class="input pl-9" />
      </div>

      <div class="flex items-center gap-2">
        <select v-model="filterStatus" class="input w-auto text-xs py-2">
          <option value="">Tous les états</option>
          <option value="online">En ligne</option>
          <option value="offline">Hors ligne</option>
          <option value="alert">Alerte</option>
          <option value="maintenance">Maintenance</option>
        </select>

        <button @click="showAddModal = true" class="btn-primary">
          <PlusIcon class="w-4 h-4" />
          Ajouter
        </button>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div v-for="s in statsStrip" :key="s.label" class="stat-card flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :class="s.bg">
          <span class="text-sm">{{ s.icon }}</span>
        </div>
        <div>
          <div class="text-lg font-bold font-mono" :class="s.color">{{ s.value }}</div>
          <div class="text-xs text-navy-500">{{ s.label }}</div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-left" style="border-color: var(--color-border)">
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">Appareil</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden md:table-cell">Localisation</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider">État</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden lg:table-cell">Métriques IoT</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider hidden xl:table-cell">Dernière com.</th>
            <th class="px-4 py-3 text-xs font-medium text-navy-500 uppercase tracking-wider text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y" style="border-color: var(--color-border)">
          <tr v-if="loading">
            <td colspan="6" class="py-12 text-center text-navy-500">
              <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
                Chargement...
              </span>
            </td>
          </tr>
          <tr v-else-if="!filteredDevices.length">
            <td colspan="6" class="py-12 text-center text-navy-500">Aucun appareil trouvé</td>
          </tr>
          <tr
            v-for="device in filteredDevices"
            :key="device.id"
            class="hover:bg-navy-800/30 transition cursor-pointer group"
            @click="$router.push(`/devices/${device.id}`)"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <span class="status-dot" :class="device.status"></span>
                <div>
                  <div class="font-medium text-navy-200 group-hover:text-navy-50 transition">{{ device.name }}</div>
                  <div class="text-xs text-navy-500 font-mono">{{ device.serial_number }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <div class="text-navy-300 text-xs">{{ device.location || '–' }}</div>
              <div class="text-navy-600 text-xs" v-if="device.building">{{ device.building }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 rounded-full" :class="statusBadge(device.status)">
                {{ statusLabels[device.status] ?? device.status }}
              </span>
              <div v-if="device.open_alerts_count > 0" class="text-xs text-med-red mt-1">
                ⚠ {{ device.open_alerts_count }} alerte(s)
              </div>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell">
              <div class="flex gap-3 font-mono text-xs">
                <span v-if="device.last_metrics?.temperature !== undefined"
                  :class="device.status === 'alert' ? 'text-med-red' : 'text-navy-300'">
                  🌡 {{ device.last_metrics.temperature }}°C
                </span>
                <span v-if="device.last_metrics?.battery !== undefined || device.last_metrics?.battery_level !== undefined"
                  :class="(device.last_metrics.battery ?? device.last_metrics.battery_level) < 20 ? 'text-med-red' : 'text-navy-300'">
                  🔋 {{ device.last_metrics.battery ?? device.last_metrics.battery_level }}%
                </span>
                <span v-if="!device.last_metrics" class="text-navy-600">Pas de données</span>
              </div>
            </td>
            <td class="px-4 py-3 hidden xl:table-cell text-xs font-mono text-navy-500">
              {{ formatRelativeTime(device.last_seen_at) }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click.stop="openEditModal(device)"
                  class="p-1.5 rounded text-navy-500 hover:text-navy-200 hover:bg-navy-700 transition"
                  title="Modifier"
                >
                  <PencilIcon class="w-3.5 h-3.5" />
                </button>
                <button
                  @click.stop="openMaintenanceModal(device)"
                  class="p-1.5 rounded text-navy-500 hover:text-med-yellow hover:bg-navy-700 transition"
                  title="Planifier maintenance"
                >
                  <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                </button>
                <button
                  @click.stop="simulateDevice(device)"
                  class="p-1.5 rounded text-navy-500 hover:text-med-green hover:bg-navy-700 transition"
                  title="Simuler données MQTT"
                >
                  <BeakerIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Feedback toast -->
    <Transition name="fade">
      <div v-if="toast" class="fixed bottom-6 right-6 z-50 toast"
        :class="toast.type === 'success'
          ? 'bg-navy-900/95 border-med-green/30 text-navy-100'
          : 'bg-navy-900/95 border-med-red/30 text-navy-100'">
        <div class="flex items-center gap-2">
          <span :class="toast.type === 'success' ? 'text-med-green' : 'text-med-red'">
            {{ toast.type === 'success' ? '✓' : '✗' }}
          </span>
          {{ toast.message }}
        </div>
      </div>
    </Transition>

    <!-- Modals -->
    <DeviceModal v-if="showAddModal"
      @close="showAddModal = false"
      @saved="onDeviceSaved" />

    <DeviceModal v-if="editDevice"
      :device="editDevice"
      @close="editDevice = null"
      @saved="onDeviceSaved" />

    <MaintenanceModal v-if="maintenanceDevice"
      :device-id="maintenanceDevice.id"
      :device-name="maintenanceDevice.name"
      @close="maintenanceDevice = null"
      @saved="onMaintenanceSaved" />
  </div>
</template>

<script setup lang="ts">
import {
  MagnifyingGlassIcon, PlusIcon, BeakerIcon,
  PencilIcon, WrenchScrewdriverIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const api              = useApi()
const devices          = ref<any[]>([])
const loading          = ref(true)
const search           = ref('')
const filterStatus     = ref('')
const showAddModal     = ref(false)
const editDevice       = ref<any>(null)
const maintenanceDevice = ref<any>(null)
const toast            = ref<{ type: string; message: string } | null>(null)

const statusLabels: Record<string, string> = {
  online: 'En ligne', offline: 'Hors ligne',
  alert: 'Alerte', maintenance: 'Maintenance', unknown: 'Inconnu',
}

function statusBadge(status: string) {
  return {
    online: 'badge-online', offline: 'badge-offline',
    alert: 'badge-alert', maintenance: 'badge-maintenance',
  }[status] ?? 'badge-offline'
}

const statsStrip = computed(() => {
  const total = devices.value.length
  const online = devices.value.filter(d => d.status === 'online').length
  const alert  = devices.value.filter(d => d.status === 'alert').length
  const maint  = devices.value.filter(d => d.status === 'maintenance').length
  return [
    { label: 'Total',        value: total,  icon: '📟', color: 'text-navy-200',   bg: 'bg-navy-800' },
    { label: 'En ligne',     value: online, icon: '🟢', color: 'text-med-green',  bg: 'bg-med-green/10' },
    { label: 'En alerte',    value: alert,  icon: '🔴', color: 'text-med-red',    bg: 'bg-med-red/10' },
    { label: 'Maintenance',  value: maint,  icon: '🔧', color: 'text-med-yellow', bg: 'bg-med-yellow/10' },
  ]
})

const filteredDevices = computed(() =>
  devices.value.filter(d => {
    const q = search.value.toLowerCase()
    const matchSearch = !q ||
      (d.name ?? '').toLowerCase().includes(q) ||
      (d.serial_number ?? '').toLowerCase().includes(q) ||
      (d.location ?? '').toLowerCase().includes(q)
    const matchStatus = !filterStatus.value || d.status === filterStatus.value
    return matchSearch && matchStatus
  })
)

async function loadDevices() {
  loading.value = true
  try {
    const data = await api.get<any[]>('/devices')
    devices.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    const status = e?.response?.status ?? e?.status ?? '?'
    const msg    = e?.data?.message ?? e?.message ?? ''
    console.error(`[loadDevices] HTTP ${status}:`, msg, e)
    if (status !== 401) {
      showToast('error', `Erreur ${status} : ${msg || 'Impossible de charger les équipements'}`)
    }
  } finally {
    loading.value = false
  }
}

async function simulateDevice(device: any) {
  try {
    await api.post(`/devices/${device.id}/simulate`, {})
    showToast('success', `Simulation MQTT envoyée pour ${device.name}`)
    setTimeout(loadDevices, 1200)
  } catch {
    showToast('error', 'Aucun capteur IoT associé à cet appareil')
  }
}

function openEditModal(device: any) {
  editDevice.value = device
}

function openMaintenanceModal(device: any) {
  maintenanceDevice.value = device
}

async function onDeviceSaved() {
  showAddModal.value  = false
  editDevice.value    = null
  await loadDevices()
  showToast('success', 'Équipement enregistré avec succès')
}

async function onMaintenanceSaved() {
  maintenanceDevice.value = null
  await loadDevices()
  showToast('success', 'Intervention planifiée avec succès')
}

function showToast(type: string, message: string) {
  toast.value = { type, message }
  setTimeout(() => { toast.value = null }, 3500)
}

function formatRelativeTime(iso: string): string {
  if (!iso) return 'Jamais'
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'À l\'instant'
  if (mins < 60) return `${mins} min`
  return `${Math.floor(mins / 60)}h`
}

const { $echo } = useNuxtApp()
onMounted(() => {
  loadDevices()
  if ($echo) {
    $echo.channel('devices').listen('.device.metric', (data: any) => {
      const idx = devices.value.findIndex(d => d.serial_number === data.serial_number)
      if (idx !== -1) {
        devices.value[idx] = {
          ...devices.value[idx],
          status: data.status,
          last_metrics: data.metrics,
          last_seen_at: data.recorded_at,
        }
      }
    })
  }
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
