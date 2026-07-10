export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()

  // Toujours appeler init() pour s'assurer que le token est chargé depuis localStorage
  if (import.meta.client) {
    auth.init()
  }

  if (!auth.isAuthenticated && to.path !== '/login') {
    return navigateTo('/login')
  }

  if (auth.isAuthenticated && to.path === '/login') {
    return navigateTo('/')
  }
})
