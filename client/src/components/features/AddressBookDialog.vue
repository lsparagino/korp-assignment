<script lang="ts" setup>
  import type { AddressBookEntry } from '@/api/address-book'
  import { useQuery, useQueryCache } from '@pinia/colada'
  import { computed, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { createAddressBookEntry, deleteAddressBookEntry } from '@/api/address-book'
  import { ADDRESS_BOOK_QUERY_KEYS, addressBookListQuery } from '@/queries/address-book'

  useI18n()
  const queryCache = useQueryCache()

  const props = defineProps<{
    modelValue: boolean
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'select', entry: AddressBookEntry): void
  }>()

  const dialog = computed({
    get: () => props.modelValue,
    set: (val: boolean) => emit('update:modelValue', val),
  })

  const { data: addressBookData } = useQuery(addressBookListQuery)
  const entries = computed<AddressBookEntry[]>(() => addressBookData.value ?? [])

  const search = ref('')
  const showAddForm = ref(false)
  const savingAddress = ref(false)
  const deleteConfirmId = ref<number | null>(null)
  const deleting = ref(false)
  const newEntry = ref({ name: '', address: '' })

  const filteredEntries = computed(() => {
    if (!search.value) return entries.value
    const q = search.value.toLowerCase()
    return entries.value.filter(
      e => e.name.toLowerCase().includes(q) || e.address.toLowerCase().includes(q),
    )
  })

  function selectEntry (entry: AddressBookEntry) {
    emit('select', entry)
    dialog.value = false
  }

  async function saveNewEntry () {
    if (!newEntry.value.name || !newEntry.value.address) return
    savingAddress.value = true
    try {
      await createAddressBookEntry(newEntry.value)
      await queryCache.invalidateQueries({ key: ADDRESS_BOOK_QUERY_KEYS.root })
      showAddForm.value = false
      newEntry.value = { name: '', address: '' }
    } catch {
      // Form stays open for retry
    } finally {
      savingAddress.value = false
    }
  }

  async function confirmDelete () {
    if (!deleteConfirmId.value) return
    deleting.value = true
    try {
      await deleteAddressBookEntry(deleteConfirmId.value)
      await queryCache.invalidateQueries({ key: ADDRESS_BOOK_QUERY_KEYS.root })
      deleteConfirmId.value = null
    } catch {
      // silent
    } finally {
      deleting.value = false
    }
  }

  watch(dialog, val => {
    if (!val) {
      search.value = ''
      showAddForm.value = false
      deleteConfirmId.value = null
      newEntry.value = { name: '', address: '' }
    }
  })
</script>

<template>
  <v-dialog v-model="dialog" data-testid="address-book-dialog" max-width="520">
    <v-card rounded="lg">
      <v-card-title class="pa-4 font-weight-bold d-flex align-center justify-space-between">
        {{ $t('addressBook.title') }}
        <v-btn
          data-testid="address-book-close-btn"
          density="comfortable"
          icon="mdi-close"
          size="small"
          variant="text"
          @click="dialog = false"
        />
      </v-card-title>
      <v-divider />

      <v-card-text class="pa-4">
        <!-- Search -->
        <v-text-field
          v-model="search"
          class="mb-3"
          clearable
          data-testid="address-book-search"
          density="compact"
          hide-details
          :placeholder="$t('addressBook.searchPlaceholder')"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
        />

        <!-- Add New Entry -->
        <v-btn
          v-if="!showAddForm"
          block
          class="mb-3 text-none"
          color="primary"
          data-testid="address-book-add-btn"
          prepend-icon="mdi-plus"
          size="small"
          variant="tonal"
          @click="showAddForm = true"
        >
          {{ $t('addressBook.addNew') }}
        </v-btn>

        <v-expand-transition>
          <v-card
            v-if="showAddForm"
            class="mb-3 pa-3"
            color="grey-lighten-5"
            flat
            rounded="lg"
          >
            <div class="text-caption font-weight-bold text-grey-darken-2 mb-2">
              {{ $t('addressBook.addNew') }}
            </div>
            <v-text-field
              v-model="newEntry.name"
              class="mb-2"
              data-testid="address-book-new-name"
              density="compact"
              hide-details="auto"
              :label="$t('addressBook.name')"
              :placeholder="$t('addressBook.namePlaceholder')"
              variant="outlined"
            />
            <v-text-field
              v-model="newEntry.address"
              class="mb-2"
              data-testid="address-book-new-address"
              density="compact"
              hide-details="auto"
              :label="$t('addressBook.address')"
              :placeholder="$t('addressBook.addressPlaceholder')"
              variant="outlined"
            />
            <div class="d-flex ga-2 justify-end">
              <v-btn
                class="text-none"
                color="grey"
                size="small"
                variant="text"
                @click="showAddForm = false"
              >
                {{ $t('common.cancel') }}
              </v-btn>
              <v-btn
                class="text-none"
                color="primary"
                data-testid="address-book-save-btn"
                :disabled="!newEntry.name || !newEntry.address"
                :loading="savingAddress"
                size="small"
                variant="flat"
                @click="saveNewEntry"
              >
                {{ $t('addressBook.save') }}
              </v-btn>
            </div>
          </v-card>
        </v-expand-transition>

        <!-- Entries List -->
        <v-list v-if="filteredEntries.length > 0" class="pa-0" lines="two">
          <template v-for="(entry, index) in filteredEntries" :key="entry.id">
            <v-list-item
              class="px-2 rounded-lg"
              :data-testid="`address-book-entry-${entry.id}`"
              @click="selectEntry(entry)"
            >
              <template #title>
                <span class="font-weight-medium">{{ entry.name }}</span>
              </template>
              <template #subtitle>
                <span class="text-caption" style="font-family: monospace">{{ entry.address }}</span>
              </template>
              <template #append>
                <v-btn
                  color="error"
                  :data-testid="`address-book-delete-${entry.id}`"
                  density="comfortable"
                  icon="mdi-delete-outline"
                  size="x-small"
                  variant="text"
                  @click.stop="deleteConfirmId = entry.id"
                />
              </template>
            </v-list-item>
            <v-divider v-if="index < filteredEntries.length - 1" />
          </template>
        </v-list>

        <!-- Empty State -->
        <div
          v-else
          class="text-center text-grey-darken-1 py-8"
          data-testid="address-book-empty"
        >
          <v-icon class="mb-2" icon="mdi-book-open-outline" size="48" />
          <div class="text-body-2">
            {{ $t('addressBook.noEntries') }}
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Delete Confirmation -->
    <v-dialog max-width="360" :model-value="deleteConfirmId !== null" persistent @update:model-value="deleteConfirmId = null">
      <v-card rounded="lg">
        <v-card-title class="text-h6">
          {{ $t('common.confirm') }}
        </v-card-title>
        <v-card-text>
          {{ $t('addressBook.deleteConfirm') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            color="grey"
            variant="text"
            @click="deleteConfirmId = null"
          >
            {{ $t('common.cancel') }}
          </v-btn>
          <v-btn
            color="error"
            data-testid="address-book-confirm-delete-btn"
            :loading="deleting"
            variant="flat"
            @click="confirmDelete"
          >
            {{ $t('common.delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>
</template>
