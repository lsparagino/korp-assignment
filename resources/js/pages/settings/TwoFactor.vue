<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { disable, enable, show } from '@/routes/two-factor';
import type { BreadcrumbItem } from '@/types';

type Props = {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Two-Factor Authentication',
        href: show.url(),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Two-Factor Authentication" />

        <h1 class="sr-only">Two-Factor Authentication Settings</h1>

        <SettingsLayout>
            <div class="d-flex flex-column ga-6">
                <Heading
                    variant="small"
                    title="Two-Factor Authentication"
                    description="Manage your two-factor authentication settings"
                />

                <div
                    v-if="!twoFactorEnabled"
                    class="d-flex flex-column ga-4 items-start"
                >
                    <div>
                        <v-chip color="error" size="small" label variant="flat">Disabled</v-chip>
                    </div>

                    <p class="text-body-2 text-grey-darken-1">
                        When you enable two-factor authentication, you will be
                        prompted for a secure pin during login. This pin can be
                        retrieved from a TOTP-supported application on your
                        phone.
                    </p>

                    <div>
                        <v-btn
                            v-if="hasSetupData"
                            color="primary"
                            variant="flat"
                            class="text-none"
                            @click="showSetupModal = true"
                        >
                            <v-icon start icon="mdi-shield-check"></v-icon>
                            Continue Setup
                        </v-btn>
                        <Form
                            v-else
                            v-bind="enable.form()"
                            @success="showSetupModal = true"
                            #default="{ processing }"
                        >
                            <v-btn
                                type="submit"
                                color="primary"
                                variant="flat"
                                class="text-none"
                                :loading="processing"
                            >
                                <v-icon start icon="mdi-shield-check"></v-icon>
                                Enable 2FA
                            </v-btn>
                        </Form>
                    </div>
                </div>

                <div
                    v-else
                    class="d-flex flex-column ga-4 items-start"
                >
                    <div>
                        <v-chip color="success" size="small" label variant="flat">Enabled</v-chip>
                    </div>

                    <p class="text-body-2 text-grey-darken-1">
                        With two-factor authentication enabled, you will be
                        prompted for a secure, random pin during login, which
                        you can retrieve from the TOTP-supported application on
                        your phone.
                    </p>

                    <TwoFactorRecoveryCodes />

                    <div>
                        <Form v-bind="disable.form()" #default="{ processing }">
                            <v-btn
                                color="error"
                                variant="flat"
                                type="submit"
                                class="text-none"
                                :loading="processing"
                            >
                                <v-icon start icon="mdi-shield-off"></v-icon>
                                Disable 2FA
                            </v-btn>
                        </Form>
                    </div>
                </div>

                <TwoFactorSetupModal
                    v-model:isOpen="showSetupModal"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
