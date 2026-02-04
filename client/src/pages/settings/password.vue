<script setup lang="ts">
import { ref, reactive } from 'vue';
import Heading from '@/components/Heading.vue';
import SettingsLayout from '@/components/SettingsLayout.vue';
import api from '@/plugins/api';

const form = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const processing = ref(false);
const recentlySuccessful = ref(false);
const errors = ref<Record<string, string[]>>({});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    recentlySuccessful.value = false;
    
    try {
        await api.put('/user/password', form);
        recentlySuccessful.value = true;
        Object.assign(form, { current_password: '', password: '', password_confirmation: '' });
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
                title="Update password"
                description="Ensure your account is using a long, random password to stay secure"
            />

            <v-form @submit.prevent="submit">
                <div class="d-flex flex-column ga-4">
                    <v-text-field
                        v-model="form.current_password"
                        label="Current password"
                        placeholder="Current password"
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                        variant="outlined"
                        color="primary"
                        density="comfortable"
                        :error-messages="errors.current_password"
                        hide-details="auto"
                    ></v-text-field>

                    <v-text-field
                        v-model="form.password"
                        label="New password"
                        placeholder="New password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        variant="outlined"
                        color="primary"
                        density="comfortable"
                        :error-messages="errors.password"
                        hide-details="auto"
                    ></v-text-field>

                    <v-text-field
                        v-model="form.password_confirmation"
                        label="Confirm password"
                        placeholder="Confirm password"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        variant="outlined"
                        color="primary"
                        density="comfortable"
                        :error-messages="errors.password_confirmation"
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
                            Save password
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
