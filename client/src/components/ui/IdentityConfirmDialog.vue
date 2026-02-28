<script lang="ts" setup>
  import { useI18n } from 'vue-i18n'

  defineProps<{
    modelValue: boolean
    credential: string
    error: string
    isSubmitting: boolean
    hasTwoFactor: boolean
    title?: string
    description?: string
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'update:credential': [value: string]
    'confirm': []
    'cancel': []
  }>()

  const { t } = useI18n()
</script>

<template>
  <v-dialog
    max-width="440"
    :model-value="modelValue"
    persistent
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6">
        {{ title ?? t('identityConfirm.title') }}
      </v-card-title>
      <v-card-text>
        <p class="text-body-2 mb-4">
          {{ description ?? t('identityConfirm.description') }}
        </p>
        <v-text-field
          autofocus
          data-testid="identity-confirm-input"
          density="comfortable"
          :error-messages="error"
          :label="hasTwoFactor ? t('identityConfirm.authCode') : t('common.password')"
          :model-value="credential"
          :type="hasTwoFactor ? 'text' : 'password'"
          variant="outlined"
          @keyup.enter="emit('confirm')"
          @update:model-value="emit('update:credential', $event as string)"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn
          class="text-none"
          data-testid="identity-confirm-cancel"
          variant="text"
          @click="emit('cancel')"
        >
          {{ t('common.cancel') }}
        </v-btn>
        <v-btn
          class="text-none font-weight-bold"
          color="primary"
          data-testid="identity-confirm-submit"
          :loading="isSubmitting"
          variant="flat"
          @click="emit('confirm')"
        >
          {{ t('common.confirm') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
