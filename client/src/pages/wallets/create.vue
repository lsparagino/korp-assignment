<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { Wallet } from 'lucide-vue-next';
import api from '@/plugins/api';

const router = useRouter();
const processing = ref(false);
const errors = ref<Record<string, string[]>>({});

const form = reactive({
    name: '',
    currency: 'USD',
});

const currencies = [
    { title: 'US Dollar (USD)', value: 'USD' },
    { title: 'Euro (EUR)', value: 'EUR' },
    { title: 'British Pound (GBP)', value: 'GBP' },
];

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await api.post('/wallets', form);
        router.push('/wallets/');
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
    <div class="mb-8">
        <v-btn
            variant="text"
            color="primary"
            prepend-icon="mdi-arrow-left"
            class="mb-4 text-none px-0"
            to="/wallets/"
        >
            Back to Wallets
        </v-btn>
        <h1 class="text-h5 font-weight-bold text-grey-darken-2">Create New Wallet</h1>
    </div>

    <v-card flat border rounded="lg" class="pa-8">
        <v-form @submit.prevent="submit">
            <v-row>
                <v-col cols="12" md="6">
                    <div class="d-flex flex-column ga-6">
                        <v-text-field
                            v-model="form.name"
                            label="Wallet Name"
                            placeholder="e.g. Savings, Marketing, Operations"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.name"
                            hide-details="auto"
                            required
                            autofocus
                        >
                            <template v-slot:prepend-inner>
                                <v-icon :icon="Wallet" size="20" color="grey-darken-1"></v-icon>
                            </template>
                        </v-text-field>

                        <v-select
                            v-model="form.currency"
                            :items="currencies"
                            label="Base Currency"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.currency"
                            hide-details="auto"
                            required
                        ></v-select>

                        <div class="d-flex ga-4 mt-4">
                            <v-btn
                                type="submit"
                                color="primary"
                                height="48"
                                rounded="lg"
                                class="text-none font-weight-bold px-8"
                                :loading="processing"
                            >
                                Create Wallet
                            </v-btn>
                            <v-btn
                                variant="outlined"
                                color="grey-darken-1"
                                height="48"
                                rounded="lg"
                                class="text-none font-weight-bold px-8"
                                to="/wallets/"
                            >
                                Cancel
                            </v-btn>
                        </div>
                    </div>
                </v-col>
                
                <v-col cols="12" md="6" class="d-none d-md-flex align-center justify-center">
                    <v-sheet
                        color="grey-lighten-4"
                        rounded="xl"
                        class="pa-8 text-center w-100"
                        max-width="320"
                    >
                        <v-icon :icon="Wallet" size="64" color="grey-lighten-1" class="mb-4"></v-icon>
                        <div class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-2">Wallet Preview</div>
                        <p class="text-body-2 text-grey-darken-1 mb-6">
                            You're creating a new wallet. You'll be able to receive and send payments in {{ form.currency }} once it's created.
                        </p>
                    </v-sheet>
                </v-col>
            </v-row>
        </v-form>
    </v-card>
</template>

<route lang="yaml">
meta:
  layout: App
</route>
