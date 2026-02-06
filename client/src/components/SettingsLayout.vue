<script lang="ts" setup>
  import { useRoute } from 'vue-router'

  const route = useRoute()

  const settingsNav = [
    { title: 'Profile', to: '/settings/profile', icon: 'mdi-account' },
    { title: 'Password', to: '/settings/password', icon: 'mdi-lock' },
    {
      title: 'Two-Factor',
      to: '/settings/two-factor',
      icon: 'mdi-shield-check',
    },
  ]
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
