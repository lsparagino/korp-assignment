<script lang="ts" setup>
  import AppLogo from '@/components/ui/AppLogo.vue'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
</script>

<template>
  <v-app>
    <v-main class="bg-grey-lighten-4 d-flex align-center justify-center">
      <v-container class="fill-height">
        <div class="w-100 text-center">
          <div class="d-flex mb-6 justify-center">
            <AppLogo
              :style="{ height: 'clamp(48px, 10vw, 80px)' }"
            />
          </div>
          <h1
            class="text-h4 text-sm-h2 font-weight-black text-grey-darken-4 mb-4 px-4"
          >
            {{ $t('landing.headline') }}
          </h1>
          <p
            class="text-subtitle-1 text-sm-h5 text-grey-darken-1 mb-12 px-4"
          >
            {{ $t('landing.subheadline') }}
          </p>
          <div
            class="d-flex flex-column flex-sm-row ga-4 justify-center px-6"
          >
            <template v-if="authStore.isAuthenticated">
              <v-btn
                class="text-none font-weight-bold px-8"
                color="primary"
                elevation="8"
                rounded="xl"
                size="x-large"
                to="/dashboard"
              >
                {{ $t('landing.goToDashboard') }}
              </v-btn>
            </template>
            <template v-else>
              <v-btn
                class="text-none font-weight-bold px-8"
                color="primary"
                elevation="8"
                rounded="xl"
                size="x-large"
                to="/auth/login"
              >
                {{ $t('landing.getStarted') }}
              </v-btn>
              <v-btn
                class="text-none font-weight-bold px-8"
                color="primary"
                rounded="xl"
                size="x-large"
                to="/auth/register"
                variant="outlined"
              >
                {{ $t('landing.createAccount') }}
              </v-btn>
            </template>
          </div>
        </div>
      </v-container>
    </v-main>
  </v-app>
</template>

<route lang="yaml">
meta:
    layout: false
    public: true
</route>
