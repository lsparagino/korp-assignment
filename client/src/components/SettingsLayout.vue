<script setup lang="ts">
import { useRoute } from 'vue-router';

const route = useRoute();

const settingsNav = [
    { title: 'Profile', to: '/settings/profile', icon: 'mdi-account' },
    { title: 'Password', to: '/settings/password', icon: 'mdi-lock' },
    { title: 'Two-Factor', to: '/settings/two-factor', icon: 'mdi-shield-check' },
];
</script>

<template>
    <v-row class="ma-0">
        <v-col cols="12" md="3" class="pa-0 pr-md-4 mb-4 mb-md-0">
            <v-list nav rounded="lg" bg-color="white" border>
                <v-list-item
                    v-for="item in settingsNav"
                    :key="item.to"
                    :to="item.to"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    color="primary"
                    rounded="lg"
                    class="mb-1"
                ></v-list-item>
            </v-list>
        </v-col>
        <v-col cols="12" md="9" class="pa-0">
            <v-card flat border rounded="lg" class="pa-4 pa-md-8">
                <slot />
            </v-card>
        </v-col>
    </v-row>
</template>
