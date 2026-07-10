export function useApi() {
  function getBase(): string {
    // 1. Essayer runtimeConfig (fonctionne dans les composants Vue)
    try {
      const config = useRuntimeConfig()
      const base = config.public.apiBase as string
      if (base && base.startsWith('http')) return base
    } catch {}

    // 2. Fallback : lire directement depuis window (injecté par Nuxt au runtime)
    if (import.meta.client) {
      const win = window as any
      const base = win.__NUXT__?.config?.public?.apiBase
      if (base && base.startsWith('http')) return base
    }

    // 3. Fallback final hardcodé
    return 'http://localhost:8000/api'
  }

  function getToken(): string | null {
    try {
      const auth = useAuthStore()
      if (auth.token) return auth.token
    } catch {}
    if (import.meta.client) {
      return localStorage.getItem('biotrack_token')
    }
    return null
  }

  async function request<T>(path: string, options: any = {}): Promise<T> {
    const base  = getBase()
    const url   = `${base}${path}`
    const token = getToken()

    try {
      const res = await $fetch<T>(url, {
        ...options,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
          ...options.headers,
        },
      })
      return res
    } catch (err: any) {
      if (err?.response?.status === 401 || err?.status === 401) {
        if (import.meta.client) {
          localStorage.removeItem('biotrack_token')
          localStorage.removeItem('biotrack_user')
        }
        try {
          const auth = useAuthStore()
          auth.token = null
          auth.user  = null
        } catch {}
        await navigateTo('/login')
      }
      throw err
    }
  }

  return {
    get:   <T>(path: string, params?: Record<string, any>) =>
             request<T>(path, { method: 'GET', params }),
    post:  <T>(path: string, body?: any) =>
             request<T>(path, { method: 'POST', body }),
    put:   <T>(path: string, body?: any) =>
             request<T>(path, { method: 'PUT', body }),
    patch: <T>(path: string, body?: any) =>
             request<T>(path, { method: 'PATCH', body }),
    del:   <T>(path: string) =>
             request<T>(path, { method: 'DELETE' }),
  }
}
