<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="d-flex flex-column ga-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="d-flex flex-column ga-4">
                        <v-text-field
                            label="Name"
                            placeholder="Full name"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.name"
                            hide-details="auto"
                        ></v-text-field>

                        <v-text-field
                            label="Email address"
                            placeholder="Email address"
                            type="email"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.email"
                            hide-details="auto"
                        ></v-text-field>

                        <div v-if="mustVerifyEmail && !user.email_verified_at" class="mt-n2">
                            <p class="text-body-2 text-grey-darken-1">
                                Your email address is unverified.
                                <Link
                                    :href="send().url"
                                    class="text-primary font-weight-bold text-decoration-none ms-1"
                                >
                                    Click here to resend the verification email.
                                </Link>
                            </p>

                            <v-alert
                                v-if="status === 'verification-link-sent'"
                                type="success"
                                variant="tonal"
                                density="compact"
                                class="mt-2"
                            >
                                A new verification link has been sent to your email address.
                            </v-alert>
                        </div>

                        <div class="d-flex align-center ga-4 mt-2">
                            <v-btn
                                type="submit"
                                color="primary"
                                variant="flat"
                                class="text-none"
                                :loading="processing"
                                data-test="update-profile-button"
                            >
                                Save
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

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
