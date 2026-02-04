<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/user-password';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Password settings" />

        <h1 class="sr-only">Password Settings</h1>

        <SettingsLayout>
            <div class="d-flex flex-column ga-6">
                <Heading
                    variant="small"
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                />

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="d-flex flex-column ga-4">
                        <v-text-field
                            label="Current password"
                            placeholder="Current password"
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.current_password"
                            hide-details="auto"
                        ></v-text-field>

                        <v-text-field
                            label="New password"
                            placeholder="New password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
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
                            autocomplete="new-password"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.password_confirmation"
                            hide-details="auto"
                        ></v-text-field>

                        <div class="d-flex align-center ga-4 mt-2">
                            <v-btn
                                type="submit"
                                color="primary"
                                variant="flat"
                                class="text-none"
                                :loading="processing"
                                data-test="update-password-button"
                            >
                                Save password
                            </v-btn>

                            <v-fade-transition>
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-body-2 text-grey-darken-1"
                                >
                                    Saved.
                                </p>
                            </v-fade-transition>
                        </div>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
