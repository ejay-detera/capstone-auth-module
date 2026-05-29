// ─── useUsers Composable ─────────────────────────────────────────────────────
// Manages user listing, creation, role assignment, and status toggling
// with reactive Vue state for the admin user management pages.

import { ref } from 'vue'
import { userService } from '@/services/userService'
import type { User, Role, Department, PaginationMeta, CreateUserPayload } from '@/types'

export function useUsers() {
  // ── List State ─────────────────────────────────────────────────────────────
  const users = ref<User[]>([])
  const roles = ref<Role[]>([])
  const departments = ref<Department[]>([])
  const isLoading = ref(true)
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
  })
  const filters = ref({
    role_id: '',
    department_id: '',
    is_active: '',
    search: '',
    per_page: 15,
  })

  const fetchUsers = async (page = 1) => {
    isLoading.value = true
    try {
      const response = await userService.fetchUsers({ page, ...filters.value })
      users.value = response.data.data
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }
    } catch (err) {
      console.error('Failed to fetch users', err)
    } finally {
      isLoading.value = false
    }
  }

  const fetchMetadata = async () => {
    try {
      const [rolesRes, deptsRes] = await Promise.all([
        userService.fetchRoleOptions(),
        userService.fetchDepartmentOptions(),
      ])
      roles.value = rolesRes.data
      departments.value = deptsRes.data
    } catch (err) {
      console.error('Failed to fetch metadata', err)
    }
  }

  // ── Create User ────────────────────────────────────────────────────────────
  const isLoadingData = ref(true)
  const isSubmitting = ref(false)
  const isSuccess = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const generalError = ref('')

  const fetchCreateFormData = async () => {
    try {
      const [rolesRes, deptsRes] = await Promise.all([
        userService.fetchRoleOptions(),
        userService.fetchDepartmentOptions(),
      ])
      roles.value = rolesRes.data
      departments.value = deptsRes.data
    } catch {
      generalError.value = 'Failed to load roles or departments.'
    } finally {
      isLoadingData.value = false
    }
  }

  const createUser = async (payload: CreateUserPayload) => {
    isSubmitting.value = true
    errors.value = {}
    generalError.value = ''

    try {
      await userService.createUser(payload)
      isSuccess.value = true
      setTimeout(() => {
        isSuccess.value = false
      }, 5000)
      return true
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors
      } else if (error.response?.status === 409) {
        errors.value = { email: ['Email already exists'] }
      } else {
        generalError.value = error.response?.data?.message || 'Failed to create user.'
      }
      return false
    } finally {
      isSubmitting.value = false
    }
  }

  // ── Role Assignment ────────────────────────────────────────────────────────
  const isAssigning = ref(false)

  const assignRole = async (userId: number, roleId: number | string) => {
    isAssigning.value = true
    try {
      await userService.assignRole(userId, roleId)
      return { success: true }
    } catch (err: any) {
      return {
        success: false,
        message: err.response?.data?.message || 'Failed to assign role',
      }
    } finally {
      isAssigning.value = false
    }
  }

  // ── Status Toggle ──────────────────────────────────────────────────────────
  const isTogglingStatus = ref(false)
  const confirmPasswordError = ref('')

  const toggleUserStatus = async (userId: number, password: string) => {
    isTogglingStatus.value = true
    confirmPasswordError.value = ''

    try {
      await userService.toggleUserStatus(userId, password)
      return true
    } catch (err: any) {
      confirmPasswordError.value =
        err.response?.data?.message ||
        err.response?.data?.errors?.password?.[0] ||
        'Password verification failed'
      return false
    } finally {
      isTogglingStatus.value = false
    }
  }

  return {
    // List
    users,
    roles,
    departments,
    isLoading,
    pagination,
    filters,
    fetchUsers,
    fetchMetadata,
    // Create
    isLoadingData,
    isSubmitting,
    isSuccess,
    errors,
    generalError,
    fetchCreateFormData,
    createUser,
    // Role Assignment
    isAssigning,
    assignRole,
    // Status Toggle
    isTogglingStatus,
    confirmPasswordError,
    toggleUserStatus,
  }
}
