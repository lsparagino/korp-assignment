<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';

const dialog = ref(false);
const passwordInput = ref<any>(null);

const openDialog = () => {
    dialog.value = true;
};
</script>

<template>
    <div class="d-flex flex-column ga-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and all of its resources"
        />
        
        <v-alert
            type="error"
            variant="tonal"
            rounded="lg"
            class="mb-4"
        >
            <template v-slot:title>
                <div class="font-weight-bold">Warning</div>
            </template>
            Please proceed with caution, this cannot be undone.
        </v-alert>

        <div>
            <v-btn
                color="error"
                variant="flat"
                class="text-none"
                data-test="delete-user-button"
                @click="openDialog"
            >
                Delete account
            </v-btn>
        </div>

        <v-dialog v-model="dialog" max-width="500">
            <v-card rounded="xl" class="pa-4">
                <Form
                    v-bind="ProfileController.destroy.form()"
                    reset-on-success
                    @error="() => passwordInput?.focus()"
                    :options="{
                        preserveScroll: true,
                    }"
                    #default="{ errors, processing, reset, clearErrors }"
                >
                    <v-card-title class="text-h6 font-weight-bold px-4 pt-4">
                        Are you sure you want to delete your account?
                    </v-card-title>
                    
                    <v-card-text class="text-body-2 text-grey-darken-1 px-4 mb-4">
                        Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                    </v-card-text>

                    <v-card-text class="px-4">
                        <v-text-field
                            label="Password"
                            placeholder="Password"
                            type="password"
                            name="password"
                            ref="passwordInput"
                            variant="outlined"
                            color="primary"
                            density="comfortable"
                            :error-messages="errors.password"
                            hide-details="auto"
                            autofocus
                        ></v-text-field>
                    </v-card-text>

                    <v-card-actions class="px-4 pb-4 ga-2">
                        <v-spacer></v-spacer>
                        <v-btn
                            variant="text"
                            color="grey-darken-1"
                            class="text-none"
                            @click="() => {
                                dialog = false;
                                clearErrors();
                                reset();
                            }"
                        >
                            Cancel
                        </v-btn>
                        <v-btn
                            type="submit"
                            color="error"
                            variant="flat"
                            class="text-none"
                            :loading="processing"
                            data-test="confirm-delete-user-button"
                        >
                            Delete account
                        </v-btn>
                    </v-card-actions>
                </Form>
            </v-card>
        </v-dialog>
    </div>
</template>
