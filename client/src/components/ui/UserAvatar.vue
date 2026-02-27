<script lang="ts" setup>
  import { useAuthStore } from '@/stores/auth'
  import { getRoleColors } from '@/utils/colors'

  const authStore = useAuthStore()

  withDefaults(defineProps<{
    size?: number | string
    showInfo?: boolean
  }>(), {
    size: 36,
    showInfo: false,
  })
</script>

<template>
  <div class="d-flex align-center" :class="{ 'ga-4': showInfo }">
    <v-avatar
      :color="getRoleColors(authStore.user?.role ?? 'member').bg"
      :size="size"
    >
      <v-icon
        :color="getRoleColors(authStore.user?.role ?? 'member').text"
        icon="mdi-account"
        :size="Number(size) * 0.55"
      />
    </v-avatar>
    <div v-if="showInfo && authStore.user">
      <h3 class="text-subtitle-1 font-weight-bold">
        {{ authStore.user.name }}
      </h3>
      <p class="text-caption text-grey-darken-1">
        {{ authStore.user.email }}
      </p>
      <v-chip
        class="text-uppercase font-weight-bold mt-1"
        :color="getRoleColors(authStore.user.role).bg"
        size="x-small"
        variant="flat"
      >
        <span :class="`text-${getRoleColors(authStore.user.role).text}`">
          {{ authStore.user.role }}
        </span>
      </v-chip>
    </div>
  </div>
</template>
