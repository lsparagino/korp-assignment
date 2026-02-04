<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Password',
        href: editPassword(),
    },
    {
        title: 'Two-Factor Auth',
        href: show(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="pa-4 pa-sm-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <v-row class="mt-4">
            <v-col cols="12" md="3" lg="2">
                <v-list nav density="comfortable" class="bg-transparent pa-0">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="toUrl(item.href)"
                        class="text-decoration-none d-block mb-1"
                    >
                        <v-list-item
                            :active="isCurrentUrl(item.href)"
                            color="primary"
                            rounded="lg"
                            variant="text"
                            class="text-grey-darken-3"
                        >
                            <v-list-item-title class="font-weight-medium">
                                {{ item.title }}
                            </v-list-item-title>
                        </v-list-item>
                    </Link>
                </v-list>
            </v-col>

            <v-col cols="12" class="d-md-none">
                <v-divider class="my-4"></v-divider>
            </v-col>

            <v-col cols="12" md="9" lg="10">
                <div class="max-w-xl">
                    <div class="d-flex flex-column ga-12">
                        <slot />
                    </div>
                </div>
            </v-col>
        </v-row>
    </div>
</template>

<style scoped>
.max-w-xl {
    max-width: 576px;
}
</style>
