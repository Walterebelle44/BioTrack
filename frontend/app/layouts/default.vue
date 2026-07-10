<template>
  <div class="min-h-screen bg-navy-950 bg-grid flex">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 border-r bg-navy-950/95 backdrop-blur-sm flex flex-col fixed h-full z-30"
      style="border-color: var(--color-border)">
      <!-- Logo -->
      <div class="p-5 border-b" style="border-color: var(--color-border)">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-med-green/20 border border-med-green/30 flex items-center justify-center">
            <span class="text-med-green font-bold text-sm font-mono">B+</span>
          </div>
          <div>
            <div class="font-bold text-navy-100 text-sm">BioTrack IoT</div>
            <div class="text-xs text-navy-400">v1.0.0 · Douala</div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <NuxtLink to="/" class="nav-link" :class="{ active: $route.path === '/' }">
          <HomeIcon class="w-4 h-4" />
          Tableau de bord
        </NuxtLink>

        <NuxtLink to="/devices" class="nav-link" :class="{ active: $route.path.startsWith('/devices') }">
          <CpuChipIcon class="w-4 h-4" />
          <span>Appareils</span>
          <span v-if="offlineCount > 0" class="ml-auto text-xs px-1.5 py-0.5 rounded bg-med-red/20 text-med-red">
            {{ offlineCount }}
          </span>
        </NuxtLink>

        <NuxtLink to="/alerts" class="nav-link" :class="{ active: $route.path.startsWith('/alerts') }">
          <BellAlertIcon class="w-4 h-4" />
          <span>Alertes</span>
          <span v-if="openAlertsCount > 0" class="ml-auto text-xs px-1.5 py-0.5 rounded bg-med-red/20 text-med-red font-bold">
            {{ openAlertsCount }}
          </span>
        </NuxtLink>

        <NuxtLink to="/maintenance" class="nav-link" :class="{ active: $route.path.startsWith('/maintenance') }">
          <WrenchScrewdriverIcon class="w-4 h-4" />
          Maintenance
        </NuxtLink>

        <NuxtLink to="/analytics" class="nav-link" :class="{ active: $route.path.startsWith('/analytics') }">
          <ChartBarIcon class="w-4 h-4" />
          Analytiques & ROI
        </NuxtLink>

        <NuxtLink to="/messages" class="nav-link" :class="{ active: $route.path.startsWith('/messages') }">
          <ChatBubbleLeftRightIcon class="w-4 h-4" />
          <span>Messages</span>
          <span v-if="unreadMessages > 0" class="ml-auto text-xs px-1.5 py-0.5 rounded bg-med-green/20 text-med-green font-bold">
            {{ unreadMessages }}
          </span>
        </NuxtLink>

        <!-- Section Admin uniquement pour admin -->
        <template v-if="auth.user?.role === 'admin'">
          <div class="pt-4 pb-1 px-3">
            <div class="text-xs font-semibold text-navy-600 uppercase tracking-wider">Administration</div>
          </div>

          <NuxtLink to="/admin" class="nav-link" :class="{ active: $route.path.startsWith('/admin') }">
            <ShieldCheckIcon class="w-4 h-4" />
            Panneau admin
          </NuxtLink>

          <NuxtLink to="/settings" class="nav-link" :class="{ active: $route.path.startsWith('/settings') }">
            <Cog6ToothIcon class="w-4 h-4" />
            Paramètres
          </NuxtLink>
        </template>

        <!-- Paramètres pour tous les autres rôles -->
        <template v-else>
          <div class="pt-4 pb-1 px-3">
            <div class="text-xs font-semibold text-navy-600 uppercase tracking-wider">Compte</div>
          </div>
          <NuxtLink to="/settings" class="nav-link" :class="{ active: $route.path.startsWith('/settings') }">
            <Cog6ToothIcon class="w-4 h-4" />
            Paramètres
          </NuxtLink>
        </template>
      </nav>

      <!-- User Info -->
      <div class="p-3 border-t" style="border-color: var(--color-border)">
        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-navy-800 transition">
          <div class="w-8 h-8 rounded-full bg-med-green/20 border border-med-green/30 flex items-center justify-center flex-shrink-0">
            <span class="text-med-green text-xs font-bold">
              {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-xs font-medium text-navy-200 truncate">{{ auth.user?.name }}</div>
            <div class="text-xs text-navy-500 truncate">{{ auth.user?.role_label ?? roleLabel(auth.user?.role) }}</div>
          </div>
          <button @click="auth.logout()" class="text-navy-500 hover:text-med-red transition p-1" title="Se déconnecter">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">
      <!-- Topbar -->
      <header class="h-14 border-b bg-navy-950/80 backdrop-blur-sm sticky top-0 z-20 flex items-center px-6 gap-4"
        style="border-color: var(--color-border)">
        <div class="flex-1">
          <h1 class="text-sm font-semibold text-navy-200">{{ pageTitle }}</h1>
        </div>
        <div class="flex items-center gap-2 text-xs text-navy-400">
          <span class="status-dot online"></span>
          <span>Temps réel</span>
        </div>
        <div class="text-xs font-mono text-navy-500 hidden md:block">{{ currentTime }}</div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>

    <!-- Toast container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
  </div>
</template>

<script setup lang="ts">
import {
  HomeIcon, CpuChipIcon, BellAlertIcon, WrenchScrewdriverIcon,
  ChartBarIcon, Cog6ToothIcon, ArrowRightOnRectangleIcon,
  ShieldCheckIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const auth  = useAuthStore()
const route = useRoute()

const openAlertsCount = ref(0)
const offlineCount    = ref(0)
const unreadMessages  = ref(0)
const currentTime     = ref('')

const pageTitles: Record<string, string> = {
  '/':           'Tableau de bord',
  '/devices':    'Gestion des appareils',
  '/alerts':     'Centre des alertes',
  '/maintenance':'Maintenance',
  '/analytics':  'Analytiques & ROI',
  '/settings':   'Paramètres',
  '/messages':   'Messagerie',
  '/admin':      'Panneau d\'administration',
}

const pageTitle = computed(() => {
  for (const [path, title] of Object.entries(pageTitles)) {
    if (route.path === path || (path !== '/' && route.path.startsWith(path))) return title
  }
  return 'BioTrack IoT'
})

function roleLabel(r: string) {
  return { admin: 'Administrateur', directeur: 'Directeur', technicien: 'Technicien', responsable_si: 'Resp. SI' }[r ?? ''] ?? r
}

function updateTime() {
  currentTime.value = new Date().toLocaleTimeString('fr-FR', {
    hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Africa/Douala',
  })
}

async function loadStats() {
  try {
    const api  = useApi()
    const data = await api.get<any>('/dashboard/summary')
    openAlertsCount.value = data.alerts?.open_total ?? 0
    offlineCount.value    = data.devices?.offline    ?? 0
    // Charger messages non lus
    try {
      const msgs = await api.get<any>('/conversations')
      unreadMessages.value = msgs?.total_unread ?? 0
    } catch {}
  } catch {}
}

const { $echo } = useNuxtApp()
onMounted(() => {
  updateTime()
  setInterval(updateTime, 1000)
  loadStats()
  setInterval(loadStats, 30000)

  if ($echo) {
    $echo.channel('alerts').listen('.alert.triggered', (data: any) => {
      openAlertsCount.value++
      showToast(data)
    })
  }
})

function showToast(alert: any) {
  const container = document.getElementById('toast-container')
  if (!container) return
  const toast = document.createElement('div')
  const cls = alert.severity === 'critical' ? 'border-med-red/40 bg-med-red/10' : 'border-med-orange/40 bg-med-orange/10'
  toast.className = `toast ${cls}`
  toast.innerHTML = `
    <div class="flex items-start gap-3">
      <div class="text-lg">${alert.severity === 'critical' ? '🚨' : '⚠️'}</div>
      <div>
        <div class="text-sm font-semibold text-navy-100">${alert.title ?? ''}</div>
        <div class="text-xs text-navy-400 mt-0.5">${alert.device?.name ?? ''} · ${alert.device?.location ?? ''}</div>
      </div>
    </div>
  `
  container.appendChild(toast)
  setTimeout(() => toast.remove(), 5000)
}
</script>
