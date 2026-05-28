// ─── useDepartments Composable ────────────────────────────────────────────────
// Manages department list, CRUD operations, and department-user association
// with reactive Vue state for the department management page.

import { ref } from 'vue'
import { departmentService } from '@/services/departmentService'
import type { Department, User, PaginationMeta, DepartmentForm } from '@/types'

export function useDepartments() {
  // ── List State ─────────────────────────────────────────────────────────────
  const departments = ref<Department[]>([])
  const loading = ref(true)
  const error = ref('')
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
  })
  const filters = ref({
    search: '',
    per_page: 15,
  })

  const fetchDepartments = async (page = 1) => {
    loading.value = true
    try {
      const response = await departmentService.fetchDepartments({ page, ...filters.value })
      departments.value = response.data.data
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }
    } catch (err: any) {
      console.error('Failed to fetch departments', err)
      error.value = 'Failed to load departments. Please try again.'
    } finally {
      loading.value = false
    }
  }

  // ── CRUD ───────────────────────────────────────────────────────────────────
  const submitting = ref(false)

  const createDepartment = async (form: Omit<DepartmentForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await departmentService.createDepartment(form)
      await fetchDepartments()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      return false
    } finally {
      submitting.value = false
    }
  }

  const updateDepartment = async (id: number, form: Omit<DepartmentForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await departmentService.updateDepartment(id, form)
      await fetchDepartments()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      return false
    } finally {
      submitting.value = false
    }
  }

  const deleteDepartment = async (id: number) => {
    try {
      await departmentService.deleteDepartment(id)
      await fetchDepartments()
      return { success: true }
    } catch (err: any) {
      return {
        success: false,
        message: err.response?.data?.message || 'Failed to delete department',
      }
    }
  }

  // ── Department Users ───────────────────────────────────────────────────────
  const departmentUsers = ref<User[]>([])
  const loadingUsers = ref(false)
  const userPagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
  })

  const fetchDepartmentUsers = async (departmentId: number, page = 1) => {
    loadingUsers.value = true
    try {
      const response = await departmentService.fetchDepartmentUsers(departmentId, page)
      departmentUsers.value = response.data.data
      userPagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }
    } catch (err: any) {
      console.error('Failed to fetch department users', err)
    } finally {
      loadingUsers.value = false
    }
  }

  return {
    // List
    departments,
    loading,
    error,
    pagination,
    filters,
    fetchDepartments,
    // CRUD
    submitting,
    createDepartment,
    updateDepartment,
    deleteDepartment,
    // Department Users
    departmentUsers,
    loadingUsers,
    userPagination,
    fetchDepartmentUsers,
  }
}
