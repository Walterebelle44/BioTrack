<template>
  <div class="flex items-center justify-between py-1">
    <span class="text-xs text-navy-500">{{ label }}</span>
    <span class="text-xs font-medium text-navy-300">{{ value ?? '–' }}</span>
  </div>
</template>

<script setup lang="ts">
defineProps<{ label: string; value?: string | null }>()
</script>
