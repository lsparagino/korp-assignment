<script lang="ts" setup>
  import type { TeamMember, Wallet } from '@/types'
  import { ref, watch } from 'vue'
  import { api } from '@/plugins/api'

  const props = defineProps<{
    user?: TeamMember | null
    modelValue: boolean
  }>()

  const emit = defineEmits(['update:modelValue', 'saved'])

  const dialog = ref(false)
  const processing = ref(false)
  const wallets = ref<Wallet[]>([])
  const form = ref({
    name: '',
    email: '',
    wallets: [] as number[],
  })
  const errors = ref<Record<string, string[]>>({})

  watch(
    () => props.modelValue,
    val => {
      dialog.value = val
      if (val) {
        fetchWallets()
        errors.value = {}
        form.value = props.user
          ? {
            name: props.user.name,
            email: props.user.email,
            wallets: props.user.assigned_wallets || [],
          }
          : { name: '', email: '', wallets: [] }
      }
    },
  )

  watch(dialog, val => {
    emit('update:modelValue', val)
  })

  async function fetchWallets () {
    try {
      const response = await api.get('/wallets')
      wallets.value = response.data.data
    } catch (error) {
      console.error('Error fetching wallets:', error)
    }
  }

  async function save () {
    processing.value = true
    errors.value = {}
    try {
      await (props.user
        ? api.put(`/team-members/${props.user.id}`, form.value)
        : api.post('/team-members', form.value))
      emit('saved')
      dialog.value = false
    } catch (error: unknown) {
      const err = error as { response?: { status?: number, data?: { errors?: Record<string, string[]> } } }
      if (err.response?.status === 422) {
        errors.value = err.response.data?.errors ?? {}
      } else {
        console.error('Error saving member:', error)
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <v-dialog v-model="dialog" max-width="600">
    <v-card rounded="lg">
      <v-card-title class="pa-4 font-weight-bold">
        {{ user ? 'Edit Member' : 'Add Member' }}
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-form @submit.prevent="save">
          <v-text-field
            v-model="form.name"
            :error-messages="errors.name"
            label="Full Name"
            placeholder="John Doe"
            variant="outlined"
          />
          <v-text-field
            v-model="form.email"
            :error-messages="errors.email"
            label="Email Address"
            placeholder="john@example.com"
            type="email"
            variant="outlined"
          />

          <div class="text-subtitle-1 font-weight-bold mb-2">
            Wallet Access
          </div>
          <div
            v-if="errors.wallets"
            class="text-caption text-error mb-2"
          >
            {{ errors.wallets[0] }}
          </div>
          <v-row dense>
            <v-col
              v-for="wallet in wallets"
              :key="wallet.id"
              cols="12"
              sm="6"
            >
              <v-checkbox
                v-model="form.wallets"
                density="compact"
                hide-details
                :label="`${wallet.name} (${wallet.currency})`"
                :value="wallet.id"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          color="grey-darken-1"
          variant="text"
          @click="dialog = false"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          :loading="processing"
          variant="flat"
          @click="save"
        >
          {{ user ? 'Update Member' : 'Invite Member' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
