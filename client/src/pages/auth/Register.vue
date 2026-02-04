<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/plugins/api';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

const submit = async () => {
  processing.value = true;
  errors.value = {};
  
  try {
    const response = await api.post('/register', form);
    authStore.setToken(response.data.access_token);
    authStore.setUser(response.data.user);
    
    // Redirect to dashboard (if email verification not forced)
    router.push('/dashboard');
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
        <div class="d-flex flex-column ga-4">
            <v-text-field
                v-model="form.name"
                label="Name"
                placeholder="Full name"
                type="text"
                name="name"
                required
                autofocus
                variant="outlined"
                color="primary"
                density="comfortable"
                :error-messages="errors.name"
                hide-details="auto"
            ></v-text-field>

            <v-text-field
                v-model="form.email"
                label="Email address"
                placeholder="email@example.com"
                type="email"
                name="email"
                required
                variant="outlined"
                color="primary"
                density="comfortable"
                :error-messages="errors.email"
                hide-details="auto"
            ></v-text-field>

            <v-text-field
                v-model="form.password"
                label="Password"
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
                class="mt-6 text-none font-weight-bold"
                :loading="processing"
            >
                Create account
            </v-btn>
        </div>

        <div class="text-center mt-6">
            <span class="text-body-2 text-grey-darken-1">Already have an account?</span>
            <router-link to="/auth/login" class="text-body-2 text-primary font-weight-bold ms-1 text-decoration-none">Log in</router-link>
        </div>
    </v-form>
</template>

<route lang="yaml">
meta:
  layout: Auth
  title: Create an account
  description: Enter your details below to create your account
</route>
