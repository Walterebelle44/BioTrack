export default defineNuxtRouteMiddleware((to) => {
  if (!to.path.startsWith('/admin')) return
  const auth = useAuthStore()
  if (auth.token === null) auth.init()
  if (auth.user?.role !== 'admin') {
    return navigateTo('/')
  }
})
