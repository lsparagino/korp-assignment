<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Forgot password"
        description="Enter your email to receive a password reset link"
    >
        <Head title="Forgot password" />

        <v-alert
            v-if="status"
            type="success"
            variant="tonal"
            class="mb-4"
            density="compact"
        >
            {{ status }}
        </v-alert>

        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="d-flex flex-column ga-6">
                <v-text-field
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

                <v-btn
                    type="submit"
                    block
                    color="primary"
                    height="48"
                    rounded="lg"
                    class="text-none font-weight-bold"
                    :loading="processing"
                    data-test="email-password-reset-link-button"
                >
                    Email password reset link
                </v-btn>
            </div>
        </Form>

        <div class="text-center mt-6">
            <span class="text-body-2 text-grey-darken-1">Or, return to</span>
            <Link :href="login().url" class="text-body-2 text-primary font-weight-bold ms-1 text-decoration-none">log in</Link>
        </div>
    </AuthLayout>
</template>
```
