// ─── Department Service ──────────────────────────────────────────────────────
// HTTP wrappers for department CRUD and department-user association endpoints.

import api from '@/lib/api'
import type { DepartmentForm } from '@/types'

export const departmentService = {
  fetchDepartments() {
    return api.get('/api/admin/departments')
  },

  createDepartment(payload: Omit<DepartmentForm, 'id'>) {
    return api.post('/api/admin/departments', payload)
  },

  updateDepartment(id: number, payload: Omit<DepartmentForm, 'id'>) {
    return api.put(`/api/admin/departments/${id}`, payload)
  },

  deleteDepartment(id: number) {
    return api.delete(`/api/admin/departments/${id}`)
  },

  fetchDepartmentUsers(id: number, page = 1) {
    return api.get(`/api/admin/departments/${id}/users`, { params: { page } })
  },
}
