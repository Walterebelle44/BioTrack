import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()

  const key = config.public.reverbKey as string
  if (!key) {
    console.warn('[Echo] NUXT_PUBLIC_REVERB_KEY non défini — WebSocket désactivé')
    return { provide: { echo: null } }
  }

  try {
    ;(window as any).Pusher = Pusher

    // Récupère le token depuis localStorage de façon dynamique à chaque requête auth
    const echo = new Echo({
      broadcaster: 'reverb' as any,
      key,
      wsHost:            config.public.reverbHost as string,
      wsPort:            Number(config.public.reverbPort),
      wssPort:           Number(config.public.reverbPort),
      forceTLS:          config.public.reverbScheme === 'https',
      enabledTransports: ['ws', 'wss'],
      // Endpoint d'autorisation des canaux privés (Sanctum Bearer token)
      authEndpoint: `${config.public.apiBase}/broadcasting/auth`,
      authorizer: (channel: any) => {
        return {
          authorize: (socketId: string, callback: Function) => {
            const token = localStorage.getItem('biotrack_token') ?? ''
            fetch(`${config.public.apiBase}/broadcasting/auth`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
              },
              body: JSON.stringify({
                socket_id:    socketId,
                channel_name: channel.name,
              }),
            })
              .then(res => res.json())
              .then(data => callback(null, data))
              .catch(err => callback(err, null))
          },
        }
      },
    })

    return { provide: { echo } }
  } catch (e) {
    console.warn('[Echo] Erreur initialisation WebSocket:', e)
    return { provide: { echo: null } }
  }
})
