<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/AlertError.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <v-card variant="outlined" rounded="lg" class="w-100">
        <v-card-item class="pb-2">
            <template v-slot:prepend>
                <v-icon icon="mdi-lock-outline" class="mr-2"></v-icon>
            </template>
            <v-card-title class="text-h6 font-weight-bold">
                2FA Recovery Codes
            </v-card-title>
            <v-card-subtitle class="text-wrap">
                Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.
            </v-card-subtitle>
        </v-card-item>

        <v-card-text>
            <div class="d-flex flex-column flex-sm-row ga-3 justify-center justify-sm-space-between align-sm-center mb-4">
                <v-btn
                    variant="tonal"
                    color="primary"
                    class="text-none"
                    @click="toggleRecoveryCodesVisibility"
                >
                    <v-icon start :icon="isRecoveryCodesVisible ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"></v-icon>
                    {{ isRecoveryCodesVisible ? 'Hide' : 'View' }} Recovery Codes
                </v-btn>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <v-btn
                        variant="text"
                        color="secondary"
                        class="text-none"
                        type="submit"
                        :loading="processing"
                    >
                        <v-icon start icon="mdi-refresh"></v-icon>
                        Regenerate Codes
                    </v-btn>
                </Form>
            </div>

            <v-expand-transition>
                <div v-show="isRecoveryCodesVisible">
                    <div v-if="errors?.length" class="mt-4">
                        <AlertError :errors="errors" />
                    </div>
                    <div v-else class="mt-4">
                        <div
                            ref="recoveryCodeSectionRef"
                            class="rounded-lg bg-grey-lighten-4 pa-4 font-weight-medium font-monospace text-body-2"
                        >
                            <v-row v-if="!recoveryCodesList.length" dense>
                                <v-col v-for="n in 8" :key="n" cols="6" sm="3">
                                    <v-skeleton-loader type="text"></v-skeleton-loader>
                                </v-col>
                            </v-row>
                            <v-row v-else dense>
                                <v-col
                                    v-for="(code, index) in recoveryCodesList"
                                    :key="index"
                                    cols="6"
                                    sm="3"
                                    class="py-1"
                                >
                                    {{ code }}
                                </v-col>
                            </v-row>
                        </div>
                        <p class="text-caption text-grey-darken-1 mt-3">
                            Each recovery code can be used once to access your account and will be removed after use. If you need more, click <strong>Regenerate Codes</strong> above.
                        </p>
                    </div>
                </div>
            </v-expand-transition>
        </v-card-text>
    </v-card>
</template>
