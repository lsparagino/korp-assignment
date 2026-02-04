<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Wallet } from 'lucide-vue-next';
import api from '@/plugins/api';

const company = ref('');
const wallets = ref<any[]>([]);
const processing = ref(true);

const fetchWallets = async () => {
    try {
        const response = await api.get('/wallets');
        company.value = response.data.company;
        wallets.value = response.data.wallets.map((w: any) => ({
            ...w,
            balanceFormatted: new Intl.NumberFormat('en-US', { style: 'currency', currency: w.currency }).format(w.balance),
            statusColor: w.status === 'Active' ? 'green-lighten-4' : 'red-lighten-4',
            statusTextColor: w.status === 'Active' ? 'green-darken-3' : 'red-darken-3'
        }));
    } catch (error) {
        console.error('Error fetching wallets:', error);
    } finally {
        processing.value = false;
    }
};

onMounted(fetchWallets);
</script>

<template>
    <div class="d-flex align-center justify-space-between mb-8">
        <h1 class="text-h5 font-weight-bold text-grey-darken-2">
            Wallets - {{ company }}
        </h1>
        <v-btn
            prepend-icon="mdi-plus"
            color="primary"
            variant="flat"
            class="text-none font-weight-bold"
            rounded="lg"
            to="/wallets/create"
        >
            Create Wallet
        </v-btn>
    </div>

    <v-card flat border rounded="lg" :loading="processing">
        <v-table density="comfortable">
            <thead class="bg-grey-lighten-4">
                <tr>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Wallet Name</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Balance</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Currency</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="wallet in wallets" :key="wallet.id">
                    <td class="font-weight-bold text-grey-darken-3">
                        <div class="d-flex align-center">
                            <v-avatar color="primary" size="24" rounded="sm" class="me-3">
                                <v-icon :icon="Wallet" size="14" color="white"></v-icon>
                            </v-avatar>
                            {{ wallet.name }}
                        </div>
                    </td>
                    <td class="font-weight-black text-grey-darken-3">
                        {{ wallet.balanceFormatted }}
                    </td>
                    <td>
                        <v-chip color="grey-lighten-3" size="small" variant="flat" class="font-weight-bold text-grey-darken-3">
                            {{ wallet.currency }}
                        </v-chip>
                    </td>
                    <td>
                        <v-chip
                            :color="wallet.statusColor"
                            size="small"
                            variant="flat"
                            class="font-weight-bold"
                        >
                            <span :class="`text-${wallet.statusTextColor}`" class="font-weight-bold">{{ wallet.status }}</span>
                        </v-chip>
                    </td>
                </tr>
            </tbody>
        </v-table>

        <div class="pa-4 d-flex align-center justify-space-between border-t bg-grey-lighten-5">
            <span class="text-caption text-grey-darken-1">Showing {{ wallets.length }} of 100</span>
            <v-pagination :length="3" density="compact" active-color="primary" class="my-0"></v-pagination>
        </div>
    </v-card>
</template>

<route lang="yaml">
meta:
  layout: App
</route>
