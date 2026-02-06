<script lang="ts" setup>
  import { useRoute } from 'vue-router'

  const route = useRoute()
</script>

<template>
  <v-app>
    <v-main class="bg-grey-lighten-4 d-flex align-center justify-center">
      <v-container class="max-w-sm px-4">
        <div class="mb-8 text-center px-4">
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
