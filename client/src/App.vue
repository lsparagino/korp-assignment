<script lang="ts" setup>
  import { PiniaColadaDevtools } from '@pinia/colada-devtools'
  import AppNotification from '@/components/ui/AppNotification.vue'
</script>

<template>
  <router-view />

  <AppNotification />
  <PiniaColadaDevtools />
</template>
