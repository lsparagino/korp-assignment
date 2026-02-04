<script lang="ts" setup>
  import { useRoute } from 'vue-router'

  const route = useRoute()
</script>

<template>
  <v-app>
    <v-main class="bg-grey-lighten-4 d-flex align-center justify-center">
      <v-container class="max-w-sm">
        <div class="mb-8 text-center">
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
            {{ route.meta.title }}
          </h1>
          <p class="text-body-2 text-grey-darken-1">
            {{ route.meta.description }}
          </p>
        </div>

        <v-card border class="pa-8" flat rounded="xl">
          <router-view />
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>

<style scoped>
.max-w-sm {
    max-width: 448px !important;
}

.logo-img {
    height: 48px;
}
</style>
