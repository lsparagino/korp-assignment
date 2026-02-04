<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/plugins/api';

const route = useRoute();
const router = useRouter();

const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const processing = ref(false);
const errors = ref<Record<string, string[]>>({});
const status = ref('');

onMounted(() => {
    form.token = route.query.token as string || '';
    form.email = route.query.email as string || '';
});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        const response = await api.post('/reset-password', form);
        status.value = response.data.message;
        setTimeout(() => router.push('/auth/login'), 3000);
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            status.value = 'An error occurred. Please try again.';
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <v-alert
        v-if="status"
        type="success"
        variant="tonal"
        class="mb-4"
        density="compact"
    >
        {{ status }}
    </v-alert>

    <v-form @submit.prevent="submit">
        <div class="d-flex flex-column ga-6">
            <v-text-field
                v-model="form.email"
                label="Email"
                type="email"
                name="email"
                variant="outlined"
                color="primary"
                density="comfortable"
                readonly
                hide-details="auto"
            ></v-text-field>

            <v-text-field
                v-model="form.password"
                label="Password"
                placeholder="Password"
                type="password"
                name="password"
                required
                autofocus
                variant="outlined"
                color="primary"
                density="comfortable"
                :error-messages="errors.password"
                hide-details="auto"
            ></v-text-field>

            <v-text-field
                v-model="form.password_confirmation"
                label="Confirm password"
                placeholder="Confirm password"
                type="password"
                name="password_confirmation"
                required
                variant="outlined"
                color="primary"
                density="comfortable"
                :error-messages="errors.password_confirmation"
                hide-details="auto"
            ></v-text-field>

            <v-btn
                type="submit"
                block
                color="primary"
                height="48"
                rounded="lg"
                class="mt-4 text-none font-weight-bold"
                :loading="processing"
            >
                Reset password
            </v-btn>
        </div>
    </v-form>
</template>

<route lang="yaml">
meta:
  layout: Auth
  title: Reset password
  description: Please enter your new password below
</route>
