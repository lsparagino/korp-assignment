<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { deleteAccount } from '@/api/settings'
  import Heading from '@/components/ui/Heading.vue'
  import { useFormSubmit } from '@/composables/useFormSubmit'
  import { useAuthStore } from '@/stores/auth'

  useI18n()
  const authStore = useAuthStore()
  const dialog = ref(false)
  const form = reactive({ password: '' })

  const { processing, errors, submit } = useFormSubmit({
    submitFn: (data: typeof form) => deleteAccount(data),
    onSuccess: () => {
      authStore.clearToken()
      window.location.href = '/'
    },
  })
</script>

<template>
  <div class="mt-8">
    <Heading
      :description="$t('deleteUser.description')"
      :title="$t('deleteUser.title')"
      variant="small"
    />

    <v-btn
      class="text-none font-weight-bold"
      color="error"
      variant="tonal"
      @click="dialog = true"
    >
      {{ $t('deleteUser.button') }}
    </v-btn>

    <v-dialog v-model="dialog" max-width="500">
      <v-card rounded="lg">
        <v-card-item class="pa-6">
          <v-card-title class="text-h5 font-weight-bold mb-1">
            {{ $t('deleteUser.dialogTitle') }}
          </v-card-title>
          <v-card-subtitle class="text-body-1 text-wrap opacity-100">
            {{ $t('deleteUser.dialogMessage') }}
          </v-card-subtitle>

          <v-form class="mt-6" @submit.prevent="submit(form)">
            <v-text-field
              v-model="form.password"
              autocomplete="current-password"
              color="primary"
              density="comfortable"
              :error-messages="errors.password"
              hide-details="auto"
              :label="$t('common.password')"
              :placeholder="$t('common.password')"
              required
              type="password"
              variant="outlined"
            />

            <div class="d-flex ga-3 mt-8 justify-end">
              <v-btn
                class="text-none font-weight-bold"
                variant="text"
                @click="dialog = false"
              >
                {{ $t('common.cancel') }}
              </v-btn>
              <v-btn
                class="text-none font-weight-bold"
                color="error"
                :loading="processing"
                type="submit"
                variant="flat"
              >
                {{ $t('deleteUser.button') }}
              </v-btn>
            </div>
          </v-form>
        </v-card-item>
      </v-card>
    </v-dialog>
  </div>
</template>
