<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { 
  Plus, 
  Search, 
  Shield
} from 'lucide-vue-next'
import { useRoles } from '@/composables/useRoles'
import type { Role } from '@/types'
import RolesTable from './RolesTable.vue'
import RoleFormModal from './RoleFormModal.vue'
import RolePermissionsModal from './RolePermissionsModal.vue'
import RoleUsersModal from './RoleUsersModal.vue'

const {
  roles,
  loading,
  error,
  pagination,
  filters,
  fetchRoles,
  submitting,
  createRole,
  updateRole,
  deleteRole,
  // Permission Sync
  allPermissions,
  selectedPermissionIds,
  loadingPermissions,
  fetchRolePermissions,
  syncRolePermissions,
  // Role Users
  roleUsers,
  loadingUsers,
  fetchRoleUsers,
} = useRoles()

// ── Create / Edit Modal ──────────────────────────────────────────────────────
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

const openCreateModal = () => {
  modalMode.value = 'create'
  form.value = { id: null, name: '', description: '' }
  error.value = ''
  showModal.value = true
}

const openEditModal = (role: Role) => {
  modalMode.value = 'edit'
  form.value = { id: role.id, name: role.name, description: role.description || '' }
  error.value = ''
  showModal.value = true
}

const handleSubmit = async (payload: { id: number | null, name: string, description: string }) => {
  const { id, ...data } = payload
  let success = false

  if (modalMode.value === 'create') {
    success = await createRole(data)
  } else if (id !== null) {
    success = await updateRole(id, data)
  }

  if (success) {
    showModal.value = false
  }
}

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this role? This action cannot be undone.')) return

  const result = await deleteRole(id)
  if (!result.success) {
    alert(result.message)
  }
}

// ── Permission Sync Modal ────────────────────────────────────────────────────
const showPermModal = ref(false)
const selectedRole = ref<Role | null>(null)

const openPermModal = async (role: Role) => {
  selectedRole.value = role
  showPermModal.value = true
  await fetchRolePermissions(role.id)
}


const handleSavePermissions = async () => {
  if (!selectedRole.value) return
  const success = await syncRolePermissions(selectedRole.value.id)
  if (success) {
    showPermModal.value = false
  }
}

// ── Users Modal ──────────────────────────────────────────────────────────────
const showUsersModal = ref(false)
const viewingRole = ref<Role | null>(null)

const openUsersModal = async (role: Role) => {
  viewingRole.value = role
  showUsersModal.value = true
  await fetchRoleUsers(role.id)
}

onMounted(() => fetchRoles())

watch(filters, () => {
  fetchRoles(1)
}, { deep: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Role Management</h1>
        <p class="text-slate-500 mt-1">Create and manage user roles and their permissions</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95"
      >
        <Plus :size="18" />
        Create Role
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Shield :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Roles</p>
            <p class="text-2xl font-bold text-slate-900">{{ roles.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Search roles by name or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <RolesTable
      :loading="loading"
      :error="error"
      :roles="roles"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchRoles"
      @open-users-modal="openUsersModal"
      @open-perm-modal="openPermModal"
      @open-edit-modal="openEditModal"
      @delete="handleDelete"
    />

    <!-- Create/Edit Role Modal -->
    <RoleFormModal
      v-model="showModal"
      :mode="modalMode"
      :initial-data="form"
      :submitting="submitting"
      :error="error"
      @submit="handleSubmit"
    />

    <!-- Permission Sync Modal -->
    <RolePermissionsModal
      v-model="showPermModal"
      :role="selectedRole"
      :all-permissions="allPermissions"
      v-model:selected-permission-ids="selectedPermissionIds"
      :loading="loadingPermissions"
      :submitting="submitting"
      :error="error"
      @submit="handleSavePermissions"
    />

    <!-- Users Modal -->
    <RoleUsersModal
      v-model="showUsersModal"
      :role="viewingRole"
      :users="roleUsers"
      :loading="loadingUsers"
    />
  </div>
</template>
