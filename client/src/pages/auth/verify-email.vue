<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/plugins/api';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const processing = ref(false);
const status = ref('');

const resend = async () => {
    processing.value = true;
    try {
        const response = await api.post('/email/verification-notification');
        status.value = 'verification-link-sent';
    } catch (error) {
        // Handle error
    } finally {
        processing.value = false;
    }
};

const handleLogout = () => {
    authStore.clearToken();
    router.push('/auth/login');
};
</script>

<template>
    <v-alert
        v-if="status === 'verification-link-sent'"
        type="success"
        variant="tonal"
        class="mb-4"
        density="compact"
    >
        A new verification link has been sent to the email address you
        provided during registration.
    </v-alert>

    <div class="d-flex flex-column ga-6 align-center">
        <v-btn
            @click="resend"
            block
            variant="tonal"
            color="secondary"
            height="48"
            rounded="lg"
            class="text-none font-weight-bold"
            :loading="processing"
        >
            Resend verification email
        </v-btn>

        <v-btn
            variant="text"
            color="primary"
            class="text-body-2 font-weight-bold"
            @click="handleLogout"
        >
            Log out
        </v-btn>
    </div>
</template>

<route lang="yaml">
meta:
  layout: Auth
  title: Verify email
  description: Please verify your email address by clicking on the link we just emailed to you.
</route>
