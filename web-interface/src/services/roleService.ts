// ─── Role Service ────────────────────────────────────────────────────────────
// HTTP wrappers for role CRUD and role-permission/user association endpoints.

import api from '@/lib/api'
import type { RoleForm } from '@/types'

export const roleService = {
  fetchRoles() {
    return api.get('/api/admin/roles')
  },

  createRole(payload: Omit<RoleForm, 'id'>) {
    return api.post('/api/admin/roles', payload)
  },

  updateRole(id: number, payload: Omit<RoleForm, 'id'>) {
    return api.put(`/api/admin/roles/${id}`, payload)
  },

  deleteRole(id: number) {
    return api.delete(`/api/admin/roles/${id}`)
  },

  fetchRoleUsers(id: number) {
    return api.get(`/api/admin/roles/${id}/users`)
  },

  fetchRolePermissions(id: number) {
    return api.get(`/api/admin/roles/${id}/permissions`)
  },

  syncRolePermissions(id: number, permissionIds: number[]) {
    return api.post(`/api/admin/roles/${id}/permissions`, { permissions: permissionIds })
  },
}
