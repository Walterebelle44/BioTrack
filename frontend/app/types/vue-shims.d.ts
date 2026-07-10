// Ce fichier déclare les auto-imports Nuxt pour que VS Code/TypeScript les reconnaisse
// sans soulignements rouges

import { Ref, ComputedRef } from 'vue'

declare global {
  // Vue
  const ref: typeof import('vue')['ref']
  const computed: typeof import('vue')['computed']
  const reactive: typeof import('vue')['reactive']
  const watch: typeof import('vue')['watch']
  const watchEffect: typeof import('vue')['watchEffect']
  const onMounted: typeof import('vue')['onMounted']
  const onUnmounted: typeof import('vue')['onUnmounted']
  const onBeforeMount: typeof import('vue')['onBeforeMount']
  const nextTick: typeof import('vue')['nextTick']
  const defineProps: typeof import('vue')['defineProps']
  const defineEmits: typeof import('vue')['defineEmits']
  const defineExpose: typeof import('vue')['defineExpose']
  const withDefaults: typeof import('vue')['withDefaults']

  // Nuxt
  const useRoute: typeof import('vue-router')['useRoute']
  const useRouter: typeof import('vue-router')['useRouter']
  const navigateTo: typeof import('#app')['navigateTo']
  const useRuntimeConfig: typeof import('#app')['useRuntimeConfig']
  const useNuxtApp: typeof import('#app')['useNuxtApp']
  const useFetch: typeof import('#app')['useFetch']
  const useAsyncData: typeof import('#app')['useAsyncData']
  const $fetch: typeof import('ofetch')['$fetch']

  // Pinia
  const useAuthStore: typeof import('~/stores/auth')['useAuthStore']
}

export {}

import Echo from 'laravel-echo'

declare module '#app' {
  interface NuxtApp {
    $echo: Echo
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $echo: Echo
  }
}

export {}