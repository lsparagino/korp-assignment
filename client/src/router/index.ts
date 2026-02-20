/**
 * router/index.ts
 *
 * Automatic routes for `./src/pages/*.vue`
 *
 * @see https://github.com/posva/unplugin-vue-router
 */
import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { setupLayouts } from 'virtual:generated-layouts'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from 'vue-router/auto-routes'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: setupLayouts(routes),
})

// Workaround for https://github.com/vitejs/vite/issues/11804
router.onError((err: Error, to: RouteLocationNormalized) => {
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

router.beforeEach(
  async (
    to: RouteLocationNormalized,
    _from: RouteLocationNormalized,
    next: NavigationGuardNext,
  ) => {
    const authStore = useAuthStore()
    const loggedIn = !!authStore.token

    const authRequired = !to.meta.public

    if (authRequired && !loggedIn) {
      return next('/auth/login')
    }

    if (loggedIn && !authStore.isEmailVerified) {
      if (to.path !== '/auth/verify-email') {
        return next('/auth/verify-email')
      }
    }

    if (loggedIn && authStore.isEmailVerified && to.path === '/auth/verify-email') {
      const hasVerifyParams = to.query.id && to.query.hash && to.query.expires && to.query.signature

      if (!authStore.user?.pending_email && !hasVerifyParams) {
        return next('/dashboard')
      }
    }

    if (loggedIn) {
      const companyStore = useCompanyStore()
      if (!companyStore.currentCompany) {
        await companyStore.fetchCompanies()
      }
    }

    return next()
  },
)

export { router }
