<script lang="ts" setup>
  import type { TeamMember } from '@/api/team-members'
  import { computed, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import TeamMemberModal from '@/components/features/TeamMemberModal.vue'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import Pagination from '@/components/ui/Pagination.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { useUrlPagination } from '@/composables/useUrlPagination'
  import { useTeamMemberStore } from '@/stores/team-member'
  import { useAuthStore } from '@/stores/auth'

  const { t } = useI18n()
  const authStore = useAuthStore()
  const teamMemberStore = useTeamMemberStore()
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()
  const { page, handlePageChange } = useUrlPagination()

  watch(page, val => { teamMemberStore.page = val }, { immediate: true })

  const members = computed(() => teamMemberStore.members)
  const processing = computed(() => teamMemberStore.listLoading)

  const showModal = ref(false)
  const selectedMember = ref<TeamMember | null>(null)

  function openAdd () {
    selectedMember.value = null
    showModal.value = true
  }

  function openEdit (m: TeamMember) {
    selectedMember.value = m
    showModal.value = true
  }

  function confirmDelete (m: TeamMember) {
    openConfirmDialog({
      title: t('teamMembers.deleteMember'),
      message: t('teamMembers.confirmDelete', { name: m.name }),
      requiresPin: true,
      onConfirm: async () => {
        await teamMemberStore.deleteMember(m.id)
      },
    })
  }
</script>

<template>
  <PageHeader :title="$t('teamMembers.title')">
    <v-btn
      v-if="authStore.isAdmin"
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      variant="flat"
      @click="openAdd"
    >
      {{ $t('teamMembers.addMember') }}
    </v-btn>
  </PageHeader>

  <v-card border flat :loading="processing" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <th class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left">
              {{ $t('teamMembers.tableHeaders.name') }}
            </th>
            <th class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left">
              {{ $t('teamMembers.tableHeaders.email') }}
            </th>
            <th class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left">
              {{ $t('teamMembers.tableHeaders.role') }}
            </th>
            <th class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left">
              {{ $t('teamMembers.tableHeaders.walletAccess') }}
            </th>
            <th
              v-if="authStore.isAdmin"
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
            >
              {{ $t('teamMembers.tableHeaders.actions') }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in members" :key="m.id">
            <td class="font-weight-bold text-grey-darken-3">
              <div class="d-flex align-center ga-2">
                {{ m.name }}
                <v-chip
                  v-if="m.id === authStore.user?.id"
                  class="font-weight-bold"
                  color="primary"
                  size="x-small"
                  variant="flat"
                >{{ $t('teamMembers.you') }}</v-chip>
                <v-chip
                  v-if="m.is_pending"
                  class="font-weight-bold"
                  color="warning"
                  size="x-small"
                  variant="flat"
                >{{ $t('teamMembers.pendingInvitation') }}</v-chip>
              </div>
            </td>
            <td class="text-grey-darken-2">{{ m.email }}</td>
            <td>
              <v-chip
                class="text-uppercase font-weight-bold"
                :color="m.role === 'admin' ? 'primary' : 'grey-darken-1'"
                size="small"
                variant="flat"
              >{{ m.role }}</v-chip>
            </td>
            <td>
              <span class="text-caption text-grey-darken-2">{{ m.wallet_access }}</span>
            </td>
            <td v-if="authStore.isAdmin" class="text-right">
              <div class="d-flex ga-2 justify-end">
                <v-btn
                  color="primary"
                  density="comfortable"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="openEdit(m)"
                />
                <v-btn
                  v-if="m.id !== authStore.user?.id"
                  color="error"
                  density="comfortable"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="confirmDelete(m)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>

    <div class="border-t">
      <Pagination
        :meta="{
          current_page: teamMemberStore.pagination.currentPage,
          last_page: teamMemberStore.pagination.lastPage,
          per_page: 15,
          total: teamMemberStore.pagination.total,
          from: null,
          to: null,
        }"
        @update:page="handlePageChange"
      />
    </div>
  </v-card>

  <TeamMemberModal
    v-model="showModal"
    :user="selectedMember"
    @saved="showModal = false"
  />

  <ConfirmDialog
    v-model="confirmDialog.show"
    confirm-color="error"
    :message="confirmDialog.message"
    :requires-pin="confirmDialog.requiresPin"
    :title="confirmDialog.title"
    @confirm="confirmDialog.onConfirm"
  />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
