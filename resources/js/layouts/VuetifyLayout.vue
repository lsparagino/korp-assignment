<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    ChevronDown,
    LayoutDashboard,
    Repeat,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';
import { create as walletsCreate, index as walletsIndex } from '@/routes/wallets';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as teamIndex } from '@/routes/team';
import type { BreadcrumbItem } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const drawer = ref(true);

const navItems = [
    {
        title: 'Dashboard',
        icon: LayoutDashboard,
        href: dashboard().url,
        active: computed(() => page.url === dashboard().url),
    },
    {
        title: 'Wallets',
        icon: Wallet,
        href: walletsIndex().url,
        active: computed(() => page.url.startsWith('/wallets')),
    },
    {
        title: 'Transactions',
        icon: Repeat,
        href: transactionsIndex().url,
        active: computed(() => page.url.startsWith('/transactions')),
    },
    {
        title: 'Team Members',
        icon: Users,
        href: teamIndex().url,
        active: computed(() => page.url.startsWith('/team-members')),
    },
];

const selectedCompany = ref('Acme Corp');
const companies = ['Acme Corp', 'Globex Inc', 'Soylent Corp'];

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <v-app>
        <v-layout class="rounded-md border">
            <v-app-bar>
                <div
                    class="d-flex h-100 w-100 justify-space-between overflow-hidden"
                >
                    <AppLogo></AppLogo>

                    <div class="d-flex align-center ga-4 px-6">
                        <!-- Company Selector -->
                        <v-menu offset-y>
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    variant="outlined"
                                    v-bind="props"
                                    class="text-none border-grey-lighten-2 text-grey-darken-3"
                                    rounded="lg"
                                >
                                    {{ selectedCompany }}
                                    <v-icon
                                        end
                                        :icon="ChevronDown"
                                        size="18"
                                    ></v-icon>
                                </v-btn>
                            </template>
                            <v-list>
                                <v-list-item
                                    v-for="company in companies"
                                    :key="company"
                                    @click="selectedCompany = company"
                                >
                                    <v-list-item-title>{{
                                        company
                                    }}</v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-menu>

                        <!-- Notifications -->
                        <v-btn icon variant="text" color="grey-darken-2">
                            <v-icon :icon="Bell"></v-icon>
                        </v-btn>

                        <!-- User Avatar -->

                        <v-menu min-width="200px">
                            <template v-slot:activator="{ props }">
                                <v-btn icon v-bind="props">
                                    <v-avatar
                                        size="large"
                                        class="bg-sidebar-bg"
                                    >
                                        <v-img
                                            v-if="auth.user?.avatar"
                                            :src="auth.user.avatar"
                                            :alt="auth.user.name"
                                        ></v-img>
                                        <v-icon
                                            v-else
                                            icon="mdi-account"
                                        ></v-icon>
                                    </v-avatar>
                                </v-btn>
                            </template>
                            <v-card>
                                <v-card-text>
                                    <div class="pa-2">
                                        <div class="d-flex align-center ga-4">
                                            <v-avatar
                                                size="36"
                                                class="bg-sidebar-bg border-grey-lighten-2"
                                            >
                                                <v-img
                                                    v-if="auth.user?.avatar"
                                                    :src="auth.user.avatar"
                                                    :alt="auth.user.name"
                                                ></v-img>
                                                <v-icon
                                                    v-else
                                                    icon="mdi-account"
                                                ></v-icon>
                                            </v-avatar>
                                            <div>
                                                <h3
                                                    class="text-subtitle-1 font-weight-bold"
                                                >
                                                    {{ auth.user.name }}
                                                </h3>
                                                <p
                                                    class="text-caption text-grey-darken-1"
                                                >
                                                    {{ auth.user.email }}
                                                </p>
                                            </div>
                                        </div>

                                        <v-divider class="my-3"></v-divider>

                                        <v-list class="py-0" :lines="false">
                                            <Link
                                                class="d-block w-100 cursor-pointer"
                                                :href="edit()"
                                                as="button"
                                                prefetch
                                            >
                                                <v-list-item
                                                    color="primary"
                                                    class="px-0"
                                                >
                                                    <template v-slot:prepend>
                                                        <v-icon
                                                            icon="mdi-cog"
                                                        ></v-icon>
                                                    </template>

                                                    <v-list-item-title
                                                        class="text-start"
                                                    >
                                                        Settings
                                                    </v-list-item-title>
                                                </v-list-item>
                                            </Link>
                                            <Link
                                                class="d-block w-100 cursor-pointer"
                                                :href="logout()"
                                                @click="handleLogout"
                                                data-test="logout-button"
                                            >
                                                <v-list-item class="px-0">
                                                    <template v-slot:prepend>
                                                        <v-icon
                                                            icon="mdi-logout"
                                                            color="error"
                                                        ></v-icon>
                                                    </template>

                                                    <v-list-item-title
                                                        class="text-error text-start"
                                                    >
                                                        Disconnect
                                                    </v-list-item-title>
                                                </v-list-item>
                                            </Link>
                                        </v-list>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-menu>
                    </div>
                </div>
            </v-app-bar>

            <v-navigation-drawer
                v-model="drawer"
                app
                permanent
                width="260"
                color="sidebar-bg"
                class="border-e-sm"
            >
                <v-list nav density="comfortable" class="list-container">
                    <v-list-item
                        v-for="item in navItems"
                        :key="item.title"
                        :value="item.title"
                        :active="item.active?.value"
                        :href="item.href"
                        variant="text"
                        active-color="primary"
                        class="rounded-0 mb-1 rounded-tr-sm rounded-br-sm"
                    >
                        <template v-slot:prepend>
                            <v-icon
                                :icon="item.icon"
                                size="20"
                                class="mr-0 ms-4"
                            ></v-icon>
                        </template>
                        <v-list-item-title
                            class="text-body-1 font-normal text-grey-darken-3"
                            >{{ item.title }}</v-list-item-title
                        >
                    </v-list-item>
                </v-list>

                <template v-slot:append>
                    <v-divider></v-divider>
                    <div class="pa-4">
                        <Link :href="walletsCreate().url" class="btn-link">
                            <v-btn
                                prepend-icon="mdi-plus"
                                class="text-none"
                                color="primary"
                                size="large"
                                block
                            >
                                Create Wallet
                            </v-btn>
                        </Link>
                    </div>
                </template>
            </v-navigation-drawer>

            <v-main>
                <v-container>
                    <slot />
                </v-container>
            </v-main>
        </v-layout>
    </v-app>
</template>

<style scoped>
.list-container {
    background: transparent;
    padding: 1rem 1rem 1rem 0;
}

:deep(.v-list-item--active) {
    background-color: #dfe4ee !important;
    color: #191f2f !important;
    border-left: 4px solid rgb(var(--v-theme-primary));
}

:deep(.v-list-item--active .v-icon) {
    color: rgb(var(--v-theme-primary)) !important;
}

.cursor-pointer {
    cursor: pointer;
}
</style>
