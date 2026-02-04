/**
 * router/index.ts
 *
 * Automatic routes for `./src/pages/*.vue`
 */

import { setupLayouts } from 'virtual:generated-layouts'
// Composables
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from 'vue-router/auto-routes'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: setupLayouts(routes),
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const publicPages = [
    '/',
    '/auth/login',
    '/auth/register',
    '/auth/forgot-password',
    '/auth/reset-password',
    '/auth/verify-email',
    '/auth/confirm-password',
    '/auth/two-factor-challenge',
  ]

  // Normalize path by removing trailing slash if not root
  const path = to.path === '/' ? '/' : to.path.replace(/\/$/, '')
  const authRequired = !publicPages.includes(path)
  const loggedIn = authStore.isAuthenticated

  if (authRequired && !loggedIn) {
    return next('/auth/login')
  }

  if (loggedIn && (path === '/auth/login' || path === '/auth/register')) {
    return next('/dashboard')
  }

  next()
})

// Workaround for https://github.com/vitejs/vite/issues/11804
router.onError((err, to) => {
  if (
    err?.message?.includes?.('Failed to fetch dynamically imported module')
  ) {
    if (localStorage.getItem('vuetify:dynamic-reload')) {
      console.error(
        'Dynamic import error, reloading page did not fix it',
        err,
      )
    } else {
      console.log('Reloading page to fix dynamic import error')
      localStorage.setItem('vuetify:dynamic-reload', 'true')
      location.assign(to.fullPath)
    }
  } else {
    console.error(err)
  }
})

router.isReady().then(() => {
  localStorage.removeItem('vuetify:dynamic-reload')
})

export default router
