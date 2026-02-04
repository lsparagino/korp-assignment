<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="space-y-4">
                <v-text-field
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
                    data-test="register-user-button"
                >
                    Create account
                </v-btn>
            </div>

            <div class="text-center mt-6">
                <span class="text-body-2 text-slate-500">Already have an account?</span>
                <Link :href="login().url" class="text-body-2 text-primary font-weight-bold ml-1">Log in</Link>
            </div>
        </Form>
    </AuthBase>
</template>
