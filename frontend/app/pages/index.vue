<template>
  <div class="space-y-6 animate-fade-up">
    <!-- Stats row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="stat-card">
        <div class="flex items-center justify-between">
          <span class="text-xs text-navy-400 font-medium uppercase tracking-wider">Appareils actifs</span>
          <CpuChipIcon class="w-4 h-4 text-navy-500" />
        </div>
        <div class="metric-value text-navy-100">{{ summary?.devices?.total ?? '–' }}</div>
        <div class="flex items-center gap-1.5">
          <span class="status-dot online"></span>
          <span class="text-xs text-med-green">{{ summary?.devices?.online ?? 0 }} en ligne</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="flex items-center justify-between">
          <span class="text-xs text-navy-400 font-medium uppercase tracking-wider">Disponibilité</span>
          <ChartBarIcon class="w-4 h-4 text-navy-500" />
        </div>
        <div class="metric-value" :class="availabilityColor">
          {{ summary?.devices?.availability_rate ?? '–' }}%
        </div>
        <div class="text-xs text-navy-500">Taux en temps réel</div>
      </div>

      <div class="stat-card">
        <div class="flex items-center justify-between">
          <span class="text-xs text-navy-400 font-medium uppercase tracking-wider">Alertes ouvertes</span>
          <BellAlertIcon class="w-4 h-4 text-navy-500" />
        </div>
        <div class="metric-value" :class="summary?.alerts?.open_critical > 0 ? 'text-med-red' : 'text-navy-100'">
          {{ summary?.alerts?.open_total ?? '–' }}
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-med-red" v-if="summary?.alerts?.open_critical">
            {{ summary.alerts.open_critical }} critique(s)
          </span>
          <span class="text-xs text-navy-500" v-else>Aucune critique</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="flex items-center justify-between">
          <span class="text-xs text-navy-400 font-medium uppercase tracking-wider">Hors ligne</span>
          <ExclamationTriangleIcon class="w-4 h-4 text-navy-500" />
        </div>
        <div class="metric-value" :class="summary?.devices?.offline > 0 ? 'text-med-orange' : 'text-navy-100'">
          {{ summary?.devices?.offline ?? '–' }}
        </div>
        <div class="text-xs text-navy-500">
          {{ summary?.devices?.maintenance ?? 0 }} en maintenance
        </div>
      </div>
    </div>

    <!-- Main grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Device status grid -->
      <div class="lg:col-span-2 card">
        <div class="card-header flex items-center justify-between">
          <h2 class="text-sm font-semibold text-navy-200">État des appareils</h2>
          <NuxtLink to="/devices" class="text-xs text-med-green hover:underline">Voir tout →</NuxtLink>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
          <DeviceCard
            v-for="device in devices"
            :key="device.id"
            :device="device"
          />
          <div v-if="devicesLoading" class="col-span-2 text-center py-8 text-navy-500 text-sm">
            Chargement...
          </div>
        </div>
      </div>

      <!-- Alertes récentes -->
      <div class="card flex flex-col">
        <div class="card-header flex items-center justify-between">
          <h2 class="text-sm font-semibold text-navy-200">Alertes récentes</h2>
          <NuxtLink to="/alerts" class="text-xs text-med-green hover:underline">Voir tout →</NuxtLink>
        </div>
        <div class="flex-1 overflow-y-auto divide-y" style="border-color: var(--color-border); max-height: 420px">
          <AlertRow
            v-for="alert in summary?.recent_alerts"
            :key="alert.id"
            :alert="alert"
          />
          <div v-if="!summary?.recent_alerts?.length" class="p-6 text-center text-navy-500 text-sm">
            <CheckCircleIcon class="w-8 h-8 mx-auto mb-2 text-med-green opacity-50" />
            Aucune alerte ouverte
          </div>
        </div>
      </div>
    </div>

    <!-- Maintenance à venir -->
    <div class="card" v-if="summary?.upcoming_maintenance?.length">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-navy-200">Maintenance planifiée (7 prochains jours)</h2>
      </div>
      <div class="divide-y" style="border-color: var(--color-border)">
        <div v-for="m in summary.upcoming_maintenance" :key="m.id"
          class="flex items-center gap-4 px-5 py-3 hover:bg-navy-800/50 transition">
          <div class="w-2 h-2 rounded-full" :class="m.days_until <= 1 ? 'bg-med-red' : 'bg-med-yellow'"></div>
          <div class="flex-1">
            <div class="text-sm text-navy-200">{{ m.device_name }}</div>
            <div class="text-xs text-navy-500">{{ m.type_label }}</div>
          </div>
          <div class="text-xs font-mono text-navy-400">{{ m.scheduled_date }}</div>
          <div class="text-xs px-2 py-0.5 rounded-full"
            :class="m.days_until <= 1 ? 'bg-med-red/15 text-med-red' : 'bg-med-yellow/15 text-med-yellow'">
            J-{{ m.days_until }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  CpuChipIcon, ChartBarIcon, BellAlertIcon,
  ExclamationTriangleIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const api = useApi()

const summary = ref<any>(null)
const devices = ref<any[]>([])
const devicesLoading = ref(true)

const availabilityColor = computed(() => {
  const rate = summary.value?.devices?.availability_rate ?? 100
  if (rate >= 90) return 'text-med-green'
  if (rate >= 70) return 'text-med-orange'
  return 'text-med-red'
})

async function loadDashboard() {
  try {
    summary.value = await api.get<any>('/dashboard/summary')
  } catch (e: any) { console.error("[API Error]", e?.response?.status ?? e?.status, e?.data?.message ?? e?.message, e) }
}

async function loadDevices() {
  try {
    devicesLoading.value = true
    devices.value = await api.get<any[]>('/dashboard/devices')
  } catch (e: any) { console.error("[API Error]", e?.response?.status ?? e?.status, e?.data?.message ?? e?.message, e) } finally {
    devicesLoading.value = false
  }
}

// Écouter les mises à jour temps réel
const { $echo } = useNuxtApp()
onMounted(() => {
  loadDashboard()
  loadDevices()

  if ($echo) {
    $echo.channel('devices').listen('.device.metric', (data: any) => {
      const idx = devices.value.findIndex(d => d.serial_number === data.serial_number)
      if (idx !== -1) {
        devices.value[idx] = { ...devices.value[idx], ...data }
      }
    })

    $echo.channel('alerts').listen('.alert.triggered', () => {
      loadDashboard()
    })
  }

  // Refresh toutes les 60s
  const interval = setInterval(() => { loadDashboard(); loadDevices() }, 60000)
  onUnmounted(() => clearInterval(interval))
})
</script>
