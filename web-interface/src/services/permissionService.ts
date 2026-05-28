// ─── Permission Service ──────────────────────────────────────────────────────
// HTTP wrappers for permission CRUD and permission-role sync endpoints.

import api from '@/lib/api'
import type { PermissionForm } from '@/types'

export const permissionService = {
  fetchPermissions(params?: { page?: number; per_page?: number; search?: string }) {
    return api.get('/api/admin/permissions', { params })
  },

  createPermission(payload: Omit<PermissionForm, 'id'>) {
    return api.post('/api/admin/permissions', payload)
  },

  updatePermission(id: number, payload: Omit<PermissionForm, 'id'>) {
    return api.put(`/api/admin/permissions/${id}`, payload)
  },

  deletePermission(id: number) {
    return api.delete(`/api/admin/permissions/${id}`)
  },

  fetchPermissionRoles(id: number) {
    return api.get(`/api/admin/permissions/${id}/roles`)
  },

  syncPermissionRoles(id: number, roleIds: number[]) {
    return api.post(`/api/admin/permissions/${id}/roles`, { role_ids: roleIds })
  },
}
