// ─── User Service ────────────────────────────────────────────────────────────
// HTTP wrappers for user management endpoints (admin scope).

import api from '@/lib/api'
import type { CreateUserPayload } from '@/types'

export interface UserListParams {
  page?: number
  search?: string
  role_id?: string
  department_id?: string
  is_active?: string
  per_page?: number
}

export const userService = {
  fetchUsers(params: UserListParams) {
    return api.get('/api/admin/users', { params })
  },

  createUser(payload: CreateUserPayload) {
    return api.post('/api/admin/users', payload)
  },

  fetchRoleOptions() {
    return api.get('/api/admin/role-options')
  },

  fetchDepartmentOptions() {
    return api.get('/api/admin/department-options')
  },

  assignRole(userId: number, roleId: number | string) {
    return api.patch(`/api/admin/users/${userId}/role`, { role_id: roleId })
  },

  toggleUserStatus(userId: number, password: string) {
    return api.patch(`/api/admin/users/${userId}/status`, { password })
  },
}
