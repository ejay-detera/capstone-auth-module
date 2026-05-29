<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { 
  Plus, 
  Search, 
  Key
} from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'
import type { Permission } from '@/types'
import PermissionFormModal from './PermissionFormModal.vue'
import PermissionsTable from './PermissionsTable.vue'
import AssignRolesModal from './AssignRolesModal.vue'

const {
  permissions,
  roles,
  loading,
  error,
  pagination,
  filters,
  fetchPermissions,
  submitting,
  createPermission,
  updatePermission,
  deletePermission: deletePermissionAction,
  selectedRoleIds,
  loadingRoles,
  fetchPermissionRoles,
  syncPermissionRoles,
} = usePermissions()

const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const selectedPermissionForEdit = ref({
  id: null as number | null,
  name: '',
  slug: '',
  description: ''
})

const showAssignModal = ref(false)
const selectedPermission = ref<Permission | null>(null)

const openAssignModal = async (permission: Permission) => {
  selectedPermission.value = permission
  showAssignModal.value = true
  await fetchPermissionRoles(permission.id)
}

const handleAssignRoles = async () => {
  if (!selectedPermission.value) return
  const success = await syncPermissionRoles(selectedPermission.value.id)
  if (success) {
    showAssignModal.value = false
  }
}

const openCreateModal = () => {
  modalMode.value = 'create'
  selectedPermissionForEdit.value = { id: null, name: '', slug: '', description: '' }
  showModal.value = true
}

const openEditModal = (permission: Permission) => {
  modalMode.value = 'edit'
  selectedPermissionForEdit.value = { ...permission }
  showModal.value = true
}

const handleSubmit = async (payload: { id: number | null, name: string, slug: string, description: string }) => {
  const { id, ...data } = payload
  let success = false

  if (modalMode.value === 'create') {
    success = await createPermission(data)
  } else if (id !== null) {
    success = await updatePermission(id, data)
  }

  if (success) {
    showModal.value = false
  }
}

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this permission? This may affect user access.')) return

  const result = await deletePermissionAction(id)
  if (!result.success) {
    alert(result.message)
  }
}

onMounted(() => fetchPermissions())

watch(filters, () => {
  fetchPermissions(1)
}, { deep: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Permission Management</h1>
        <p class="text-slate-500 mt-1">Manage system-wide permissions and access rules</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95"
      >
        <Plus :size="18" />
        Add New Permission
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Key :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Permissions</p>
            <p class="text-2xl font-bold text-slate-900">{{ permissions.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Search permissions by name, slug, or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Permissions Table -->
    <PermissionsTable
      :loading="loading"
      :permissions="permissions"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchPermissions"
      @open-assign-modal="openAssignModal"
      @open-edit-modal="openEditModal"
      @delete="handleDelete"
    />

    <!-- Assign to Roles Modal -->
    <AssignRolesModal
      v-model="showAssignModal"
      :permission="selectedPermission"
      :roles="roles"
      v-model:selected-role-ids="selectedRoleIds"
      :loading="loadingRoles"
      :submitting="submitting"
      :error="error"
      @submit="handleAssignRoles"
    />

    <!-- Create/Edit Modal -->
    <PermissionFormModal
      v-model="showModal"
      :mode="modalMode"
      :initial-data="selectedPermissionForEdit"
      :submitting="submitting"
      :error="error"
      @submit="handleSubmit"
    />
  </div>
</template>
