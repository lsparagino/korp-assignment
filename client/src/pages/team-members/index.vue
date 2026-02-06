<script lang="ts" setup>
  import { ExternalLink } from 'lucide-vue-next'
  import { onMounted, ref, computed } from 'vue'
  import api from '@/plugins/api'
  import TeamMemberModal from '@/components/TeamMemberModal.vue'
  import Pagination from '@/components/Pagination.vue'
  import ConfirmDialog from '@/components/ConfirmDialog.vue'
  import { useAuthStore } from '@/stores/auth'
  import { useCompanyStore } from '@/stores/company'

  const authStore = useAuthStore()
  const companyStore = useCompanyStore()
  const isAdmin = computed(() => authStore.user?.role === 'admin')
  const members = ref<any[]>([])
  const processing = ref(true)
  const showModal = ref(false)
  const selectedUser = ref<any>(null)
  
  const paginationData = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0
  })

  const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    requiresPin: false,
    onConfirm: () => {},
  })

  async function fetchTeam (page = 1) {
    processing.value = true
    try {
      const response = await api.get('/team-members', { params: { page } })
      members.value = response.data.members
      paginationData.value = {
        currentPage: response.data.pagination.current_page,
        lastPage: response.data.pagination.last_page,
        total: response.data.pagination.total
      }
    } catch (error) {
      console.error('Error fetching team:', error)
    } finally {
      processing.value = false
    }
  }

  function openCreateModal () {
    selectedUser.value = null
    showModal.value = true
  }

  function openEditModal (member: any) {
    // We need to fetch the member details or use the list data.
    // The list data doesn't have the assigned wallet IDs currently.
    // Let's assume the index returns them or handles them.
    selectedUser.value = member
    showModal.value = true
  }

  function getRoleColor (role: string) {
    switch (role.toLowerCase()) {
      case 'admin': {
        return 'grey-lighten-2'
      }
      case 'member': {
        return 'blue-lighten-4'
      }
      default: {
        return 'grey-lighten-4'
      }
    }
  }

  function getRoleTextColor (role: string) {
    switch (role.toLowerCase()) {
      case 'admin': {
        return 'grey-darken-3'
      }
      case 'member': {
        return 'blue-darken-3'
      }
      default: {
        return 'grey-darken-1'
      }
    }
  }

  function deleteMember (member: any) {
    confirmDialog.value = {
      show: true,
      title: 'Delete Member',
      message: `Are you sure you want to permanently delete ${member.name}? This action cannot be undone.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await api.delete(`/team-members/${member.id}`)
          fetchTeam(paginationData.value.currentPage)
        } catch (error) {
          console.error('Error deleting member:', error)
        }
      }
    }
  }

  onMounted(() => fetchTeam())
</script>

<template>
  <div class="d-flex flex-column flex-sm-row align-start align-sm-center justify-space-between ga-4 mb-8">
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Team Members <span v-if="companyStore.currentCompany" class="text-grey-darken-1">- {{ companyStore.currentCompany.name }}</span>
    </h1>
    <v-btn
      v-if="isAdmin"
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      variant="flat"
      @click="openCreateModal"
    >
      Add Member
    </v-btn>
  </div>

  <v-card border flat :loading="processing" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Name
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Email
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Role
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Wallet Access
            </th>
            <th
              v-if="isAdmin"
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in members" :key="member.id">
            <td class="font-weight-bold text-grey-darken-3">
              {{ member.name }}
              <v-chip
                v-if="member.is_current"
                class="ms-2"
                color="grey-lighten-4"
                size="x-small"
                variant="flat"
              >
                YOU
              </v-chip>
              <v-chip
                v-if="member.is_pending"
                class="ms-2 text-uppercase"
                color="orange-lighten-5"
                size="x-small"
                variant="flat"
              >
                <span class="text-orange-darken-3 font-weight-bold">Pending Invitation</span>
              </v-chip>
            </td>
            <td class="text-grey-darken-2">{{ member.email }}</td>
            <td>
              <v-chip
                class="font-weight-bold"
                :color="getRoleColor(member.role)"
                size="small"
                variant="flat"
              >
                <span
                  class="font-weight-bold"
                  :class="`text-${getRoleTextColor(member.role)}`"
                >{{ member.role }}</span>
              </v-chip>
            </td>
            <td>
              <v-chip
                class="font-weight-bold"
                color="green-lighten-5"
                size="small"
                variant="flat"
              >
                <span
                  class="text-green-darken-3 font-weight-bold"
                >{{ member.wallet_access }}</span>
              </v-chip>
            </td>
            <td v-if="isAdmin" class="text-right">
              <div class="d-flex justify-end ga-2">
                <v-btn
                  v-if="!member.is_current && member.role === 'Member'"
                  color="primary"
                  density="comfortable"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="openEditModal(member)"
                />
                <v-btn
                  v-if="!member.is_current && member.role === 'Member'"
                  color="error"
                  density="comfortable"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="deleteMember(member)"
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
          current_page: paginationData.currentPage,
          last_page: paginationData.lastPage,
          per_page: 10,
          total: paginationData.total,
          from: (paginationData.currentPage - 1) * 10 + 1,
          to: Math.min(paginationData.currentPage * 10, paginationData.total),
        }"
        @update:page="fetchTeam"
      />
    </div>
  </v-card>

  <TeamMemberModal
    v-model="showModal"
    :user="selectedUser"
    @saved="fetchTeam"
  />

  <ConfirmDialog
    v-model="confirmDialog.show"
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
