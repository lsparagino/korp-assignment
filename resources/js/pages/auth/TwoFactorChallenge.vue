<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery Code',
            description:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
            buttonText: 'login using an authentication code',
        };
    }

    return {
        title: 'Authentication Code',
        description:
            'Enter the authentication code provided by your authenticator application.',
        buttonText: 'login using a recovery code',
    };
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};

const code = ref<string>('');
</script>

<template>
    <AuthLayout
        :title="authConfigContent.title"
        :description="authConfigContent.description"
    >
        <Head title="Two-Factor Authentication" />

        <div class="d-flex flex-column ga-6">
            <template v-if="!showRecoveryInput">
                <Form
                    v-bind="store.form()"
                    reset-on-error
                    @error="code = ''"
                    #default="{ errors, processing, clearErrors }"
                >
                    <div class="d-flex flex-column ga-4">
                        <input type="hidden" name="code" :value="code" />
                        <div class="d-flex justify-center">
                            <v-otp-input
                                v-model="code"
                                length="6"
                                :disabled="processing"
                                autofocus
                                color="primary"
                            ></v-otp-input>
                        </div>
                        <v-alert
                            v-if="errors.code"
                            type="error"
                            variant="tonal"
                            density="compact"
                        >
                            {{ errors.code }}
                        </v-alert>

                        <v-btn
                            type="submit"
                            block
                            color="primary"
                            height="48"
                            rounded="lg"
                            class="text-none font-weight-bold"
                            :disabled="processing"
                        >
                            Continue
                        </v-btn>

                        <div class="text-center">
                            <span class="text-body-2 text-grey-darken-1">or you can </span>
                            <button
                                type="button"
                                class="text-body-2 text-primary font-weight-bold text-decoration-underline"
                                @click="() => toggleRecoveryMode(clearErrors)"
                            >
                                {{ authConfigContent.buttonText }}
                            </button>
                        </div>
                    </div>
                </Form>
            </template>

            <template v-else>
                <Form
                    v-bind="store.form()"
                    reset-on-error
                    #default="{ errors, processing, clearErrors }"
                >
                    <div class="d-flex flex-column ga-4">
                        <v-text-field
                            name="recovery_code"
                            label="Recovery Code"
                            placeholder="Enter recovery code"
                            :autofocus="showRecoveryInput"
                            required
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.recovery_code"
                            hide-details="auto"
                        ></v-text-field>

                        <v-btn
                            type="submit"
                            block
                            color="primary"
                            height="48"
                            rounded="lg"
                            class="text-none font-weight-bold"
                            :disabled="processing"
                        >
                            Continue
                        </v-btn>

                        <div class="text-center">
                            <span class="text-body-2 text-grey-darken-1">or you can </span>
                            <button
                                type="button"
                                class="text-body-2 text-primary font-weight-bold text-decoration-underline"
                                @click="() => toggleRecoveryMode(clearErrors)"
                            >
                                {{ authConfigContent.buttonText }}
                            </button>
                        </div>
                    </div>
                </Form>
            </template>
        </div>
    </AuthLayout>
</template>
