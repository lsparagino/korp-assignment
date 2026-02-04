<script setup lang="ts">
import { useRoute } from 'vue-router';
const route = useRoute();
</script>

<template>
    <v-app>
        <v-main class="bg-grey-lighten-4 d-flex align-center justify-center">
            <v-container class="max-w-sm">
                <div class="text-center mb-8">
                    <router-link to="/" class="d-inline-block mb-4">
                        <img src="/images/sw_logo.png" alt="SecureWallet" class="logo-img" />
                    </router-link>
                    <h1 class="text-h5 font-weight-bold text-grey-darken-3 mb-2">{{ route.meta.title }}</h1>
                    <p class="text-body-2 text-grey-darken-1">{{ route.meta.description }}</p>
                </div>

                <v-card flat border rounded="xl" class="pa-8">
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
