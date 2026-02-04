<script lang="ts" setup>
  import { computed, onMounted, ref, watch } from 'vue'

  const props = defineProps({
    modelValue: Boolean,
    title: {
      type: String,
      default: 'Confirm Action',
    },
    message: {
      type: String,
      default: 'Are you sure you want to proceed?',
    },
    confirmText: {
      type: String,
      default: 'Yes, Proceed',
    },
    cancelText: {
      type: String,
      default: 'Cancel',
    },
    requiresPin: {
      type: Boolean,
      default: false,
    },
    confirmColor: {
      type: String,
      default: 'primary',
    },
  })

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
    // reset()
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

  watch(() => props.modelValue, newVal => {
    if (newVal) {
      reset()
    }
  })

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
        {{ title }}
      </v-card-title>

      <v-card-text class="px-4 py-4">
        <p class="text-body-1 text-grey-darken-2 mb-6">
          {{ message }}
        </p>

        <div v-if="requiresPin" class="bg-grey-lighten-4 pa-6 rounded-lg border border-dashed border-grey-lighten-1">
          <div class="text-center mb-4">
            <span class="text-caption text-uppercase font-weight-bold text-grey-darken-1">Verification Required</span>
            <div class="text-h4 font-weight-black tracking-widest text-primary my-2">
              {{ expectedPin }}
            </div>
            <p class="text-caption text-grey-darken-1">
              Please enter the code above to confirm this action.
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
          {{ cancelText }}
        </v-btn>
        <v-btn
          class="text-none font-weight-bold px-8 ml-2"
          :color="confirmColor"
          :disabled="!isPinValid"
          rounded="lg"
          variant="elevated"
          @click="handleConfirm"
        >
          {{ confirmText }}
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
