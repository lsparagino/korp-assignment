<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/plugins/api';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  email: '',
  password: '',
  remember: false,
});

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const status = ref('');

const submit = async () => {
  processing.value = true;
  errors.value = {};
  
  try {
    const response = await api.post('/login', form);
    
    if (response.data.two_factor) {
      authStore.setTwoFactor(response.data.user_id);
      router.push('/auth/two-factor-challenge');
      return;
    }

    authStore.setToken(response.data.access_token);
    authStore.setUser(response.data.user);
    router.push('/dashboard');
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    } else {
      status.value = 'An error occurred during login.';
    }
  } finally {
    processing.value = false;
  }
};
</script>

<template>
    <v-alert
        v-if="status"
        type="error"
        variant="tonal"
        class="mb-4"
        density="compact"
    >
        {{ status }}
    </v-alert>

    <v-form @submit.prevent="submit">
        <div class="d-flex flex-column ga-4">
            <v-text-field
                v-model="form.email"
                label="Email address"
                placeholder="email@example.com"
                type="email"
                name="email"
                required
                autofocus
                variant="outlined"
                color="primary"
                density="comfortable"
                :error-messages="errors.email"
                hide-details="auto"
            ></v-text-field>

            <div>
                <div class="d-flex align-center justify-space-between mb-1">
                    <span
                        class="text-caption font-weight-medium text-grey-darken-3"
                        >Password</span
                    >
                    <router-link
                        to="/auth/forgot-password"
                        class="text-caption font-weight-bold text-decoration-none text-primary"
                    >
                        Forgot password?
                    </router-link>
                </div>
                <v-text-field
                    v-model="form.password"
                    placeholder="Password"
                    type="password"
                    name="password"
                    required
                    variant="outlined"
                    color="primary"
                    density="comfortable"
                    :error-messages="errors.password"
                    hide-details="auto"
                ></v-text-field>
            </div>

            <v-checkbox
                v-model="form.remember"
                label="Remember me"
                name="remember"
                color="primary"
                density="comfortable"
                hide-details
                class="ms-n3"
            ></v-checkbox>

            <v-btn
                type="submit"
                block
                color="primary"
                height="48"
                rounded="lg"
                class="mt-4 text-none font-weight-bold"
                :loading="processing"
            >
                Log in
            </v-btn>
        </div>

        <div class="mt-6 text-center">
            <span class="text-body-2 text-grey-darken-1"
                >Don't have an account?</span
            >
            <router-link
                to="/auth/register"
                class="text-body-2 font-weight-bold ms-1 text-decoration-none text-primary"
                >Sign up</router-link
            >
        </div>
    </v-form>
</template>

<route lang="yaml">
meta:
  layout: Auth
  title: Log in to your account
  description: Enter your email and password below to log in
</route>
