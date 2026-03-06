<script lang="ts" setup>
  import { PiniaColadaDevtools } from '@pinia/colada-devtools'
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AppNotification from '@/components/ui/AppNotification.vue'

  const loading = ref(true)
  const router = useRouter()
  const showDevtools = import.meta.env.MODE !== 'e2e'

  router.isReady().then(() => {
    loading.value = false
  })
</script>

<template>
  <div v-if="loading" class="app-loader">
    <div class="app-loader__spinner" />
    <span class="app-loader__text">Loading…</span>
  </div>

  <template v-else>
    <router-view />
    <AppNotification />
    <PiniaColadaDevtools v-if="showDevtools" />
  </template>
</template>
