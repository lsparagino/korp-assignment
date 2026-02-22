<script lang="ts" setup>
  import { useI18n } from 'vue-i18n'
  import { useRoute } from 'vue-router'

  const { t } = useI18n()
  const route = useRoute()
</script>

<template>
  <v-app>
    <v-main class="bg-grey-lighten-4 d-flex align-center justify-center">
      <v-container class="max-w-sm px-4">
        <div class="mb-8 px-4 text-center">
          <router-link class="d-inline-block mb-4" to="/">
            <img
              alt="SecureWallet"
              class="logo-img"
              src="/images/sw_logo.png"
            >
          </router-link>
          <h1
            class="text-h5 font-weight-bold text-grey-darken-3 mb-2"
          >
            {{ t(route.meta.title as string) }}
          </h1>
          <p class="text-body-2 text-grey-darken-1">
            {{ t(route.meta.description as string) }}
          </p>
        </div>

        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.max-w-sm {
    max-width: 500px !important;
}

.logo-img {
    height: 48px;
}
</style>
