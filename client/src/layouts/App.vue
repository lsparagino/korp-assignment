<script lang="ts" setup>
  import {
    Bell,
    ChevronDown,
    LayoutDashboard,
    Repeat,
    Users,
    Wallet,
  } from 'lucide-vue-next'
  import { computed, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import AppLogo from '@/components/AppLogo.vue'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const drawer = ref(true)

  const navItems = [
    {
      title: 'Dashboard',
      icon: LayoutDashboard,
      to: '/dashboard',
      active: computed(() => route.path === '/dashboard'),
    },
    {
      title: 'Wallets',
      icon: Wallet,
      to: '/wallets/',
      active: computed(() => route.path.startsWith('/wallets')),
    },
    {
      title: 'Transactions',
      icon: Repeat,
      to: '/transactions/',
      active: computed(() => route.path.startsWith('/transactions')),
    },
    {
      title: 'Team Members',
      icon: Users,
      to: '/team-members/',
      active: computed(() => route.path.startsWith('/team-members')),
    },
  ]

  const selectedCompany = ref('Acme Corp')
  const companies = ['Acme Corp', 'Globex Inc', 'Soylent Corp']

  async function handleLogout () {
    // In a real app, you'd call the API logout first
    authStore.clearToken()
    router.push('/auth/login')
  }
</script>

<template>
  <v-app>
    <v-layout>
      <v-app-bar>
        <div
          class="d-flex h-100 w-100 justify-space-between overflow-hidden"
        >
          <AppLogo />

          <div class="d-flex align-center ga-4 px-6">
            <!-- Company Selector -->
            <v-menu offset-y>
              <template #activator="{ props }">
                <v-btn
                  class="text-none border-grey-lighten-2 text-grey-darken-3"
                  rounded="lg"
                  v-bind="props"
                  variant="outlined"
                >
                  {{ selectedCompany }}
                  <v-icon
                    end
                    :icon="ChevronDown"
                    size="18"
                  />
                </v-btn>
              </template>
              <v-list>
                <v-list-item
                  v-for="company in companies"
                  :key="company"
                  @click="selectedCompany = company"
                >
                  <v-list-item-title>{{
                    company
                  }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>

            <!-- Notifications -->
            <v-btn color="grey-darken-2" icon variant="text">
              <v-icon :icon="Bell" />
            </v-btn>

            <!-- User Avatar -->

            <v-menu min-width="200px">
              <template #activator="{ props }">
                <v-btn icon v-bind="props">
                  <v-avatar
                    class="bg-sidebar-bg"
                    size="large"
                  >
                    <v-icon icon="mdi-account" />
                  </v-avatar>
                </v-btn>
              </template>
              <v-card>
                <v-card-text>
                  <div class="pa-2">
                    <div class="d-flex align-center ga-4">
                      <v-avatar
                        class="bg-sidebar-bg border-grey-lighten-2"
                        size="36"
                      >
                        <v-icon
                          icon="mdi-account"
                        />
                      </v-avatar>
                      <div v-if="authStore.user">
                        <h3
                          class="text-subtitle-1 font-weight-bold"
                        >
                          {{ authStore.user.name }}
                        </h3>
                        <p
                          class="text-caption text-grey-darken-1"
                        >
                          {{ authStore.user.email }}
                        </p>
                      </div>
                    </div>

                    <v-divider class="my-3" />

                    <v-list class="py-0" :lines="false">
                      <v-list-item
                        class="px-0"
                        color="primary"
                        to="/settings/profile"
                      >
                        <template #prepend>
                          <v-icon
                            icon="mdi-cog"
                          />
                        </template>

                        <v-list-item-title
                          class="text-start"
                        >
                          Settings
                        </v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        class="px-0"
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
                          Disconnect
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
        class="border-e-sm"
        color="sidebar-bg"
        permanent
        width="260"
      >
        <v-list class="list-container" density="comfortable" nav>
          <v-list-item
            v-for="item in navItems"
            :key="item.title"
            :active="item.active.value"
            active-color="primary"
            class="nav-item"
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
              class="text-body-1 font-normal text-grey-darken-3"
            >{{ item.title }}</v-list-item-title>
          </v-list-item>
        </v-list>

        <template #append>
          <v-divider />
          <div class="pa-4">
            <v-btn
              block
              class="text-none"
              color="primary"
              prepend-icon="mdi-plus"
              size="large"
              to="/wallets/create/"
            >
              Create Wallet
            </v-btn>
          </div>
        </template>
      </v-navigation-drawer>

      <v-main class="bg-background">
        <v-container class="pa-4 pa-md-8">
          <router-view />
        </v-container>
      </v-main>
    </v-layout>
  </v-app>
</template>

<style scoped>
.list-container {
    background: transparent;
    padding: 1rem 1rem 1rem 0;
}

.cursor-pointer {
    cursor: pointer;
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
