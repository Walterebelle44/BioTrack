<template>
  <div class="space-y-6 animate-fade-up max-w-2xl">
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-navy-200">Mon profil</h2>
      </div>
      <div class="p-5 space-y-4">
        <div class="flex items-center gap-4 pb-4 border-b" style="border-color:var(--color-border)">
          <div class="w-14 h-14 rounded-2xl bg-med-green/20 border border-med-green/30 flex items-center justify-center">
            <span class="text-med-green font-bold text-xl">{{ auth.user?.name?.charAt(0) }}</span>
          </div>
          <div>
            <div class="font-semibold text-navy-100">{{ auth.user?.name }}</div>
            <div class="text-sm text-navy-500">{{ auth.user?.email }}</div>
            <div class="text-xs text-navy-600 mt-0.5">{{ auth.user?.role_label }}</div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Nom complet</label>
            <input :value="auth.user?.name" class="input" readonly />
          </div>
          <div>
            <label class="label">Service</label>
            <input :value="auth.user?.service" class="input" readonly />
          </div>
          <div class="col-span-2">
            <label class="label">Email</label>
            <input :value="auth.user?.email" class="input" readonly />
          </div>
        </div>
      </div>
    </div>

    <!-- Changer mot de passe -->
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-navy-200">Changer le mot de passe</h2>
      </div>
      <form @submit.prevent="changePassword" class="p-5 space-y-4">
        <div>
          <label class="label">Mot de passe actuel</label>
          <input v-model="pwForm.current" type="password" class="input" />
        </div>
        <div>
          <label class="label">Nouveau mot de passe</label>
          <input v-model="pwForm.password" type="password" class="input" />
        </div>
        <div>
          <label class="label">Confirmation</label>
          <input v-model="pwForm.password_confirmation" type="password" class="input" />
        </div>
        <div v-if="pwSuccess" class="text-xs text-med-green">✓ Mot de passe mis à jour.</div>
        <div v-if="pwError" class="text-xs text-med-red">{{ pwError }}</div>
        <button type="submit" class="btn-primary text-xs">Mettre à jour</button>
      </form>
    </div>

    <!-- Infos système -->
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-navy-200">Informations système</h2>
      </div>
      <div class="p-5 space-y-3">
        <div class="flex justify-between text-sm">
          <span class="text-navy-500">Version</span>
          <span class="font-mono text-navy-300">v1.0.0</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-navy-500">Stack</span>
          <span class="font-mono text-navy-300">Nuxt 3 + Laravel 11</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-navy-500">Protocole IoT</span>
          <span class="font-mono text-navy-300">MQTT v3.1.1</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-navy-500">WebSocket</span>
          <span class="font-mono text-navy-300">Laravel Reverb</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-navy-500">Déploiement</span>
          <span class="font-mono text-navy-300">Douala, Cameroun</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useApi } from '~/composables/useApi'
const auth = useAuthStore()
const api = useApi()

const pwForm = reactive({ current: '', password: '', password_confirmation: '' })
const pwSuccess = ref(false)
const pwError = ref('')

async function changePassword() {
  pwError.value = ''
  pwSuccess.value = false
  try {
    await api.post('/auth/password', {
      current_password: pwForm.current,
      password: pwForm.password,
      password_confirmation: pwForm.password_confirmation,
    })
    pwSuccess.value = true
    pwForm.current = ''
    pwForm.password = ''
    pwForm.password_confirmation = ''
  } catch (e: any) {
    pwError.value = e?.data?.message ?? 'Erreur lors de la mise à jour'
  }
}
</script>
