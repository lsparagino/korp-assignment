<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Wallet } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const totalBalances = [
    { amount: '$12,500.00', currency: 'USD' },
    { amount: '€ 8,750.00', currency: 'EUR' },
];

const wallets = [
    { name: 'Main Wallet', balance: '$7,200.00', currency: 'USD', color: 'primary' },
    { name: 'EUR Wallet', balance: '€4,500.00', currency: 'EUR', color: 'blue-darken-3' },
    { name: 'Marketing Wallet', balance: '$5,300.00', currency: 'USD', color: 'green-darken-2' },
];

const transactions = [
    { date: '12/10/2022', wallet: 'Main Wallet', type: 'Debit', amount: '-$500.00', reference: 'Invoice #123', amountColor: 'red-darken-1' },
    { date: '12/09/2022', wallet: 'EUR Wallet', type: 'Credit', amount: '€1,000.00', reference: 'Client Payment', amountColor: 'green-darken-1' },
    { date: '12/09/2022', wallet: 'Marketing Wallet', type: 'Debit', amount: '-$200.00', reference: 'Advertising', amountColor: 'red-darken-1' },
    { date: '12/07/2022', wallet: 'Main Wallet', type: 'Credit', amount: '€2,500.00', reference: 'Transfer', amountColor: 'green-darken-1' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mb-8">
            <h1 class="text-h5 font-weight-bold text-grey-darken-2">Dashboard – Acme Corp</h1>
        </div>

        <!-- Total Balance Card -->
        <v-card flat border rounded="lg" class="mb-6 pa-6">
            <div class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-6">Total Balance</div>
            <div v-for="balance in totalBalances" :key="balance.currency" class="mb-2">
                <span class="text-h4 font-weight-black mr-2">{{ balance.amount }}</span>
                <span class="text-subtitle-1 font-weight-medium text-grey-darken-1">{{ balance.currency }}</span>
            </div>
        </v-card>

        <!-- Wallets Grid -->
        <v-row class="mb-6">
            <v-col v-for="wallet in wallets" :key="wallet.name" cols="12" md="4">
                <v-card flat border rounded="lg" class="pa-4">
                    <div class="d-flex align-center mb-6">
                        <v-avatar :color="wallet.color" size="32" rounded="lg" class="me-3">
                            <v-icon :icon="Wallet" size="18" color="white"></v-icon>
                        </v-avatar>
                        <span class="font-weight-bold text-grey-darken-3">{{ wallet.name }}</span>
                    </div>
                    <div>
                        <span class="text-h5 font-weight-black mr-2">{{ wallet.balance }}</span>
                        <span class="text-caption font-weight-bold text-grey-darken-1 text-uppercase">{{ wallet.currency }}</span>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Recent Transactions -->
        <v-card flat border rounded="lg">
            <div class="pa-4 border-b">
                <div class="text-subtitle-1 font-weight-bold text-grey-darken-3">Recent Transactions</div>
            </div>

            <v-table density="comfortable">
                <thead class="bg-grey-lighten-4">
                    <tr>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Date</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">From/To</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Type</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Amount</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in transactions" :key="item.reference">
                        <td class="text-grey-darken-2">{{ item.date }}</td>
                        <td>
                            <div class="d-flex align-center">
                                <v-avatar rounded="sm" size="20" color="primary" class="me-2">
                                    <v-icon :icon="Wallet" size="12" color="white"></v-icon>
                                </v-avatar>
                                <span class="text-caption text-grey-darken-2 font-weight-medium">{{ item.wallet }}</span>
                            </div>
                        </td>
                        <td class="text-grey-darken-2">{{ item.type }}</td>
                        <td :class="`text-${item.amountColor} font-weight-bold`">{{ item.amount }}</td>
                        <td class="text-grey-darken-2">{{ item.reference }}</td>
                    </tr>
                </tbody>
            </v-table>

            <div class="pa-4 d-flex align-center justify-space-between border-t bg-grey-lighten-4">
                <span class="text-caption text-grey-darken-1">Showing 2 of 100</span>
                <v-pagination :length="3" density="compact" active-color="primary" class="my-0"></v-pagination>
            </div>
        </v-card>
    </AppLayout>
</template>
