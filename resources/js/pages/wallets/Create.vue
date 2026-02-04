<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index as walletsIndex } from '@/routes/wallets';

interface Props {
    company: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wallets',
        href: walletsIndex().url,
    },
    {
        title: 'Create Wallet',
        href: '#',
    },
];

const form = useForm({
    name: '',
    currency: 'USD',
    initial_balance: '0.00',
    status: 'Active',
});

const currencies = ['USD', 'EUR', 'GBP', 'JPY'];
const statuses = ['Active', 'Frozen', 'Inactive'];

const submit = () => {
    // Mock submit logic
    console.log('Form submitted:', form.data());
};
</script>

<template>
    <Head title="Create Wallet" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <v-container fluid class="pa-0">
            <v-card flat border rounded="lg">
                <v-card-title class="pa-6 border-b">
                    <h1 class="text-h6 font-weight-regular text-grey-darken-3">
                        Create New Wallet for {{ company }}
                    </h1>
                </v-card-title>

                <v-card-text class="pa-6">
                    <v-form @submit.prevent="submit">
                        <v-row>
                            <v-col cols="12">
                                <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                                    Wallet Name <span class="text-error">*</span>
                                </label>
                                <v-text-field
                                    v-model="form.name"
                                    placeholder="Enter wallet name"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    color="primary"
                                    hide-details="auto"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" md="6">
                                <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                                    Currency <span class="text-error">*</span>
                                </label>
                                <v-select
                                    v-model="form.currency"
                                    :items="currencies"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    color="primary"
                                    hide-details="auto"
                                >
                                    <template v-slot:append-inner>
                                        <v-icon :icon="ChevronDown" size="18"></v-icon>
                                    </template>
                                </v-select>
                            </v-col>

                            <v-col cols="12" md="6">
                                <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                                    Initial Balance
                                </label>
                                <v-text-field
                                    v-model="form.initial_balance"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    color="primary"
                                    hide-details="auto"
                                >
                                    <template v-slot:append-inner>
                                        <v-divider vertical class="mx-2"></v-divider>
                                        <span class="text-caption font-weight-bold text-grey-darken-1 mr-2">{{ form.currency }}</span>
                                        <v-icon :icon="ChevronDown" size="14"></v-icon>
                                    </template>
                                </v-text-field>
                            </v-col>

                            <v-col cols="12" md="6">
                                <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                                    Status <span class="text-error">*</span>
                                </label>
                                <v-select
                                    v-model="form.status"
                                    :items="statuses"
                                    variant="outlined"
                                    density="comfortable"
                                    rounded="lg"
                                    color="primary"
                                    hide-details="auto"
                                >
                                    <template v-slot:append-inner>
                                        <v-icon :icon="ChevronDown" size="18"></v-icon>
                                    </template>
                                </v-select>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>

                <v-card-actions class="pa-6 border-t justify-end">
                    <Link :href="walletsIndex().url">
                        <v-btn
                            variant="outlined"
                            color="grey-darken-1"
                            class="text-none mr-4"
                            rounded="lg"
                        >
                            Cancel
                        </v-btn>
                    </Link>
                    <v-btn
                        color="primary"
                        class="text-none font-weight-bold px-6"
                        rounded="lg"
                        prepend-icon="mdi-plus"
                        @click="submit"
                    >
                        Create Wallet
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-container>
    </AppLayout>
</template>
