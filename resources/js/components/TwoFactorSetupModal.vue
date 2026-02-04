<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const { resolvedAppearance } = useAppearance();

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: 'Two-Factor Authentication Enabled',
            description:
                'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            buttonText: 'Close',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Verify Authentication Code',
            description: 'Enter the 6-digit code from your authenticator app',
            buttonText: 'Continue',
        };
    }

    return {
        title: 'Enable Two-Factor Authentication',
        description:
            'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
        buttonText: 'Continue',
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();
            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <v-dialog v-model="isOpen" max-width="500">
        <v-card rounded="xl" class="pa-4">
            <v-card-item class="text-center pb-0">
                <div class="d-flex justify-center mb-4">
                    <v-avatar color="primary" variant="tonal" size="56">
                        <v-icon icon="mdi-qrcode-scan" size="32"></v-icon>
                    </v-avatar>
                </div>
                <v-card-title class="text-h6 font-weight-bold">
                    {{ modalConfig.title }}
                </v-card-title>
                <v-card-subtitle class="text-body-2 text-grey-darken-1 text-wrap mt-1">
                    {{ modalConfig.description }}
                </v-card-subtitle>
            </v-card-item>

            <v-card-text class="pt-6">
                <template v-if="!showVerificationStep">
                    <AlertError v-if="errors?.length" :errors="errors" class="mb-4" />
                    
                    <template v-else>
                        <div class="d-flex justify-center mb-6">
                            <v-card
                                variant="outlined"
                                rounded="lg"
                                class="pa-4 bg-white"
                                width="250"
                                height="250"
                            >
                                <div
                                    v-if="!qrCodeSvg"
                                    class="d-flex align-center justify-center fill-height"
                                >
                                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                                </div>
                                <div
                                    v-else
                                    class="d-flex align-center justify-center fill-height"
                                >
                                    <div
                                        v-html="qrCodeSvg"
                                        class="d-flex align-center justify-center"
                                        :style="{
                                            filter:
                                                resolvedAppearance === 'dark'
                                                    ? 'invert(1) brightness(1.5)'
                                                    : undefined,
                                            width: '100%',
                                            height: '100%'
                                        }"
                                    />
                                </div>
                            </v-card>
                        </div>

                        <v-btn
                            block
                            color="primary"
                            variant="flat"
                            class="text-none mb-6"
                            @click="handleModalNextStep"
                        >
                            {{ modalConfig.buttonText }}
                        </v-btn>

                        <div class="d-flex align-center mb-6">
                            <v-divider></v-divider>
                            <span class="px-4 text-caption text-grey-darken-1 text-no-wrap">
                                or, enter the code manually
                            </span>
                            <v-divider></v-divider>
                        </div>

                        <v-text-field
                            readonly
                            :value="manualSetupKey"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            color="primary"
                            placeholder="Setup Key"
                            persistent-hint
                            :loading="!manualSetupKey"
                        >
                            <template v-slot:append-inner>
                                <v-btn
                                    icon
                                    variant="text"
                                    density="comfortable"
                                    class="mr-n2"
                                    @click="copy(manualSetupKey || '')"
                                >
                                    <v-icon
                                        :color="copied ? 'success' : 'grey-darken-1'"
                                        :icon="copied ? 'mdi-check' : 'mdi-content-copy'"
                                        size="18"
                                    ></v-icon>
                                </v-btn>
                            </template>
                        </v-text-field>
                    </template>
                </template>

                <template v-else>
                    <Form
                        v-bind="confirm.form()"
                        reset-on-error
                        @finish="code = ''"
                        @success="isOpen = false"
                        v-slot="{ errors: formErrors, processing }"
                    >
                        <input type="hidden" name="code" :value="code" />
                        <div
                            ref="pinInputContainerRef"
                            class="d-flex flex-column ga-6 align-center"
                        >
                            <v-otp-input
                                v-model="code"
                                :length="6"
                                :disabled="processing"
                                variant="outlined"
                                color="primary"
                            ></v-otp-input>

                            <InputError
                                :message="formErrors?.confirmTwoFactorAuthentication?.code"
                            />

                            <div class="d-flex ga-4 w-100">
                                <v-btn
                                    variant="outlined"
                                    color="grey-darken-1"
                                    class="text-none flex-grow-1"
                                    @click="showVerificationStep = false"
                                    :disabled="processing"
                                >
                                    Back
                                </v-btn>
                                <v-btn
                                    type="submit"
                                    color="primary"
                                    variant="flat"
                                    class="text-none flex-grow-1"
                                    :loading="processing"
                                    :disabled="code.length < 6"
                                >
                                    Confirm
                                </v-btn>
                            </div>
                        </div>
                    </Form>
                </template>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>
