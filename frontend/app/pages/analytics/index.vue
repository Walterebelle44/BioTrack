<template>
  <div class="space-y-6 animate-fade-up">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- ROI Calculator -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-navy-200">Calculateur ROI</h2>
        </div>
        <div class="p-5" v-if="roi">
          <div class="space-y-4">
            <div class="flex justify-between py-2 border-b text-sm" style="border-color:var(--color-border)">
              <span class="text-navy-400">Appareils surveillés</span>
              <span class="font-mono text-navy-200">{{ roi.total_devices }}</span>
            </div>
            <div class="flex justify-between py-2 border-b text-sm" style="border-color:var(--color-border)">
              <span class="text-navy-400">Coût annuel abonnement</span>
              <span class="font-mono text-navy-200">{{ formatXAF(roi.annual_subscription_xaf) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b text-sm" style="border-color:var(--color-border)">
              <span class="text-navy-400">Coût installation</span>
              <span class="font-mono text-navy-200">{{ formatXAF(roi.installation_cost_xaf) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b text-sm font-medium" style="border-color:var(--color-border)">
              <span class="text-navy-300">Coût total année 1</span>
              <span class="font-mono text-med-orange">{{ formatXAF(roi.total_cost_year_1) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b text-sm" style="border-color:var(--color-border)">
              <span class="text-navy-400">Pannes prévenues</span>
              <span class="font-mono text-navy-200">{{ roi.prevented_failures }}</span>
            </div>
            <div class="flex justify-between py-2 border-b text-sm" style="border-color:var(--color-border)">
              <span class="text-navy-400">Économies estimées pannes</span>
              <span class="font-mono text-med-green">{{ formatXAF(roi.estimated_savings_xaf) }}</span>
            </div>
            <div class="p-4 rounded-xl bg-med-green/10 border border-med-green/20">
              <div class="text-xs text-navy-400 mb-1">ROI estimé</div>
              <div class="text-3xl font-bold font-mono" :class="roi.roi_percent >= 0 ? 'text-med-green' : 'text-med-red'">
                {{ roi.roi_percent >= 0 ? '+' : '' }}{{ roi.roi_percent }}%
              </div>
              <div class="text-xs text-navy-500 mt-1">Sur la première année d'exploitation</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerts Timeline Chart -->
      <div class="card">
        <div class="card-header flex items-center justify-between">
          <h2 class="text-sm font-semibold text-navy-200">Alertes sur 7 jours</h2>
        </div>
        <div class="p-4">
          <canvas ref="timelineCanvas" height="220"></canvas>
        </div>
      </div>
    </div>

    <!-- Disponibilité par appareil -->
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-navy-200">Disponibilité des appareils</h2>
      </div>
      <div class="p-4 space-y-3">
        <div v-for="device in devices" :key="device.id" class="flex items-center gap-4">
          <div class="flex items-center gap-2 w-48 flex-shrink-0">
            <span class="status-dot" :class="device.status"></span>
            <span class="text-xs text-navy-300 truncate">{{ device.name }}</span>
          </div>
          <div class="flex-1 bg-navy-800 rounded-full h-2">
            <div
              class="h-2 rounded-full transition-all duration-1000"
              :class="device.status === 'online' ? 'bg-med-green' : device.status === 'alert' ? 'bg-med-red' : 'bg-gray-500'"
              :style="{ width: `${device.status === 'online' ? 98 : device.status === 'maintenance' ? 0 : 45}%` }"
            ></div>
          </div>
          <div class="text-xs font-mono text-navy-400 w-12 text-right">
            {{ device.status === 'online' ? '98%' : device.status === 'offline' ? '0%' : device.status === 'alert' ? '45%' : '–' }}
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full w-24 text-center"
            :class="statusBadge(device.status)">
            {{ statusLabel(device.status) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Chart from 'chart.js/auto'
import { useApi } from '~/composables/useApi'

const api = useApi()
const roi = ref<any>(null)
const devices = ref<any[]>([])
const timelineCanvas = ref<HTMLCanvasElement | null>(null)
let chart: Chart | null = null

function formatXAF(n: number): string {
  return new Intl.NumberFormat('fr-FR').format(n) + ' XAF'
}

function statusLabel(s: string) {
  return { online: 'En ligne', offline: 'Hors ligne', alert: 'Alerte', maintenance: 'Maintenance' }[s] ?? s
}

function statusBadge(s: string) {
  return { online: 'badge-online', offline: 'badge-offline', alert: 'badge-alert', maintenance: 'badge-maintenance' }[s] ?? 'badge-offline'
}

async function load() {
  const [roiData, devicesData, timelineData] = await Promise.all([
    api.get<any>('/dashboard/roi'),
    api.get<any[]>('/dashboard/devices'),
    api.get<any[]>('/dashboard/alerts-timeline'),
  ])
  roi.value = roiData
  devices.value = devicesData
  renderTimeline(timelineData)
}

function renderTimeline(data: any[]) {
  if (!timelineCanvas.value) return
  if (chart) chart.destroy()

  // Group by date
  const dateMap: Record<string, { critical: number; warning: number }> = {}
  data.forEach((d: any) => {
    data.forEach((d: any) => {
  if (!dateMap[d.date]) dateMap[d.date] = { critical: 0, warning: 0 }
  
  const entry = dateMap[d.date]!  // Le "!" dit à TS que c'est garanti non-undefined
  if (d.severity === 'critical') entry.critical = d.count
  if (d.severity === 'warning') entry.warning = d.count
})
  })

  const labels = Object.keys(dateMap).sort()

  chart = new Chart(timelineCanvas.value, {
    type: 'bar',
    data: {
      labels: labels.map(l => new Date(l).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
      datasets: [
        {
          label: 'Critiques',
          data: labels.map(l => dateMap[l]?.critical ?? 0),
          backgroundColor: 'rgba(255,69,96,0.7)',
          borderRadius: 4,
        },
        {
          label: 'Avertissements',
          data: labels.map(l => dateMap[l]?.warning ?? 0),
          backgroundColor: 'rgba(255,140,66,0.7)',
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { labels: { color: 'rgba(232,237,245,0.5)', font: { size: 11 }, boxWidth: 12 } },
      },
      scales: {
        x: {
          ticks: { color: 'rgba(232,237,245,0.3)', font: { size: 10 } },
          grid: { display: false },
          stacked: true,
        },
        y: {
          ticks: { color: 'rgba(232,237,245,0.3)', font: { size: 10 }, stepSize: 1 },
          grid: { color: 'rgba(255,255,255,0.04)' },
          stacked: true,
        },
      },
    },
  })
}

onMounted(load)
onUnmounted(() => { if (chart) chart.destroy() })
</script>
