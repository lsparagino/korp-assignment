<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ExternalLink } from 'lucide-vue-next';
import api from '@/plugins/api';

const company = ref('');
const members = ref<any[]>([]);
const processing = ref(true);

const fetchTeam = async () => {
    try {
        const response = await api.get('/team-members');
        company.value = response.data.company;
        members.value = response.data.members;
    } catch (error) {
        console.error('Error fetching team:', error);
    } finally {
        processing.value = false;
    }
};

const getRoleColor = (role: string) => {
    switch (role) {
        case 'Admin': return 'grey-lighten-2';
        case 'Member': return 'blue-lighten-4';
        default: return 'grey-lighten-4';
    }
};

const getRoleTextColor = (role: string) => {
    switch (role) {
        case 'Admin': return 'grey-darken-3';
        case 'Member': return 'blue-darken-3';
        default: return 'grey-darken-1';
    }
};

onMounted(fetchTeam);
</script>

<template>
    <div class="d-flex align-center justify-space-between mb-8">
        <h1 class="text-h5 font-weight-bold text-grey-darken-2">
            Team Members - {{ company }}
        </h1>
        <v-btn
            prepend-icon="mdi-plus"
            color="primary"
            variant="flat"
            class="text-none font-weight-bold"
            rounded="lg"
        >
            Add Member
        </v-btn>
    </div>

    <v-card flat border rounded="lg" :loading="processing">
        <v-table density="comfortable">
            <thead class="bg-grey-lighten-4">
                <tr>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Name</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Email</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Role</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Wallet Access</th>
                    <th class="text-left text-grey-darken-1 text-uppercase text-caption font-weight-bold">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="member in members" :key="member.id">
                    <td class="font-weight-bold text-grey-darken-3">
                        {{ member.name }}
                    </td>
                    <td class="text-grey-darken-2">{{ member.email }}</td>
                    <td>
                        <v-chip
                            :color="getRoleColor(member.role)"
                            size="small"
                            variant="flat"
                            class="font-weight-bold"
                        >
                            <span :class="`text-${getRoleTextColor(member.role)}`" class="font-weight-bold">{{ member.role }}</span>
                        </v-chip>
                    </td>
                    <td>
                        <v-chip
                            color="green-lighten-5"
                            size="small"
                            variant="flat"
                            class="font-weight-bold"
                        >
                            <span class="text-green-darken-3 font-weight-bold">{{ member.wallet_access }}</span>
                        </v-chip>
                    </td>
                    <td>
                        <v-btn
                            variant="text"
                            color="primary"
                            density="compact"
                            class="text-none font-weight-black"
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

        <div class="pa-4 d-flex align-center justify-space-between border-t bg-grey-lighten-5">
            <span class="text-caption text-grey-darken-1">Showing {{ members.length }} of 100</span>
            <v-pagination
                :length="3"
                density="compact"
                active-color="primary"
                class="my-0"
                rounded="sm"
            ></v-pagination>
        </div>
    </v-card>
</template>

<route lang="yaml">
meta:
  layout: App
</route>
