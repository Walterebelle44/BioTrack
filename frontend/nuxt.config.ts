// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/tailwind.css'],

  vite: {
    plugins: [
      tailwindcss(),
    ],
  },

  runtimeConfig: {
    public: {
      apiBase:      process.env.NUXT_PUBLIC_API_BASE      || 'http://localhost:8000/api',
      reverbKey:    process.env.NUXT_PUBLIC_REVERB_KEY    || '',
      reverbHost:   process.env.NUXT_PUBLIC_REVERB_HOST   || 'localhost',
      reverbPort:   process.env.NUXT_PUBLIC_REVERB_PORT   || '8081',
      reverbScheme: process.env.NUXT_PUBLIC_REVERB_SCHEME || 'http',
    }
  },

  modules: ['shadcn-nuxt', '@pinia/nuxt'],
  imports: {
    dirs: ['stores']
  },
  shadcn: {
    prefix: '',
    componentDir: '@/components/ui'
  }
})
