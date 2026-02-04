<script setup lang="ts">
import { computed, ref, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/plugins/api';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const showRecoveryInput = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string[]>>({});
const code = ref('');
const recoveryCode = ref('');

const authConfigContent = computed(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery Code',
            description: 'Please confirm access to your account by entering one of your emergency recovery codes.',
            buttonText: 'login using an authentication code',
        };
    }
    return {
        title: 'Authentication Code',
        description: 'Enter the authentication code provided by your authenticator application.',
        buttonText: 'login using a recovery code',
    };
});

const toggleRecoveryMode = () => {
    showRecoveryInput.value = !showRecoveryInput.value;
    errors.value = {};
    code.value = '';
    recoveryCode.value = '';
};

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        const payload = showRecoveryInput.value 
            ? { recovery_code: recoveryCode.value } 
            : { code: code.value };
            
        const response = await api.post('/two-factor-challenge', payload);
        
        authStore.setToken(response.data.access_token);
        authStore.setUser(response.data.user);
        router.push('/dashboard');
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    } finally {
        processing.value = false;
    }
};
import { watch } from 'vue';

watch(authConfigContent, (val) => {
    route.meta.title = val.title;
    route.meta.description = val.description;
}, { immediate: true });
</script>

<template>
    <v-form @submit.prevent="submit">
        <div class="d-flex flex-column ga-6">
            <template v-if="!showRecoveryInput">
                <div class="d-flex flex-column ga-4">
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
                        {{ errors.code[0] }}
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
                            @click="toggleRecoveryMode"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="d-flex flex-column ga-4">
                    <v-text-field
                        v-model="recoveryCode"
                        label="Recovery Code"
                        placeholder="Enter recovery code"
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
                            @click="toggleRecoveryMode"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </v-form>
</template>

<route lang="yaml">
meta:
  layout: Auth
</route>
