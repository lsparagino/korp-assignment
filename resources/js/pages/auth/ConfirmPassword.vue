<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="d-flex flex-column ga-6">
                <v-text-field
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
                    data-test="confirm-password-button"
                >
                    Confirm Password
                </v-btn>
            </div>
        </Form>
    </AuthLayout>
</template>
