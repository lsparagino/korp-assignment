<script setup lang="ts">
import { ref, reactive } from 'vue';
import Heading from '@/components/Heading.vue';
import SettingsLayout from '@/components/SettingsLayout.vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/plugins/api';

const authStore = useAuthStore();
const user = authStore.user!;

const form = reactive({
    name: user.name,
    email: user.email,
});

const processing = ref(false);
const recentlySuccessful = ref(false);
const errors = ref<Record<string, string[]>>({});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    recentlySuccessful.value = false;
    
    try {
        const response = await api.put('/user/profile-information', form);
        authStore.setUser(response.data);
        recentlySuccessful.value = true;
        setTimeout(() => recentlySuccessful.value = false, 3000);
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
    <SettingsLayout>
        <div class="d-flex flex-column ga-6">
            <Heading
                variant="small"
                title="Profile information"
                description="Update your name and email address"
            />

            <v-form @submit.prevent="submit">
                <div class="d-flex flex-column ga-4">
                    <v-text-field
                        v-model="form.name"
                        label="Name"
                        placeholder="Full name"
                        name="name"
                        required
                        autocomplete="name"
                        variant="outlined"
                        color="primary"
                        density="comfortable"
                        :error-messages="errors.name"
                        hide-details="auto"
                    ></v-text-field>

                    <v-text-field
                        v-model="form.email"
                        label="Email address"
                        placeholder="Email address"
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                        variant="outlined"
                        color="primary"
                        density="comfortable"
                        :error-messages="errors.email"
                        hide-details="auto"
                    ></v-text-field>

                    <div class="d-flex align-center ga-4 mt-2">
                        <v-btn
                            type="submit"
                            color="primary"
                            variant="flat"
                            class="text-none font-weight-bold"
                            :loading="processing"
                        >
                            Save
                        </v-btn>

                        <v-fade-transition>
                            <p
                                v-show="recentlySuccessful"
                                class="text-body-2 text-grey-darken-1"
                            >
                                Saved.
                            </p>
                        </v-fade-transition>
                    </div>
                </div>
            </v-form>
        </div>
    </SettingsLayout>
</template>

<route lang="yaml">
meta:
  layout: App
</route>
