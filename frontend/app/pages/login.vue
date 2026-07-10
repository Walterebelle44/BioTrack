<template>
  <div class="min-h-screen bg-navy-950 bg-grid flex items-center justify-center p-4">
    <!-- Background glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-med-green/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-navy-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-med-green/20 border border-med-green/30 mb-4">
          <span class="text-med-green font-bold text-2xl font-mono">B+</span>
        </div>
        <h1 class="text-2xl font-bold text-navy-50">BioTrack IoT</h1>
        <p class="text-navy-400 text-sm mt-1">Plateforme de monitoring biomédical</p>
      </div>

      <!-- Card -->
      <div class="card p-8">
        <h2 class="text-lg font-semibold text-navy-100 mb-6">Connexion</h2>

        <div class="space-y-4">
          <div>
            <label class="label">Adresse e-mail</label>
            <input v-model="form.email" type="email" class="input" placeholder="admin@biotrack.cm" @keydown.enter="handleLogin" />
          </div>

          <div>
            <label class="label">Mot de passe</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="input pr-10"
                placeholder="••••••••"
                @keydown.enter="handleLogin"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-navy-500 hover:text-navy-300">
                <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div v-if="error" class="p-3 rounded-lg bg-med-red/10 border border-med-red/25 text-med-red text-sm">
            {{ error }}
          </div>

          <button @click="handleLogin" class="btn-primary w-full justify-center py-3" :disabled="loading">
            <span v-if="loading" class="w-4 h-4 border-2 border-navy-800 border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Se connecter</span>
          </button>
        </div>

        <!-- Demo accounts -->
        <div class="mt-6 pt-6 border-t" style="border-color: var(--color-border)">
          <p class="text-xs text-navy-500 mb-3 text-center">Comptes de démonstration</p>
          <div class="grid grid-cols-2 gap-2">
            <button v-for="demo in demoAccounts" :key="demo.email"
              @click="fillDemo(demo)"
              class="text-left p-2.5 rounded-lg border text-xs hover:bg-navy-800 transition-colors"
              style="border-color: var(--color-border)">
              <div class="font-medium text-navy-300">{{ demo.label }}</div>
              <div class="text-navy-500 truncate">{{ demo.email }}</div>
            </button>
          </div>
        </div>
      </div>

      <p class="text-center text-xs text-navy-600 mt-6">© 2026 BioTrack IoT · Douala, Cameroun</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

definePageMeta({ layout: false })

const auth   = useAuthStore()
const router = useRouter()

const form         = reactive({ email: '', password: 'password' })
const loading      = ref(false)
const error        = ref('')
const showPassword = ref(false)

const demoAccounts = [
  { label: 'Administrateur',   email: 'admin@biotrack.cm' },
  { label: 'Directeur',        email: 'directeur@biotrack.cm' },
  { label: 'Technicien',       email: 'tech@biotrack.cm' },
  { label: 'Resp. SI',         email: 'si@biotrack.cm' },
]

function fillDemo(demo: any) {
  form.email    = demo.email
  form.password = 'password'
}

async function handleLogin() {
  if (!form.email || !form.password) { error.value = 'Veuillez remplir tous les champs.'; return }
  loading.value = true
  error.value   = ''
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (e: any) {
    error.value = e?.data?.data?.message ?? e?.data?.message ?? 'Identifiants incorrects.'
  } finally {
    loading.value = false
  }
}
</script>
