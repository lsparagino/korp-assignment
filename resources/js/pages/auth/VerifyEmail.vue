<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification" />

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

        <Form
            v-bind="send.form()"
            v-slot="{ processing }"
        >
            <div class="d-flex flex-column ga-6 align-center">
                <v-btn
                    type="submit"
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

                <Link
                    :href="logout().url"
                    class="text-body-2 text-primary font-weight-bold text-decoration-none"
                >
                    Log out
                </Link>
            </div>
        </Form>
    </AuthLayout>
</template>
