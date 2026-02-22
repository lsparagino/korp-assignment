<script lang="ts" setup>
  import { computed, onMounted, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'

  interface Props {
    modelValue: boolean
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    requiresPin?: boolean
    confirmColor?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    title: undefined,
    message: undefined,
    confirmText: undefined,
    cancelText: undefined,
    requiresPin: false,
    confirmColor: 'primary',
  })

  const { t } = useI18n()

  const resolvedTitle = computed(() => props.title ?? t('confirmDialog.defaultTitle'))
  const resolvedMessage = computed(() => props.message ?? t('confirmDialog.defaultMessage'))
  const resolvedConfirmText = computed(() => props.confirmText ?? t('confirmDialog.defaultConfirm'))
  const resolvedCancelText = computed(() => props.cancelText ?? t('common.cancel'))

  const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

  const pin = ref('')
  const expectedPin = ref('')
  const isDialogVisible = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  function generatePin () {
    expectedPin.value = Math.floor(10_000 + Math.random() * 90_000).toString()
  }

  const isPinValid = computed(() => {
    if (!props.requiresPin) return true
    return pin.value === expectedPin.value
  })

  function handleCancel () {
    isDialogVisible.value = false
    emit('cancel')
  }

  function handleConfirm () {
    if (isPinValid.value) {
      emit('confirm')
      isDialogVisible.value = false
      reset()
    }
  }

  function reset () {
    pin.value = ''
    if (props.requiresPin) {
      generatePin()
    }
  }

  watch(
    () => props.modelValue,
    newVal => {
      if (newVal) {
        reset()
      }
    },
  )

  onMounted(() => {
    if (props.requiresPin) {
      generatePin()
    }
  })
</script>

<template>
  <v-dialog v-model="isDialogVisible" max-width="500px" persistent>
    <v-card class="pa-4" rounded="xl">
      <v-card-title class="text-h6 font-weight-bold px-4 pt-4">
        {{ resolvedTitle }}
      </v-card-title>

      <v-card-text class="px-4 py-4">
        <p class="text-body-1 text-grey-darken-2 mb-6">
          {{ resolvedMessage }}
        </p>

        <div
          v-if="requiresPin"
          class="bg-grey-lighten-4 pa-6 border-grey-lighten-1 rounded-lg border border-dashed"
        >
          <div class="mb-4 text-center">
            <span
              class="text-caption text-uppercase font-weight-bold text-grey-darken-1"
            >{{ $t('confirmDialog.verificationRequired') }}</span>
            <div
              class="text-h4 font-weight-black text-primary my-2 tracking-widest"
            >
              {{ expectedPin }}
            </div>
            <p class="text-caption text-grey-darken-1">
              {{ $t('confirmDialog.verificationHint') }}
            </p>
          </div>

          <v-text-field
            v-model="pin"
            autofocus
            class="centered-input"
            color="primary"
            density="comfortable"
            hide-details
            placeholder="00000"
            variant="outlined"
            @keyup.enter="handleConfirm"
          />
        </div>
      </v-card-text>

      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn
          class="text-none font-weight-bold px-6"
          color="grey-darken-1"
          rounded="lg"
          variant="text"
          @click="handleCancel"
        >
          {{ resolvedCancelText }}
        </v-btn>
        <v-btn
          class="text-none font-weight-bold ml-2 px-8"
          :color="confirmColor"
          :disabled="!isPinValid"
          rounded="lg"
          variant="elevated"
          @click="handleConfirm"
        >
          {{ resolvedConfirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.centered-input :deep(input) {
    text-align: center;
    font-size: 1.5rem;
    letter-spacing: 0.5rem;
    font-weight: bold;
}
</style>
