// ─── usePermissions Composable ────────────────────────────────────────────────
// Manages permissions list, CRUD operations, and role-permission assignment
// with reactive Vue state for the permission management page.

import { ref, computed } from 'vue'
import { permissionService } from '@/services/permissionService'
import { userService } from '@/services/userService'
import type { Permission, Role, PermissionForm } from '@/types'

export function usePermissions() {
  // ── List State ─────────────────────────────────────────────────────────────
  const permissions = ref<Permission[]>([])
  const roles = ref<Role[]>([])
  const loading = ref(true)
  const error = ref('')
  const searchQuery = ref('')

  const filteredPermissions = computed(() => {
    if (!Array.isArray(permissions.value)) return []
    return permissions.value.filter(p =>
      p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.slug.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  })

  const fetchPermissions = async () => {
    loading.value = true
    try {
      const response = await permissionService.fetchPermissions()
      permissions.value = response.data
    } catch (err: any) {
      console.error('Failed to fetch permissions', err)
      error.value = 'Failed to load permissions. Please try again.'
    } finally {
      loading.value = false
    }
  }

  // ── CRUD ───────────────────────────────────────────────────────────────────
  const submitting = ref(false)

  const createPermission = async (form: Omit<PermissionForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await permissionService.createPermission(form)
      await fetchPermissions()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      return false
    } finally {
      submitting.value = false
    }
  }

  const updatePermission = async (id: number, form: Omit<PermissionForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await permissionService.updatePermission(id, form)
      await fetchPermissions()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      return false
    } finally {
      submitting.value = false
    }
  }

  const deletePermission = async (id: number) => {
    try {
      await permissionService.deletePermission(id)
      await fetchPermissions()
      return { success: true }
    } catch (err: any) {
      return {
        success: false,
        message: err.response?.data?.message || 'Failed to delete permission',
      }
    }
  }

  // ── Role Assignment ────────────────────────────────────────────────────────
  const selectedRoleIds = ref<number[]>([])
  const loadingRoles = ref(false)

  const fetchPermissionRoles = async (permissionId: number) => {
    loadingRoles.value = true
    error.value = ''
    selectedRoleIds.value = []

    try {
      // Fetch all roles if not already loaded
      if (roles.value.length === 0) {
        const rolesRes = await userService.fetchRoleOptions()
        roles.value = rolesRes.data
      }

      // Fetch which roles currently have this permission
      const res = await permissionService.fetchPermissionRoles(permissionId)
      selectedRoleIds.value = res.data.map((r: any) => r.id)
    } catch (err: any) {
      console.error('Failed to fetch roles for permission', err)
      error.value = 'Failed to load roles'
    } finally {
      loadingRoles.value = false
    }
  }

  const syncPermissionRoles = async (permissionId: number) => {
    submitting.value = true
    error.value = ''
    try {
      await permissionService.syncPermissionRoles(permissionId, selectedRoleIds.value)
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update roles'
      return false
    } finally {
      submitting.value = false
    }
  }

  return {
    // List
    permissions,
    roles,
    loading,
    error,
    searchQuery,
    filteredPermissions,
    fetchPermissions,
    // CRUD
    submitting,
    createPermission,
    updatePermission,
    deletePermission,
    // Role Assignment
    selectedRoleIds,
    loadingRoles,
    fetchPermissionRoles,
    syncPermissionRoles,
  }
}
