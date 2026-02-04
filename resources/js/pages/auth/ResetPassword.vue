<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <AuthLayout
        title="Reset password"
        description="Please enter your new password below"
    >
        <Head title="Reset password" />

        <Form
            v-bind="update.form()"
            :transform="(data) => ({ ...data, token, email })"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="d-flex flex-column ga-6">
                <v-text-field
                    label="Email"
                    v-model="inputEmail"
                    type="email"
                    name="email"
                    variant="outlined"
                    color="primary"
                    density="comfortable"
                    readonly
                    hide-details="auto"
                ></v-text-field>

                <v-text-field
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
                    data-test="reset-password-button"
                >
                    Reset password
                </v-btn>
            </div>
        </Form>
    </AuthLayout>
</template>
