<template>
  <NuxtLink :to="`/devices/${device.id}`"
    class="block p-4 rounded-xl border hover:border-navy-600 transition-all duration-200 group cursor-pointer"
    :class="cardBgClass"
    style="border-color: inherit"
  >
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center gap-2">
        <span class="status-dot" :class="device.status"></span>
        <span class="text-xs font-mono text-navy-500">{{ device.serial_number }}</span>
      </div>
      <span class="text-xs px-2 py-0.5 rounded-full" :class="statusBadgeClass">
        {{ statusLabel }}
      </span>
    </div>

    <div class="mb-3">
      <div class="text-sm font-semibold text-navy-100 group-hover:text-white transition">{{ device.name }}</div>
      <div class="text-xs text-navy-500 flex items-center gap-1 mt-0.5">
        <MapPinIcon class="w-3 h-3" />
        {{ device.location }}
      </div>
    </div>

    <!-- Métriques -->
    <div class="grid grid-cols-2 gap-2" v-if="device.last_metrics">
      <div v-if="device.last_metrics.temperature !== undefined" class="bg-navy-950/50 rounded-lg p-2">
        <div class="text-xs text-navy-500">Température</div>
        <div class="text-sm font-mono font-bold" :class="tempColor">
          {{ device.last_metrics.temperature }}°C
        </div>
      </div>
      <div v-if="device.last_metrics.battery !== undefined || device.last_metrics.battery_level !== undefined" class="bg-navy-950/50 rounded-lg p-2">
        <div class="text-xs text-navy-500">Batterie</div>
        <div class="text-sm font-mono font-bold" :class="batteryColor">
          {{ device.last_metrics.battery ?? device.last_metrics.battery_level }}%
        </div>
      </div>
    </div>

    <div v-if="device.last_seen_at" class="text-xs text-navy-600 mt-2 font-mono">
      {{ formatRelativeTime(device.last_seen_at) }}
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps<{ device: any }>()

const statusLabel = computed(() => ({
  online: 'En ligne',
  offline: 'Hors ligne',
  alert: 'Alerte',
  maintenance: 'Maintenance',
  unknown: 'Inconnu',
}[props.device.status] ?? props.device.status))

const cardBgClass = computed(() => ({
  online: 'bg-navy-900/60',
  offline: 'bg-navy-950/40',
  alert: 'bg-med-red/5 border-med-red/20',
  maintenance: 'bg-med-yellow/5 border-med-yellow/20',
  unknown: 'bg-navy-900/40',
}[props.device.status] ?? ''))

const statusBadgeClass = computed(() => ({
  online: 'badge-online',
  offline: 'badge-offline',
  alert: 'badge-alert',
  maintenance: 'badge-maintenance',
  unknown: 'badge-offline',
}[props.device.status] ?? ''))

const tempColor = computed(() => {
  const t = props.device.last_metrics?.temperature
  if (t === undefined) return 'text-navy-300'
  const thresholds = props.device.profile?.alert_thresholds?.temperature
  if (!thresholds) return 'text-navy-300'
  if ((thresholds.max && t > thresholds.max) || (thresholds.min && t < thresholds.min)) return 'text-med-red'
  return 'text-med-green'
})

const batteryColor = computed(() => {
  const b = props.device.last_metrics?.battery ?? props.device.last_metrics?.battery_level
  if (b === undefined) return 'text-navy-300'
  if (b < 15) return 'text-med-red'
  if (b < 30) return 'text-med-orange'
  return 'text-med-green'
})

function formatRelativeTime(iso: string): string {
  const date = new Date(iso)
  const diff = Date.now() - date.getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'À l\'instant'
  if (mins < 60) return `Il y a ${mins} min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `Il y a ${hours}h`
  return `Il y a ${Math.floor(hours / 24)}j`
}
</script>
