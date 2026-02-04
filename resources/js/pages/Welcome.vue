<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { dashboard, login, register } from '@/routes';

interface Props {
    canRegister: boolean;
}

withDefaults(defineProps<Props>(), {
    canRegister: true,
});
</script>

<template>
    <Head title="Welcome" />

    <v-app>
        <v-main class="d-flex align-center justify-center welcome-bg">
            <v-container>
                <v-row justify="center">
                    <v-col cols="12" sm="8" md="6" lg="4" class="text-center">
                        <!-- Glassmorphism Card -->
                        <v-card class="pa-8 pa-sm-12 rounded-xl glass-card" elevation="10">
                            <!-- Logo Section -->
                            <div class="mb-10 d-flex justify-center logo-container">
                                <AppLogo />
                            </div>

                            <!-- Subtitle Section -->
                            <h1 class="text-h4 font-weight-bold mb-3 text-grey-darken-3">Secure Wallet</h1>
                            <p class="text-subtitle-1 text-grey-darken-1 mb-10">
                                Secure Business Wallet Management App
                            </p>

                            <!-- Actions -->
                            <div v-if="$page.props.auth.user" class="d-flex flex-column">
                                <Link :href="dashboard().url" class="text-decoration-none">
                                    <v-btn
                                        color="primary"
                                        size="x-large"
                                        block
                                        rounded="lg"
                                        elevation="2"
                                        class="text-none font-weight-bold"
                                    >
                                        Go to Dashboard
                                    </v-btn>
                                </Link>
                            </div>
                            <div v-else class="d-flex flex-column">
                                <Link :href="login().url" class="text-decoration-none mb-4">
                                    <v-btn
                                        color="primary"
                                        size="x-large"
                                        block
                                        rounded="lg"
                                        elevation="2"
                                        class="text-none font-weight-bold"
                                    >
                                        Log in
                                    </v-btn>
                                </Link>

                                <Link v-if="canRegister" :href="register().url" class="text-decoration-none">
                                    <v-btn
                                        variant="outlined"
                                        color="primary"
                                        size="x-large"
                                        block
                                        rounded="lg"
                                        class="text-none font-weight-bold"
                                    >
                                        Register
                                    </v-btn>
                                </Link>
                            </div>
                        </v-card>

                        <!-- Footer simple text -->
                        <p class="mt-8 text-caption text-grey-darken-1">
                            &copy; {{ new Date().getFullYear() }} SecureWallet. All rights reserved.
                        </p>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

<style scoped>
.welcome-bg {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.glass-card {
    background: rgba(255, 255, 255, 0.8) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.logo-container {
    height: 120px;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.logo-container :deep(img) {
    height: 100%;
    width: auto;
}
</style>
