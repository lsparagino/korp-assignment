<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Heading from '@/components/Heading.vue';
import SettingsLayout from '@/components/SettingsLayout.vue';
import api from '@/plugins/api';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const enabled = ref(false);
const processing = ref(false);
const qrCode = ref<{ svg: string, url: string } | null>(null);
const recoveryCodes = ref<any[]>([]);

const fetchStatus = async () => {
    enabled.value = !!authStore.user?.two_factor_confirmed_at;
    if (enabled.value) {
        fetchRecoveryCodes();
    }
};

const enable2FA = async () => {
    processing.value = true;
    try {
        await api.post('/user/two-factor-authentication');
        const response = await api.get('/user/two-factor-qr-code');
        qrCode.value = response.data;
        // In a real app, you'd confirm it with a code here
        // For now just mark as enabled for demo
        enabled.value = true;
    } catch (error) {
        console.error('Error enabling 2FA:', error);
    } finally {
        processing.value = false;
    }
};

const disable2FA = async () => {
    processing.value = true;
    try {
        await api.delete('/user/two-factor-authentication');
        enabled.value = false;
        qrCode.value = null;
        recoveryCodes.value = [];
    } catch (error) {
        console.error('Error disabling 2FA:', error);
    } finally {
        processing.value = false;
    }
};

const fetchRecoveryCodes = async () => {
    try {
        const response = await api.get('/user/two-factor-recovery-codes');
        recoveryCodes.value = response.data;
    } catch (error) {
        console.error('Error fetching recovery codes:', error);
    }
};

onMounted(fetchStatus);
</script>

<template>
    <SettingsLayout>
        <div class="d-flex flex-column ga-6">
            <Heading
                variant="small"
                title="Two-Factor Authentication"
                description="Manage your two-factor authentication settings"
            />

            <div v-if="!enabled" class="d-flex flex-column ga-4 align-start">
                <v-chip color="error" size="small" variant="flat" class="font-weight-bold">Disabled</v-chip>
                <p class="text-body-2 text-grey-darken-1">
                    When you enable two-factor authentication, you will be prompted for a secure pin during login.
                </p>
                <v-btn color="primary" variant="flat" class="text-none font-weight-bold" @click="enable2FA" :loading="processing">
                    Enable 2FA
                </v-btn>
            </div>

            <div v-else class="d-flex flex-column ga-4 align-start">
                <v-chip color="success" size="small" variant="flat" class="font-weight-bold">Enabled</v-chip>
                <p class="text-body-2 text-grey-darken-1">
                    Two-factor authentication is enabled. You can manage your recovery codes below.
                </p>
                
                <div v-if="qrCode" class="pa-4 border rounded-lg bg-white mb-4">
                    <div v-html="qrCode.svg"></div>
                </div>

                <div v-if="recoveryCodes.length" class="w-100 pa-4 bg-grey-lighten-4 rounded-lg mb-4">
                    <p class="text-caption font-weight-bold mb-2">Recovery Codes</p>
                    <v-row dense>
                        <v-col v-for="c in recoveryCodes" :key="c.code" cols="6" class="text-caption font-monospace">
                            {{ c.code }}
                        </v-col>
                    </v-row>
                </div>

                <v-btn color="error" variant="flat" class="text-none font-weight-bold" @click="disable2FA" :loading="processing">
                    Disable 2FA
                </v-btn>
            </div>
        </div>
    </SettingsLayout>
</template>

<route lang="yaml">
meta:
  layout: App
</route>
