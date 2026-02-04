<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>

<template>
    <v-app>
        <v-main class="bg-grey-lighten-4 d-flex align-center justify-center">
            <v-container class="fill-height">
                <div class="text-center w-100">
                    <div class="d-flex justify-center mb-6">
                        <AppLogo style="height: 80px;"></AppLogo>
                    </div>
                    <h1 class="text-h2 font-weight-black text-grey-darken-4 mb-4">
                        Secure your wealth with confidence
                    </h1>
                    <p class="text-h5 text-grey-darken-1 mb-12">
                        The ultimate platform for managing your multi-currency wallets and team members securely.
                    </p>

                    <div class="d-flex justify-center ga-4">
                        <template v-if="authStore.isAuthenticated">
                            <v-btn
                                to="/dashboard"
                                color="primary"
                                size="x-large"
                                rounded="xl"
                                class="text-none font-weight-bold px-8"
                                elevation="8"
                            >
                                Go to Dashboard
                            </v-btn>
                        </template>
                        <template v-else>
                            <v-btn
                                to="/auth/login"
                                color="primary"
                                size="x-large"
                                rounded="xl"
                                class="text-none font-weight-bold px-8"
                                elevation="8"
                            >
                                Get Started
                            </v-btn>
                            <v-btn
                                to="/auth/register"
                                variant="outlined"
                                color="primary"
                                size="x-large"
                                rounded="xl"
                                class="text-none font-weight-bold px-8"
                            >
                                Create Account
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
</route>
