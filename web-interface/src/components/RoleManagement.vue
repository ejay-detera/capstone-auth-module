<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { 
  Plus, 
  Search, 
  Edit2, 
  Trash2, 
  Users, 
  Shield,
  X,
  Loader2,
  AlertCircle,
  Key,
  Check
} from 'lucide-vue-next'
import api from '@/lib/api'

interface Role {
  id: number
  name: string
  description: string
  users_count: number
}

const roles = ref<Role[]>([])
const loading = ref(true)
const searchQuery = ref('')

const filteredRoles = computed(() => {
  if (!Array.isArray(roles.value)) return []
  return roles.value.filter(r => 
    r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
    r.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const submitting = ref(false)
const error = ref('')
const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

const showPermissionModal = ref(false)
const allPermissions = ref<any[]>([])
const selectedRole = ref<Role | null>(null)
const rolePermissions = ref<number[]>([])
const loadingPermissions = ref(false)

const fetchAllPermissions = async () => {
  try {
    const response = await api.get('/api/admin/permissions')
    allPermissions.value = response.data
  } catch (err) {
    console.error('Failed to fetch permissions', err)
  }
}

const openPermissionModal = async (role: Role) => {
  selectedRole.value = role
  showPermissionModal.value = true
  loadingPermissions.value = true
  rolePermissions.value = []
  error.value = ''
  
  try {
    if (allPermissions.value.length === 0) {
      await fetchAllPermissions()
    }
    const response = await api.get(`/api/admin/roles/${role.id}/permissions`)
    rolePermissions.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch role permissions', err)
    error.value = 'Failed to load permissions'
  } finally {
    loadingPermissions.value = false
  }
}

const handleSyncPermissions = async () => {
  if (!selectedRole.value) return
  submitting.value = true
  error.value = ''
  try {
    await api.post(`/api/admin/roles/${selectedRole.value.id}/permissions`, {
      permissions: rolePermissions.value
    })
    showPermissionModal.value = false
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to update permissions'
  } finally {
    submitting.value = false
  }
}

const fetchRoles = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/admin/roles')
    roles.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch roles', err)
    error.value = 'Failed to load roles. Please try again.'
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  modalMode.value = 'create'
  form.value = { id: null, name: '', description: '' }
  showModal.value = true
}

const openEditModal = (role: Role) => {
  modalMode.value = 'edit'
  form.value = { id: role.id, name: role.name, description: role.description }
  showModal.value = true
}

const handleSubmit = async () => {
  submitting.value = true
  error.value = ''
  try {
    if (modalMode.value === 'create') {
      await api.post('/api/admin/roles', form.value)
    } else {
      await api.put(`/api/admin/roles/${form.value.id}`, form.value)
    }
    showModal.value = false
    fetchRoles()
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Something went wrong'
  } finally {
    submitting.value = false
  }
}

const deleteRole = async (id: number) => {
  if (!confirm('Are you sure you want to delete this role?')) return

  try {
    await api.delete(`/api/admin/roles/${id}`)
    fetchRoles()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to delete role')
  }
}

onMounted(fetchRoles)
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Role Management</h1>
        <p class="text-slate-500 mt-1">Define and manage system access levels</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-slate-900 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10 active:scale-95"
      >
        <Plus :size="18" />
        Create New Role
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
            v-model="searchQuery"
            type="text" 
            placeholder="Search roles by name or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Roles Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 flex flex-col items-center justify-center gap-4 text-slate-500">
        <Loader2 class="animate-spin" :size="32" />
        <p class="font-medium">Loading roles...</p>
      </div>

      <div v-else-if="roles.length === 0" class="p-12 text-center">
        <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Shield :size="32" />
        </div>
        <h3 class="text-lg font-bold text-slate-900">No Roles Found</h3>
        <p class="text-slate-500 mt-1">Get started by creating your first system role.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Role Name</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Assigned Users</th>
              <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="role in filteredRoles" 
              :key="role.id"
              class="hover:bg-slate-50/50 transition-colors group"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-slate-100 text-slate-600 rounded-lg flex items-center justify-center group-hover:bg-slate-900 group-hover:text-white transition-colors">
                    <Shield :size="16" />
                  </div>
                  <span class="font-bold text-slate-900">{{ role.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-slate-500 line-clamp-1 max-w-xs">{{ role.description || 'No description' }}</p>
              </td>
              <td class="px-6 py-4 text-center">
                <span 
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all"
                  :class="role.users_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                >
                  <Users :size="12" />
                  {{ role.users_count }} Users
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="openPermissionModal(role)"
                    class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                    title="Manage Permissions"
                  >
                    <Key :size="16" />
                  </button>
                  <button 
                    @click="openEditModal(role)"
                    class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-all"
                    title="Edit Role"
                  >
                    <Edit2 :size="16" />
                  </button>
                  <button 
                    @click="deleteRole(role.id)"
                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                    title="Delete Role"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Permission Management Modal -->
    <div v-if="showPermissionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showPermissionModal = false"></div>
      
      <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Manage Permissions</h2>
            <p class="text-sm text-slate-500 font-medium">Role: <span class="text-indigo-600">{{ selectedRole?.name }}</span></p>
          </div>
          <button @click="showPermissionModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <div class="p-6">
          <div v-if="loadingPermissions" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
            <Loader2 class="animate-spin" :size="32" />
            <p class="font-medium">Loading permissions...</p>
          </div>

          <div v-else class="space-y-6">
            <div v-if="error" class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium">
              <AlertCircle :size="18" />
              {{ error }}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label 
                v-for="permission in allPermissions" 
                :key="permission.id"
                class="relative flex items-start gap-3 p-4 rounded-2xl border-2 transition-all cursor-pointer group"
                :class="rolePermissions.includes(permission.id) 
                  ? 'border-indigo-600 bg-indigo-50/30' 
                  : 'border-slate-100 hover:border-slate-200 bg-white'"
              >
                <div class="mt-0.5">
                  <div 
                    class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all"
                    :class="rolePermissions.includes(permission.id)
                      ? 'bg-indigo-600 border-indigo-600 text-white'
                      : 'border-slate-300 bg-white group-hover:border-slate-400'"
                  >
                    <Check v-if="rolePermissions.includes(permission.id)" :size="14" stroke-width="3" />
                  </div>
                  <input 
                    type="checkbox" 
                    :value="permission.id" 
                    v-model="rolePermissions"
                    class="sr-only"
                  />
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ permission.name }}</p>
                  <p class="text-[11px] text-slate-500 leading-tight mt-0.5">{{ permission.description || 'No description available' }}</p>
                </div>
              </label>
            </div>

            <div class="pt-4 flex gap-3">
              <button 
                type="button" 
                @click="showPermissionModal = false"
                class="flex-1 px-6 py-3 border-2 border-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-50 transition-all"
              >
                Cancel
              </button>
              <button 
                @click="handleSyncPermissions"
                :disabled="submitting"
                class="flex-1 px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-slate-900/20"
              >
                <Loader2 v-if="submitting" class="animate-spin" :size="18" />
                {{ submitting ? 'Updating...' : 'Save Permissions' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>
      
      <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-xl font-bold text-slate-900">
            {{ modalMode === 'create' ? 'Create New Role' : 'Edit Role' }}
          </h2>
          <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-all">
            <X :size="20" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <div v-if="error" class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium animate-shake">
            <AlertCircle :size="18" />
            {{ error }}
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Role Name</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              placeholder="e.g. System Administrator"
              class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-medium focus:bg-white focus:border-slate-900 transition-all outline-none"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Description</label>
            <textarea 
              v-model="form.description"
              rows="3"
              placeholder="What can users with this role do?"
              class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-medium focus:bg-white focus:border-slate-900 transition-all outline-none resize-none"
            ></textarea>
          </div>

          <div class="pt-4 flex gap-3">
            <button 
              type="button" 
              @click="showModal = false"
              class="flex-1 px-6 py-3 border-2 border-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-50 transition-all"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="submitting"
              class="flex-1 px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-slate-900/20"
            >
              <Loader2 v-if="submitting" class="animate-spin" :size="18" />
              {{ submitting ? 'Saving...' : (modalMode === 'create' ? 'Create Role' : 'Save Changes') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake {
  animation: shake 0.2s ease-in-out 0s 2;
}
</style>
