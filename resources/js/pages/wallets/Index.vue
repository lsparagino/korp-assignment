<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Wallet } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { create as createWallet } from '@/routes/wallets';

interface WalletItem {
    id: number;
    name: string;
    currency: string;
    balance: number;
    status: 'Active' | 'Frozen';
}

interface Props {
    company: string;
    wallets: WalletItem[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wallets',
        href: '#',
    },
];

const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Active':
            return 'success';
        case 'Frozen':
            return 'primary';
        default:
            return 'grey';
    }
};
</script>

<template>
    <Head title="Wallets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="d-flex align-center justify-space-between mb-8">
            <h1 class="text-h5 font-weight-bold text-grey-darken-2">
                Wallets – {{ company }}
            </h1>
            <Link :href="createWallet().url">
                <v-btn
                    prepend-icon="mdi-plus"
                    color="primary"
                    class="text-none font-weight-bold"
                    rounded="lg"
                >
                    Create Wallet
                </v-btn>
            </Link>
        </div>

        <v-card flat border rounded="lg">
            <v-table density="comfortable">
                <thead class="bg-grey-lighten-4">
                    <tr>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Name</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Currency</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Balance</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Status</th>
                        <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="wallet in wallets" :key="wallet.id">
                        <td class="font-weight-bold text-grey-darken-3">
                            {{ wallet.name }}
                        </td>
                        <td class="text-grey-darken-2">{{ wallet.currency }}</td>
                        <td class="font-weight-black text-grey-darken-3">
                            {{ formatCurrency(wallet.balance, wallet.currency) }}
                        </td>
                        <td>
                            <v-chip
                                :color="getStatusColor(wallet.status)"
                                size="small"
                                variant="tonal"
                                class="font-weight-bold"
                            >
                                {{ wallet.status }}
                            </v-chip>
                        </td>
                        <td>
                            <v-btn
                                variant="text"
                                color="primary"
                                density="compact"
                                class="text-none font-weight-black"
                                prefetch
                            >
                                EDIT
                                <v-icon
                                    end
                                    :icon="ExternalLink"
                                    size="14"
                                    class="ms-1"
                                ></v-icon>
                            </v-btn>
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <div class="pa-4 d-flex align-center justify-space-between border-t bg-grey-lighten-4">
                <span class="text-caption text-grey-darken-1">Showing 3 of 100</span>
                <v-pagination :length="3" density="compact" active-color="primary" class="my-0"></v-pagination>
            </div>
        </v-card>
    </AppLayout>
</template>
