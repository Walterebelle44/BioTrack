<template>
  <div class="space-y-6 animate-fade-up" v-if="device">
    <!-- Header -->
    <div class="flex items-start justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <NuxtLink to="/devices" class="text-navy-500 hover:text-navy-300 transition">
          <ArrowLeftIcon class="w-5 h-5" />
        </NuxtLink>
        <div>
          <div class="flex items-center gap-3 flex-wrap">
            <span class="status-dot" :class="device.status"></span>
            <h1 class="text-xl font-bold text-navy-100">{{ device.name }}</h1>
            <span class="text-xs px-2 py-0.5 rounded-full" :class="statusBadge(device.status)">
              {{ statusLabel(device.status) }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-navy-800 text-navy-400 border border-navy-700">
              {{ device.criticite }}
            </span>
          </div>
          <div class="text-sm text-navy-500 mt-1 font-mono">
            {{ device.serial_number }} · {{ device.location }}
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <button @click="handleSimulate" :disabled="simulating" class="btn-secondary text-xs">
          <span v-if="simulating" class="w-3.5 h-3.5 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
          <BeakerIcon v-else class="w-3.5 h-3.5" />
          Simuler MQTT
        </button>
        <button @click="showMaintenanceModal = true" class="btn-secondary text-xs">
          <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
          Planifier maintenance
        </button>
        <button @click="showEditModal = true" class="btn-primary text-xs">
          <PencilIcon class="w-3.5 h-3.5" />
          Modifier
        </button>
      </div>
    </div>

    <!-- Toast feedback -->
    <div v-if="feedback" class="p-3 rounded-lg text-sm"
      :class="feedback.type === 'success' ? 'bg-med-green/10 border border-med-green/30 text-med-green' : 'bg-med-red/10 border border-med-red/25 text-med-red'">
      {{ feedback.message }}
    </div>

    <!-- Live metrics -->
    <div v-if="liveMetrics.length" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div v-for="metric in liveMetrics" :key="metric.key" class="stat-card">
        <div class="flex items-center justify-between">
          <span class="text-xs text-navy-500 uppercase tracking-wider">{{ metric.label }}</span>
          <span class="text-lg">{{ metric.icon }}</span>
        </div>
        <div class="font-mono text-2xl font-bold" :class="metric.color">
          {{ metric.value ?? '–' }}<span class="text-sm font-normal text-navy-500">{{ metric.unit }}</span>
        </div>
        <div class="text-xs text-navy-600">Seuil: {{ metric.threshold }}</div>
      </div>
    </div>

    <!-- Metrics chart -->
    <div class="card">
      <div class="card-header flex items-center justify-between">
        <h2 class="text-sm font-semibold text-navy-200">Historique des métriques</h2>
        <div class="flex items-center gap-2">
          <select v-model="chartHours" @change="loadMetrics" class="input text-xs py-1.5 w-auto">
            <option value="6">6 heures</option>
            <option value="24">24 heures</option>
            <option value="48">48 heures</option>
            <option value="168">7 jours</option>
          </select>
        </div>
      </div>
      <div class="p-4">
        <div v-if="metricsLoading" class="h-48 flex items-center justify-center text-navy-500 text-sm">
          Chargement du graphique...
        </div>
        <div v-else-if="!hasMetricsData" class="h-48 flex flex-col items-center justify-center text-navy-500 text-sm gap-2">
          <ChartBarIcon class="w-8 h-8 opacity-30" />
          <span>Aucune donnée IoT enregistrée</span>
          <button @click="handleSimulate" class="btn-secondary text-xs mt-1">
            <BeakerIcon class="w-3 h-3" /> Simuler pour générer des données
          </button>
        </div>
        <div v-else style="position:relative; height:200px;">
          <canvas ref="chartCanvas"></canvas>
        </div>
      </div>
    </div>

    <!-- Info + Alerts + Maintenance -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Device info -->
      <div class="card">
        <div class="card-header">
          <h3 class="text-sm font-semibold text-navy-200">Informations</h3>
        </div>
        <div class="p-4 space-y-3">
          <InfoRow label="Fabricant"         :value="device.manufacturer" />
          <InfoRow label="Modèle"            :value="device.model" />
          <InfoRow label="Type"              :value="device.type_nom" />
          <InfoRow label="Service"           :value="device.service_nom ?? device.building" />
          <InfoRow label="Localisation"      :value="device.location" />
          <InfoRow label="Fournisseur"       :value="device.fournisseur" />
          <InfoRow label="Acquisition"       :value="device.acquisition_date" />
          <InfoRow label="Fin de garantie"   :value="device.warranty_end" />
          <InfoRow label="Coût"              :value="device.cost ? Number(device.cost).toLocaleString('fr-FR') + ' XAF' : undefined" />
          <div v-if="device.mqtt_topic" class="pt-2 border-t" style="border-color:var(--color-border)">
            <div class="text-xs text-navy-500 mb-1">Topic MQTT</div>
            <div class="font-mono text-xs text-navy-400 bg-navy-950 rounded p-2 break-all">
              {{ device.mqtt_topic }}
            </div>
          </div>
        </div>
      </div>

      <!-- Open alerts -->
      <div class="card">
        <div class="card-header flex items-center justify-between">
          <h3 class="text-sm font-semibold text-navy-200">Alertes ouvertes</h3>
          <span class="text-xs px-2 py-0.5 rounded-full"
            :class="(device.open_alerts?.length ?? 0) > 0 ? 'bg-med-red/15 text-med-red' : 'bg-navy-800 text-navy-500'">
            {{ device.open_alerts?.length ?? 0 }}
          </span>
        </div>
        <div class="divide-y" style="border-color:var(--color-border)">
          <div v-if="!device.open_alerts?.length" class="p-6 text-center text-navy-500 text-sm">
            <CheckCircleIcon class="w-8 h-8 mx-auto mb-2 text-med-green opacity-50" />
            Aucune alerte ouverte
          </div>
          <div v-for="a in device.open_alerts" :key="a.id" class="px-4 py-3 flex items-start gap-3">
            <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
              :class="a.severity === 'critical' || a.severity === 'critique' ? 'bg-med-red' : 'bg-med-orange'"></div>
            <div>
              <div class="text-xs font-medium text-navy-200">{{ a.title ?? a.titre }}</div>
              <div class="text-xs text-navy-600 mt-0.5">{{ formatRelativeTime(a.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Maintenance history -->
      <div class="card">
        <div class="card-header flex items-center justify-between">
          <h3 class="text-sm font-semibold text-navy-200">Maintenances récentes</h3>
          <button @click="showMaintenanceModal = true" class="text-xs text-med-green hover:underline flex items-center gap-1">
            <PlusIcon class="w-3 h-3" /> Planifier
          </button>
        </div>
        <div class="divide-y" style="border-color:var(--color-border)">
          <div v-if="!device.recent_maintenance?.length" class="p-6 text-center text-navy-500 text-sm">
            Aucune maintenance enregistrée
          </div>
          <div v-for="m in device.recent_maintenance" :key="m.id" class="px-4 py-3">
            <div class="flex items-center justify-between mb-0.5">
              <span class="text-xs font-medium text-navy-200">{{ m.type_label }}</span>
              <span class="text-xs px-1.5 py-0.5 rounded font-mono"
                :class="m.status === 'terminee' ? 'text-med-green' : m.status === 'en_cours' ? 'text-med-blue' : 'text-med-yellow'">
                {{ statusMaintLabels[m.status] ?? m.status }}
              </span>
            </div>
            <div class="text-xs text-navy-600">{{ m.scheduled_date }}</div>
            <div v-if="m.cost && m.cost > 0" class="text-xs text-navy-500 font-mono">
              {{ Number(m.cost).toLocaleString('fr-FR') }} XAF
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="flex items-center justify-center h-64 text-navy-500">
    <div v-if="loading" class="flex items-center gap-2">
      <span class="w-5 h-5 border-2 border-navy-500 border-t-transparent rounded-full animate-spin"></span>
      Chargement...
    </div>
    <div v-else class="text-center">
      <p>Appareil introuvable.</p>
      <NuxtLink to="/devices" class="text-med-green text-sm hover:underline mt-2 inline-block">← Retour à la liste</NuxtLink>
    </div>
  </div>

  <!-- Edit modal -->
  <DeviceModal v-if="showEditModal" :device="device" @close="showEditModal = false" @saved="onDeviceUpdated" />

  <!-- Maintenance modal -->
  <MaintenanceModal
    v-if="showMaintenanceModal"
    :device-id="device?.id"
    :device-name="device?.name"
    @close="showMaintenanceModal = false"
    @saved="onMaintenanceSaved"
  />
</template>

<script setup lang="ts">
import {
  ArrowLeftIcon, BeakerIcon, PencilIcon,
  CheckCircleIcon, ChartBarIcon, WrenchScrewdriverIcon, PlusIcon
} from '@heroicons/vue/24/outline'
import Chart from 'chart.js/auto'
import { useApi } from '~/composables/useApi'

const route = useRoute()
const api   = useApi()

const device               = ref<any>(null)
const loading              = ref(true)
const metricsLoading       = ref(false)
const hasMetricsData       = ref(false)
const chartHours           = ref('24')
const chartCanvas          = ref<HTMLCanvasElement | null>(null)
const showEditModal        = ref(false)
const showMaintenanceModal = ref(false)
const simulating           = ref(false)
const feedback             = ref<{ type: string; message: string } | null>(null)
let chart: Chart | null    = null

const statusMaintLabels: Record<string, string> = {
  planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée',
}

function statusLabel(s: string) {
  return { online: 'En ligne', offline: 'Hors ligne', alert: 'Alerte', maintenance: 'Maintenance' }[s] ?? s
}
function statusBadge(s: string) {
  return { online: 'badge-online', offline: 'badge-offline', alert: 'badge-alert', maintenance: 'badge-maintenance' }[s] ?? 'badge-offline'
}

const liveMetrics = computed(() => {
  if (!device.value) return []
  const m = device.value.last_metrics ?? {}
  const thresholds = device.value.profile?.alert_thresholds ?? {}
  const items: any[] = []

  if (m.temperature !== undefined) {
    const t = thresholds.temperature ?? {}
    const ok = (t.min === undefined || m.temperature >= t.min) && (t.max === undefined || m.temperature <= t.max)
    items.push({ key: 'temperature', label: 'Température', icon: '🌡️',
      value: m.temperature, unit: '°C',
      color: ok ? 'text-med-green' : 'text-med-red',
      threshold: t.min !== undefined ? `${t.min}–${t.max}°C` : '–' })
  }
  if (m.vibrations !== undefined) {
    items.push({ key: 'vibrations', label: 'Vibrations', icon: '📳',
      value: m.vibrations, unit: ' g',
      color: m.vibrations > 0.5 ? 'text-med-red' : m.vibrations > 0.2 ? 'text-med-orange' : 'text-med-green',
      threshold: '< 0.2 g' })
  }
  const bat = m.battery ?? m.battery_level
  if (bat !== undefined) {
    const tBat = thresholds.battery ?? {}
    items.push({ key: 'battery', label: 'Batterie', icon: '🔋',
      value: bat, unit: '%',
      color: bat < (tBat.min ?? 15) ? 'text-med-red' : bat < 30 ? 'text-med-orange' : 'text-med-green',
      threshold: `Min ${tBat.min ?? 15}%` })
  }
  if (m.utilization !== undefined) {
    items.push({ key: 'utilization', label: 'Utilisation', icon: '📊',
      value: m.utilization, unit: '%',
      color: 'text-med-blue', threshold: '–' })
  }
  return items
})

async function loadDevice() {
  loading.value = true
  try {
    device.value = await api.get<any>(`/devices/${route.params.id}`)
  } catch { device.value = null }
  finally { loading.value = false }
}

async function loadMetrics() {
  if (!device.value) return
  metricsLoading.value = true
  if (chart) { chart.destroy(); chart = null }
  try {
    const data = await api.get<any>(`/devices/${device.value.id}/metrics`, { hours: chartHours.value })
    const metrics = Array.isArray(data?.metrics) ? data.metrics : []
    hasMetricsData.value = metrics.length > 0
    if (metrics.length > 0) {
      await nextTick()
      renderChart(metrics)
    }
  } finally {
    metricsLoading.value = false
  }
}

function renderChart(metrics: any[]) {
  if (!chartCanvas.value) return
  if (chart) { chart.destroy(); chart = null }

  const labels = metrics.map(m =>
    new Date(m.recorded_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  )
  const datasets: any[] = []

  if (metrics.some(m => m.temperature != null)) {
    datasets.push({
      label: 'Température (°C)',
      data: metrics.map(m => m.temperature),
      borderColor: '#00d68f', backgroundColor: 'rgba(0,214,143,0.07)',
      fill: true, tension: 0.4, pointRadius: 0,
    })
  }
  if (metrics.some(m => m.vibrations != null)) {
    datasets.push({
      label: 'Vibrations (g)',
      data: metrics.map(m => m.vibrations),
      borderColor: '#ff9f43', backgroundColor: 'rgba(255,159,67,0.07)',
      fill: true, tension: 0.4, pointRadius: 0,
    })
  }
  if (metrics.some(m => m.battery_level != null)) {
    datasets.push({
      label: 'Batterie (%)',
      data: metrics.map(m => m.battery_level),
      borderColor: '#54a0ff', backgroundColor: 'rgba(84,160,255,0.07)',
      fill: true, tension: 0.4, pointRadius: 0,
    })
  }
  if (!datasets.length) return

  chart = new Chart(chartCanvas.value, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: 'rgba(180,202,228,0.6)', font: { size: 11 }, boxWidth: 12 } } },
      scales: {
        x: { ticks: { color: 'rgba(180,202,228,0.35)', maxTicksLimit: 8, font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
        y: { ticks: { color: 'rgba(180,202,228,0.35)', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
      },
    },
  })
}

async function handleSimulate() {
  if (!device.value || simulating.value) return
  simulating.value = true
  feedback.value   = null
  try {
    await api.post(`/devices/${device.value.id}/simulate`, {})
    feedback.value = { type: 'success', message: 'Simulation MQTT envoyée. Données mises à jour.' }
    setTimeout(async () => {
      await loadDevice()
      await loadMetrics()
      feedback.value = null
    }, 1500)
  } catch (e: any) {
    feedback.value = { type: 'error', message: e?.data?.data?.message ?? 'Erreur de simulation' }
  } finally {
    simulating.value = false
  }
}

async function onDeviceUpdated() {
  showEditModal.value = false
  await loadDevice()
  feedback.value = { type: 'success', message: 'Équipement mis à jour.' }
  setTimeout(() => { feedback.value = null }, 3000)
}

async function onMaintenanceSaved() {
  showMaintenanceModal.value = false
  await loadDevice()
  feedback.value = { type: 'success', message: 'Intervention planifiée avec succès.' }
  setTimeout(() => { feedback.value = null }, 3000)
}

function formatRelativeTime(iso: string): string {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'À l\'instant'
  if (mins < 60) return `Il y a ${mins} min`
  return `Il y a ${Math.floor(mins / 60)}h`
}

const { $echo } = useNuxtApp()
onMounted(async () => {
  await loadDevice()
  await loadMetrics()
  if ($echo && device.value) {
    $echo.channel(`device.${device.value.serial_number}`)
      .listen('.device.metric', (data: any) => {
        if (device.value) {
          device.value.status      = data.status
          device.value.last_metrics = data.metrics
          device.value.last_seen_at = data.recorded_at
        }
      })
  }
})
onUnmounted(() => { if (chart) chart.destroy() })
</script>
