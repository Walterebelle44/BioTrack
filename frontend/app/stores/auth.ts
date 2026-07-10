import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

function getApiBase(): string {
  try {
    const config = useRuntimeConfig()
    const base = config.public.apiBase as string
    if (base && base.startsWith('http')) return base
  } catch {}
  if (import.meta.client) {
    const win = window as any
    const base = win.__NUXT__?.config?.public?.apiBase
    if (base && base.startsWith('http')) return base
  }
  return 'http://localhost:8000/api'
}

export const useAuthStore = defineStore('auth', () => {
  const user  = ref<any>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  function init() {
    if (import.meta.client) {
      token.value = localStorage.getItem('biotrack_token')
      const u = localStorage.getItem('biotrack_user')
      if (u) { try { user.value = JSON.parse(u) } catch {} }
    }
  }

  async function login(email: string, password: string) {
    const base = getApiBase()
    const res = await $fetch<any>(`${base}/auth/login`, {
      method: 'POST',
      body: { email, password },
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    })
    token.value = res.token
    user.value  = res.user
    if (import.meta.client) {
      localStorage.setItem('biotrack_token', res.token)
      localStorage.setItem('biotrack_user', JSON.stringify(res.user))
    }
    return res
  }

  async function logout() {
    const base = getApiBase()
    try {
      await $fetch(`${base}/auth/logout`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}`, 'Content-Type': 'application/json' },
      })
    } catch {}
    token.value = null
    user.value  = null
    if (import.meta.client) {
      localStorage.removeItem('biotrack_token')
      localStorage.removeItem('biotrack_user')
    }
    await navigateTo('/login')
  }

  return { user, token, isAuthenticated, init, login, logout }
})
