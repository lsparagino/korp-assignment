<script lang="ts" setup>
  import { computed } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useAuthStore } from '@/stores/auth'

  const { t } = useI18n()
  const authStore = useAuthStore()

  interface NavItem {
    title: string
    to: string
    icon: string
    adminOnly?: boolean
  }

  const allNavItems: NavItem[] = [
    { title: t('settings.nav.profile'), to: '/settings/profile', icon: 'mdi-account' },
    { title: t('settings.nav.password'), to: '/settings/password', icon: 'mdi-lock' },
    {
      title: t('settings.nav.twoFactor'),
      to: '/settings/two-factor',
      icon: 'mdi-shield-check',
    },
    { title: t('settings.nav.preferences'), to: '/settings/preferences', icon: 'mdi-tune' },
    {
      title: t('settings.nav.thresholds'),
      to: '/settings/thresholds',
      icon: 'mdi-currency-usd',
      adminOnly: true,
    },
  ]

  const settingsNav = computed(() =>
    allNavItems.filter(item => !item.adminOnly || authStore.isAdmin),
  )
</script>

<template>
  <v-row class="ma-0">
    <v-col class="pa-0 pr-md-4 mb-md-0 mb-4" cols="12" md="3">
      <v-list bg-color="white" border nav rounded="lg">
        <v-list-item
          v-for="item in settingsNav"
          :key="item.to"
          class="mb-1"
          color="primary"
          :prepend-icon="item.icon"
          rounded="lg"
          :title="item.title"
          :to="item.to"
        />
      </v-list>
    </v-col>
    <v-col class="pa-0" cols="12" md="9">
      <v-card border class="pa-4 pa-md-8" flat rounded="lg">
        <slot />
      </v-card>
    </v-col>
  </v-row>
</template>
