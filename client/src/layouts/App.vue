<script lang="ts" setup>
  import type { FunctionalComponent } from 'vue'
  import { LayoutDashboard, Repeat, Users, Wallet } from 'lucide-vue-next'
  import { computed, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRoute, useRouter } from 'vue-router'
  import CompanySelector from '@/components/features/CompanySelector.vue'
  import AppLogo from '@/components/ui/AppLogo.vue'
  import UserAvatar from '@/components/ui/UserAvatar.vue'
  import { useAuthStore } from '@/stores/auth'
  import { useCompanyStore } from '@/stores/company'

  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const companyStore = useCompanyStore()
  const drawer = ref<boolean | null>(null)

  interface NavItem {
    title: string
    icon: FunctionalComponent
    to: string
    active: ComputedRef<boolean>
    role?: string
  }

  const navItems: NavItem[] = [
    {
      title: t('nav.dashboard'),
      icon: LayoutDashboard,
      to: '/dashboard',
      active: computed(() => route.path === '/dashboard'),
    },
    {
      title: t('nav.wallets'),
      icon: Wallet,
      to: '/wallets/',
      active: computed(() => route.path.startsWith('/wallets')),
    },
    {
      title: t('nav.transactions'),
      icon: Repeat,
      to: '/transactions/',
      active: computed(() => route.path.startsWith('/transactions')),
    },
    {
      title: t('nav.teamMembers'),
      icon: Users,
      to: '/team-members/',
      active: computed(() => route.path.startsWith('/team-members')),
    },
  ]

  const filteredNavItems = computed(() => {
    return navItems.filter(item => {
      if (!item.role) return true
      return authStore.user?.role === item.role
    })
  })

  async function handleLogout () {
    await authStore.logout()
    router.push('/auth/login')
  }
</script>

<template>
  <v-app class="app-container">
    <v-layout class="h-100 overflow-hidden">
      <v-app-bar border="b-sm" flat>
        <div
          class="d-flex h-100 w-100 align-center justify-space-between overflow-hidden"
        >
          <div
            class="d-flex align-center h-100 ga-2 ga-sm-4 px-sm-4 px-1"
          >
            <v-app-bar-nav-icon
              class="hidden-md-and-up"
              @click="drawer = !drawer"
            />
            <AppLogo />
          </div>

          <div class="d-flex align-center ga-2 ga-sm-4 px-sm-6 px-2">
            <!-- Company Selector (Desktop) -->
            <div class="hidden-sm-and-down">
              <CompanySelector />
            </div>

            <!-- Notifications -->
            <v-btn
              color="grey-darken-2"
              disabled
              icon
              variant="text"
            >
              <v-icon icon="mdi-bell-outline" />
            </v-btn>

            <!-- User Avatar -->

            <v-menu min-width="200px">
              <template #activator="{ props }">
                <v-btn data-testid="user-menu-btn" icon v-bind="props">
                  <UserAvatar size="36" />
                </v-btn>
              </template>
              <v-card>
                <v-card-text>
                  <div class="pa-2">
                    <UserAvatar show-info />

                    <v-divider class="my-3" />

                    <v-list class="py-0" :lines="false">
                      <v-list-item
                        class="px-0"
                        color="primary"
                        data-testid="settings-link"
                        to="/settings/profile"
                      >
                        <template #prepend>
                          <v-icon icon="mdi-cog" />
                        </template>

                        <v-list-item-title
                          class="text-start"
                        >
                          {{ $t('nav.settings') }}
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        class="px-0"
                        data-testid="disconnect-btn"
                        @click="handleLogout"
                      >
                        <template #prepend>
                          <v-icon
                            color="error"
                            icon="mdi-logout"
                          />
                        </template>

                        <v-list-item-title
                          class="text-error text-start"
                        >
                          {{ $t('nav.disconnect') }}
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </div>
                </v-card-text>
              </v-card>
            </v-menu>
          </div>
        </div>
      </v-app-bar>

      <v-navigation-drawer
        v-model="drawer"
        app
        class="border-e-sm overflow-hidden"
        color="sidebar-bg"
        data-testid="sidebar"
        :permanent="$vuetify.display.mdAndUp"
        width="260"
      >
        <div class="d-flex flex-column h-100">
          <!-- Company Selector (Mobile) -->
          <div
            v-if="companyStore.hasCompanies"
            class="pa-4 hidden-md-and-up border-b-sm"
          >
            <CompanySelector block />
          </div>

          <v-list
            class="list-container flex-grow-1 overflow-y-auto"
            data-testid="nav-list"
            density="comfortable"
            nav
          >
            <v-list-item
              v-for="item in filteredNavItems"
              :key="item.title"
              :active="item.active.value"
              active-color="primary"
              class="nav-item"
              :data-testid="`nav-${item.to.replace(/\//g, '').replace(/\s+/g, '-')}`"
              :to="item.to"
              :value="item.title"
              variant="text"
            >
              <template #prepend>
                <v-icon
                  class="mr-0 ms-4"
                  :icon="item.icon"
                  size="20"
                />
              </template>
              <v-list-item-title
                class="text-body-1 text-grey-darken-3 font-normal"
              >{{ item.title }}</v-list-item-title>
            </v-list-item>
          </v-list>

          <v-divider />
          <div class="pa-4 flex-shrink-0">
            <TransferButton block size="large" />
          </div>
        </div>
      </v-navigation-drawer>

      <v-main class="bg-background h-100 overflow-y-auto">
        <v-container class="pa-4 pa-md-8">
          <router-view />
        </v-container>
      </v-main>
    </v-layout>
  </v-app>
</template>

<style scoped>
.app-container {
    height: 100vh;
    overflow: hidden;
}

.h-100 {
    height: 100% !important;
}

.list-container {
    background: transparent;
    padding: 1rem 1rem 1rem 0;
}

.nav-item {
    border-radius: 0 8px 8px 0;
    margin-bottom: 0.25rem;
    border-left: 4px solid transparent;
}

.nav-item.v-list-item--active {
    background-color: #dfe4ee !important;
    color: #191f2f !important;
    border-left: 4px solid rgb(var(--v-theme-primary));
}

.nav-item.v-list-item--active .v-icon {
    color: rgb(var(--v-theme-primary)) !important;
}
</style>
