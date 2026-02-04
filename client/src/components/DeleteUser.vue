<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Heading from '@/components/Heading.vue';
import api from '@/plugins/api';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const dialog = ref(false);
const processing = ref(false);
const password = ref('');
const errors = ref<Record<string, string[]>>({});

const deleteAccount = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await api.delete('/user', { data: { password: password.value } });
        authStore.clearToken();
        router.push('/');
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="mt-12 pt-12 border-t">
        <Heading
            variant="small"
            title="Delete Account"
            description="Permanently delete your account. Any data you have in SecureWallet will be lost forever."
        />

        <v-btn color="error" variant="flat" class="text-none font-weight-bold" @click="dialog = true">
            Delete Account
        </v-btn>

        <v-dialog v-model="dialog" max-width="500">
            <v-card rounded="xl" class="pa-4">
                <v-card-title class="text-h6 font-weight-bold">Are you sure?</v-card-title>
                <v-card-text>
                    Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.

                    <v-text-field
                        v-model="password"
                        label="Password"
                        type="password"
                        variant="outlined"
                        class="mt-4"
                        color="primary"
                        density="comfortable"
                        :error-messages="errors.password"
                        hide-details="auto"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="dialog = false" class="text-none">Cancel</v-btn>
                    <v-btn color="error" variant="flat" @click="deleteAccount" :loading="processing" class="text-none px-6">Delete Account</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
