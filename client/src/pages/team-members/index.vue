<script lang="ts" setup>
  import { ExternalLink } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import api from '@/plugins/api'

  const company = ref('')
  const members = ref<any[]>([])
  const processing = ref(true)

  async function fetchTeam () {
    try {
      const response = await api.get('/team-members')
      company.value = response.data.company
      members.value = response.data.members
    } catch (error) {
      console.error('Error fetching team:', error)
    } finally {
      processing.value = false
    }
  }

  function getRoleColor (role: string) {
    switch (role) {
      case 'Admin': {
        return 'grey-lighten-2'
      }
      case 'Member': {
        return 'blue-lighten-4'
      }
      default: {
        return 'grey-lighten-4'
      }
    }
  }

  function getRoleTextColor (role: string) {
    switch (role) {
      case 'Admin': {
        return 'grey-darken-3'
      }
      case 'Member': {
        return 'blue-darken-3'
      }
      default: {
        return 'grey-darken-1'
      }
    }
  }

  onMounted(fetchTeam)
</script>

<template>
  <div class="d-flex align-center justify-space-between mb-8">
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Team Members - {{ company }}
    </h1>
    <v-btn
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      variant="flat"
    >
      Add Member
    </v-btn>
  </div>

  <v-card border flat :loading="processing" rounded="lg">
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
            class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
          >
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="member in members" :key="member.id">
          <td class="font-weight-bold text-grey-darken-3">
            {{ member.name }}
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
          <td>
            <v-btn
              class="text-none font-weight-black"
              color="primary"
              density="compact"
              variant="text"
            >
              EDIT
              <v-icon
                class="ms-1"
                end
                :icon="ExternalLink"
                size="14"
              />
            </v-btn>
          </td>
        </tr>
      </tbody>
    </v-table>

    <div
      class="pa-4 d-flex align-center justify-space-between bg-grey-lighten-5 border-t"
    >
      <span class="text-caption text-grey-darken-1">Showing {{ members.length }} of 100</span>
      <v-pagination
        active-color="primary"
        class="my-0"
        density="compact"
        :length="3"
        rounded="sm"
      />
    </div>
  </v-card>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
