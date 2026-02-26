<script lang="ts" setup>
  import type { TeamMember } from '@/api/team-members'
  import { ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import TeamMemberModal from '@/components/features/TeamMemberModal.vue'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import DataTable from '@/components/ui/DataTable.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { useRefreshData } from '@/composables/useRefreshData'
  import { useUrlPagination } from '@/composables/useUrlPagination'
  import { useTeamMemberList } from '@/queries/team-members'
  import { useAuthStore } from '@/stores/auth'
  import { useTeamMemberStore } from '@/stores/team-member'
  import { getRoleColors } from '@/utils/colors'

  const { t } = useI18n()
  const authStore = useAuthStore()
  const teamMemberStore = useTeamMemberStore()
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()
  const { page: urlPage, handlePageChange } = useUrlPagination()

  const { members, meta, isPending: processing, refetch, page } = useTeamMemberList()

  watch(urlPage, val => {
    page.value = val
  }, { immediate: true })

  const { refreshing, refresh } = useRefreshData(async () => {
    await refetch()
  })

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

  function confirmPromote (m: TeamMember) {
    const isManager = m.role === 'Manager'
    const newRole = isManager ? 'member' : 'manager'

    openConfirmDialog({
      title: isManager ? t('teamMembers.demoteToMember') : t('teamMembers.promoteToManager'),
      message: isManager
        ? t('teamMembers.confirmDemote', { name: m.name })
        : t('teamMembers.confirmPromote', { name: m.name }),
      requiresPin: false,
      onConfirm: async () => {
        await teamMemberStore.promoteMember({ id: m.id, role: newRole })
      },
    })
  }
</script>

<template>
  <PageHeader :title="$t('teamMembers.title')">
    <div class="d-flex ga-2 align-center">
      <v-btn
        v-if="authStore.isAdmin"
        class="text-none font-weight-bold"
        color="primary"
        data-testid="add-member-btn"
        prepend-icon="mdi-plus"
        rounded="lg"
        variant="flat"
        @click="openAdd"
      >
        {{ $t('teamMembers.addMember') }}
      </v-btn>
    </div>
  </PageHeader>

  <DataTable
    :loading="processing"
    :meta="meta"
    :refreshing="refreshing"
    :title="$t('teamMembers.title')"
    @refresh="refresh"
    @update:page="handlePageChange"
  >
    <template #columns>
      <th>{{ $t('teamMembers.tableHeaders.name') }}</th>
      <th>{{ $t('teamMembers.tableHeaders.email') }}</th>
      <th>{{ $t('teamMembers.tableHeaders.role') }}</th>
      <th>{{ $t('teamMembers.tableHeaders.walletAccess') }}</th>
      <th v-if="authStore.isAdmin" class="text-right">
        {{ $t('teamMembers.tableHeaders.actions') }}
      </th>
    </template>

    <template #body>
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
            :color="getRoleColors(m.role).bg"
            size="small"
            variant="flat"
          >
            <span :class="`text-${getRoleColors(m.role).text}`">{{ m.role }}</span>
          </v-chip>
        </td>
        <td>
          <span class="text-caption text-grey-darken-2">{{ m.wallet_access }}</span>
        </td>
        <td v-if="authStore.isAdmin" class="text-right">
          <div class="d-flex ga-2 justify-end">
            <v-btn
              v-if="m.role !== 'Admin' && m.id !== authStore.user?.id"
              :color="m.role === 'Manager' ? 'grey-darken-1' : 'info'"
              :data-testid="`promote-btn-${m.id}`"
              density="comfortable"
              :icon="m.role === 'Manager' ? 'mdi-arrow-down' : 'mdi-arrow-up'"
              size="small"
              :title="m.role === 'Manager' ? $t('teamMembers.demoteToMember') : $t('teamMembers.promoteToManager')"
              variant="text"
              @click="confirmPromote(m)"
            />
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
    </template>
  </DataTable>

  <TeamMemberModal
    v-model="showModal"
    :user="selectedMember"
    @saved="showModal = false; refetch()"
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
