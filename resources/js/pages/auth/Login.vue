<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your email and password below to log in"
    >
        <Head title="Log in" />

        <v-alert
            v-if="status"
            type="success"
            variant="tonal"
            class="mb-4"
            density="compact"
        >
            {{ status }}
        </v-alert>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
        >
            <div class="d-flex flex-column ga-4">
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

                <div>
                    <div class="d-flex align-center justify-space-between mb-1">
                        <span
                            class="text-caption font-weight-medium text-grey-darken-3"
                            >Password</span
                        >
                        <Link
                            v-if="canResetPassword"
                            :href="request().url"
                            class="text-caption font-weight-bold text-decoration-none text-primary"
                        >
                            Forgot password?
                        </Link>
                    </div>
                    <v-text-field
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
                    data-test="login-button"
                >
                    Log in
                </v-btn>
            </div>

            <div class="mt-6 text-center" v-if="canRegister">
                <span class="text-body-2 text-grey-darken-1"
                    >Don't have an account?</span
                >
                <Link
                    :href="register().url"
                    class="text-body-2 font-weight-bold ms-1 text-decoration-none text-primary"
                    >Sign up</Link
                >
            </div>
        </Form>
    </AuthBase>
</template>
