<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, ChevronDown, Repeat, Wallet } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index as transactionsIndex } from '@/routes/transactions';

interface TransactionItem {
    id: number;
    date: string;
    wallet: string;
    type: 'Debit' | 'Credit';
    amount: number;
    currency: string;
    reference: string;
}

interface Props {
    company: string;
    transactions: TransactionItem[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: '#',
    },
];

const filterForm = useForm({
    date_from: '',
    date_to: '',
    type: 'All',
});

const types = ['All', 'Debit', 'Credit'];

const formatCurrency = (amount: number, currency: string) => {
    const formatted = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD', // Using USD for formatting as per screenshot symbols
    }).format(Math.abs(amount));

    if (currency === 'EUR') {
        return `€${formatted.substring(1)}`;
    }
    return amount < 0 ? `-$${formatted.substring(1)}` : `$${formatted.substring(1)}`;
};

const getAmountColor = (amount: number) => {
    return amount < 0 ? 'text-red-darken-1' : 'text-green-darken-1';
};
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mb-8">
            <h1 class="text-h5 font-weight-bold text-grey-darken-2">
                Transactions - {{ company }}
            </h1>
        </div>

        <!-- Filter Card -->
        <v-card flat border rounded="lg" class="mb-6">
            <v-card-text class="pa-6">
                <v-row>
                    <v-col cols="12" md="4">
                        <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                            Date From
                        </label>
                        <v-text-field
                            v-model="filterForm.date_from"
                            placeholder=" / / "
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details
                        >
                            <template v-slot:append-inner>
                                <v-icon :icon="Calendar" size="18" color="grey-darken-1"></v-icon>
                            </template>
                        </v-text-field>
                    </v-col>

                    <v-col cols="12" md="4">
                        <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                            Date To
                        </label>
                        <v-text-field
                            v-model="filterForm.date_to"
                            placeholder=" / / "
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details
                        >
                            <template v-slot:append-inner>
                                <v-icon :icon="Calendar" size="18" color="grey-darken-1"></v-icon>
                            </template>
                        </v-text-field>
                    </v-col>

                    <v-col cols="12" md="4">
                        <label class="text-caption font-weight-bold text-grey-darken-2 mb-2 d-block">
                            Type
                        </label>
                        <v-select
                            v-model="filterForm.type"
                            :items="types"
                            variant="outlined"
                            density="comfortable"
                            rounded="lg"
                            hide-details
                        >
                            <template v-slot:append-inner>
                                <v-icon :icon="ChevronDown" size="18"></v-icon>
                            </template>
                        </v-select>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions class="pa-4 bg-grey-lighten-5 justify-end">
                <v-btn
                    variant="outlined"
                    color="grey-darken-1"
                    class="text-none mr-2"
                    rounded="lg"
                    @click="filterForm.reset()"
                >
                    Clear
                </v-btn>
                <v-btn
                    color="primary"
                    variant="flat"
                    class="text-none font-weight-bold px-6"
                    rounded="lg"
                >
                    Filter
                </v-btn>
            </v-card-actions>
        </v-card>

        <!-- Transactions List Card -->
        <v-card flat border rounded="lg">
            <v-card-title class="pa-4 border-b bg-grey-lighten-5">
                <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">Transactions List</span>
            </v-card-title>

            <v-table density="comfortable">
                <thead>
                    <tr>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Date</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">To</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Type</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Amount</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in transactions" :key="item.id">
                        <td class="text-grey-darken-2">{{ item.date }}</td>
                        <td>
                            <div class="d-flex align-center">
                                <v-avatar rounded="sm" size="20" color="primary" class="me-2">
                                    <v-icon icon="mdi-wallet" size="12" color="white"></v-icon>
                                </v-avatar>
                                <span class="text-caption text-grey-darken-2 font-weight-medium">{{ item.wallet }}</span>
                            </div>
                        </td>
                        <td class="text-grey-darken-3 font-weight-bold">{{ item.type }}</td>
                        <td :class="[getAmountColor(item.amount), 'font-weight-black']">
                            {{ formatCurrency(item.amount, item.currency) }}
                        </td>
                        <td class="text-grey-darken-2">{{ item.reference }}</td>
                    </tr>
                </tbody>
            </v-table>

            <div class="pa-4 d-flex align-center justify-space-between border-t bg-grey-lighten-5">
                <span class="text-caption text-grey-darken-1">Showing 1 to 4 of 100 transactions</span>
                <v-pagination
                    :length="3"
                    density="compact"
                    active-color="primary"
                    class="my-0"
                    rounded="sm"
                ></v-pagination>
            </div>
        </v-card>
    </AppLayout>
</template>
