// ─── useRoles Composable ─────────────────────────────────────────────────────
// Manages roles data, CRUD operations, permission sync, and user listing
// with reactive Vue state for the role management page.

import { ref, computed } from 'vue'
import { roleService } from '@/services/roleService'
import { permissionService } from '@/services/permissionService'
import type { Role, Permission, User, RoleForm } from '@/types'

export function useRoles() {
  // ── List State ─────────────────────────────────────────────────────────────
  const roles = ref<Role[]>([])
  const loading = ref(true)
  const error = ref('')
  const searchQuery = ref('')

  const filteredRoles = computed(() => {
    if (!Array.isArray(roles.value)) return []
    return roles.value.filter(r =>
      r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  })

  const fetchRoles = async () => {
    loading.value = true
    try {
      const response = await roleService.fetchRoles()
      roles.value = response.data
    } catch (err: any) {
      console.error('Failed to fetch roles', err)
      error.value = 'Failed to load roles. Please try again.'
    } finally {
      loading.value = false
    }
  }

  // ── CRUD ───────────────────────────────────────────────────────────────────
  const submitting = ref(false)

  const createRole = async (form: Omit<RoleForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await roleService.createRole(form)
      await fetchRoles()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      return false
    } finally {
      submitting.value = false
    }
  }

  const updateRole = async (id: number, form: Omit<RoleForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await roleService.updateRole(id, form)
      await fetchRoles()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      return false
    } finally {
      submitting.value = false
    }
  }

  const deleteRole = async (id: number) => {
    try {
      await roleService.deleteRole(id)
      await fetchRoles()
      return { success: true }
    } catch (err: any) {
      return {
        success: false,
        message: err.response?.data?.message || 'Failed to delete role',
      }
    }
  }

  // ── Permission Sync ────────────────────────────────────────────────────────
  const allPermissions = ref<Permission[]>([])
  const selectedPermissionIds = ref<number[]>([])
  const loadingPermissions = ref(false)

  const fetchAllPermissions = async () => {
    try {
      const response = await permissionService.fetchPermissions()
      allPermissions.value = response.data
    } catch (err) {
      console.error('Failed to fetch permissions', err)
    }
  }

  const fetchRolePermissions = async (roleId: number) => {
    loadingPermissions.value = true
    error.value = ''
    selectedPermissionIds.value = []

    try {
      if (allPermissions.value.length === 0) {
        await fetchAllPermissions()
      }
      const res = await roleService.fetchRolePermissions(roleId)
      selectedPermissionIds.value = res.data
    } catch (err: any) {
      console.error('Failed to fetch role permissions', err)
      error.value = 'Failed to load permissions'
    } finally {
      loadingPermissions.value = false
    }
  }

  const syncRolePermissions = async (roleId: number) => {
    submitting.value = true
    error.value = ''
    try {
      await roleService.syncRolePermissions(roleId, selectedPermissionIds.value)
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update permissions'
      return false
    } finally {
      submitting.value = false
    }
  }

  // ── Role Users ─────────────────────────────────────────────────────────────
  const roleUsers = ref<User[]>([])
  const loadingUsers = ref(false)

  const fetchRoleUsers = async (roleId: number) => {
    loadingUsers.value = true
    try {
      const response = await roleService.fetchRoleUsers(roleId)
      roleUsers.value = response.data.data || response.data
    } catch (err) {
      console.error('Failed to fetch role users', err)
    } finally {
      loadingUsers.value = false
    }
  }

  return {
    // List
    roles,
    loading,
    error,
    searchQuery,
    filteredRoles,
    fetchRoles,
    // CRUD
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
  }
}
