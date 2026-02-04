<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/plugins/api';

const router = useRouter();

const form = reactive({
    password: '',
});

const processing = ref(false);
const errors = ref<Record<string, string[]>>({});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await api.post('/user/confirm-password', form);
        // Usually redirects back or to a specific page
        router.back();
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <v-form @submit.prevent="submit">
        <div class="d-flex flex-column ga-6">
            <v-text-field
                v-model="form.password"
                label="Password"
                placeholder="Password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                autofocus
                variant="outlined"
                color="primary"
                density="comfortable"
                :error-messages="errors.password"
                hide-details="auto"
            ></v-text-field>

            <v-btn
                type="submit"
                block
                color="primary"
                height="48"
                rounded="lg"
                class="text-none font-weight-bold"
                :loading="processing"
            >
                Confirm Password
            </v-btn>
        </div>
    </v-form>
</template>

<route lang="yaml">
meta:
  layout: Auth
  title: Confirm your password
  description: This is a secure area of the application. Please confirm your password before continuing.
</route>
