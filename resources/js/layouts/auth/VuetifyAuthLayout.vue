<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <v-app>
        <v-main class="bg-slate-50 flex items-center justify-center">
            <v-container fluid class="max-w-sm">
                <div class="text-center mb-8">
                    <Link :href="home()" class="inline-block mb-4">
                        <img src="/images/sw_logo.png" alt="SecureWallet" class="h-12" />
                    </Link>
                    <h1 class="text-h5 font-weight-bold text-slate-800 mb-2">{{ title }}</h1>
                    <p class="text-body-2 text-slate-500">{{ description }}</p>
                </div>

                <v-card flat border rounded="xl" class="pa-8">
                    <slot />
                </v-card>
            </v-container>
        </v-main>
    </v-app>
</template>
