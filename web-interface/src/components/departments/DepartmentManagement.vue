<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { 
  Plus, 
  Search, 
  Building2
} from 'lucide-vue-next'
import { useDepartments } from '@/composables/useDepartments'
import type { Department } from '@/types'
import DepartmentFormModal from './DepartmentFormModal.vue'
import DepartmentsTable from './DepartmentsTable.vue'
import DepartmentUsersModal from './DepartmentUsersModal.vue'

const {
  departments,
  loading,
  error,
  pagination,
  filters,
  fetchDepartments,
  submitting,
  createDepartment,
  updateDepartment,
  deleteDepartment: deleteDepartmentAction,
  departmentUsers,
  loadingUsers,
  userPagination,
  fetchDepartmentUsers,
} = useDepartments()

const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const selectedDepartmentForEdit = ref({
  id: null as number | null,
  name: '',
  description: ''
})

const showUserModal = ref(false)
const selectedDepartment = ref<Department | null>(null)

const openCreateModal = () => {
  modalMode.value = 'create'
  selectedDepartmentForEdit.value = { id: null, name: '', description: '' }
  showModal.value = true
}

const openEditModal = (dept: Department) => {
  modalMode.value = 'edit'
  selectedDepartmentForEdit.value = { id: dept.id, name: dept.name, description: dept.description || '' }
  showModal.value = true
}

const handleSubmit = async (payload: { id: number | null, name: string, description: string }) => {
  const { id, ...data } = payload
  let success = false

  if (modalMode.value === 'create') {
    success = await createDepartment(data)
  } else if (id !== null) {
    success = await updateDepartment(id, data)
  }

  if (success) {
    showModal.value = false
  }
}

const handleDelete = async (id: number) => {
  const dept = departments.value.find(d => d.id === id)
  if (!dept) return

  if ((dept.users_count ?? 0) > 0) {
    alert(`Cannot delete department with ${dept.users_count} assigned users.`)
    return
  }

  if (!confirm('Are you sure you want to delete this department?')) return

  const result = await deleteDepartmentAction(id)
  if (!result.success) {
    alert(result.message)
  }
}

const openUserModal = async (dept: Department, page = 1) => {
  selectedDepartment.value = dept
  showUserModal.value = true
  await fetchDepartmentUsers(dept.id, page)
}

const changeUserPage = (page: number) => {
  if (selectedDepartment.value) {
    openUserModal(selectedDepartment.value, page)
  }
}

onMounted(() => fetchDepartments())

watch(filters, () => {
  fetchDepartments(1)
}, { deep: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Department Management</h1>
        <p class="text-slate-500 mt-1">Organize users into organizational units</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95"
      >
        <Plus :size="18" />
        Create New Department
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Building2 :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Departments</p>
            <p class="text-2xl font-bold text-slate-900">{{ departments.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Search departments by name or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Departments Table -->
    <DepartmentsTable
      :loading="loading"
      :departments="departments"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchDepartments"
      @open-user-modal="openUserModal"
      @open-edit-modal="openEditModal"
      @delete="handleDelete"
    />

    <!-- Users Modal -->
    <DepartmentUsersModal
      v-model="showUserModal"
      :department="selectedDepartment"
      :loading="loadingUsers"
      :users="departmentUsers"
      :pagination="userPagination"
      @page-change="changeUserPage"
    />

    <!-- Create/Edit Modal -->
    <DepartmentFormModal
      v-model="showModal"
      :mode="modalMode"
      :initial-data="selectedDepartmentForEdit"
      :submitting="submitting"
      :error="error"
      @submit="handleSubmit"
    />
  </div>
</template>
