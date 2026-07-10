<template>
  <div class="flex items-start gap-3 px-4 py-3 hover:bg-navy-800/30 transition">
    <div class="mt-0.5 flex-shrink-0">
      <div class="w-2 h-2 rounded-full mt-1"
        :class="alert.severity === 'critical' ? 'bg-med-red' : alert.severity === 'warning' ? 'bg-med-orange' : 'bg-med-blue'">
      </div>
    </div>
    <div class="flex-1 min-w-0">
      <div class="text-xs font-medium text-navy-200 truncate">{{ alert.title }}</div>
      <div class="text-xs text-navy-500 mt-0.5">
        {{ alert.device_name }} · {{ alert.device_location }}
      </div>
    </div>
    <div class="text-xs text-navy-600 flex-shrink-0 font-mono">
      {{ alert.created_at }}
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ alert: any }>()
</script>
