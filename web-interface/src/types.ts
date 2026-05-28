// ─── Shared TypeScript Interfaces ────────────────────────────────────────────
// Central type definitions used across services, composables, and components.

export interface Role {
  id: number
  name: string
  description?: string
  users_count?: number
}

export interface Department {
  id: number
  name: string
  description?: string
  users_count?: number
}

export interface Permission {
  id: number
  name: string
  slug: string
  description: string
}

export interface UserProfile {
  first_name: string
  last_name: string
  role?: Role
  department?: Department
}

export interface User {
  id: number
  email: string
  is_active: boolean
  profile?: UserProfile
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface CreateUserPayload {
  first_name: string
  last_name: string
  email: string
  role_id: string
  department_id: string
}

export interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface PermissionForm {
  id: number | null
  name: string
  slug: string
  description: string
}

export interface RoleForm {
  id: number | null
  name: string
  description: string
}

export interface DepartmentForm {
  id: number | null
  name: string
  description: string
}
