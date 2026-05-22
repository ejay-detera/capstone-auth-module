// ─── Auth Service ────────────────────────────────────────────────────────────
// Thin HTTP wrappers for authentication-related endpoints.
// No reactive state — that responsibility belongs to the useAuth composable.

import api from '@/lib/api'
import type { LoginCredentials, ResetPasswordPayload } from '@/types'

export const authService = {
  login(credentials: LoginCredentials) {
    return api.post('/api/login', credentials)
  },

  logout() {
    return api.post('/api/logout')
  },

  forgotPassword(email: string) {
    return api.post('/api/forgot-password', { email })
  },

  resetPassword(payload: ResetPasswordPayload) {
    return api.post('/api/reset-password', payload)
  },

  /**
   * Verify an email address using a token.
   * Uses the shared `api` instance (not raw axios) to leverage
   * interceptors for auth headers and base URL resolution.
   */
  verifyEmail(token: string) {
    return api.get('/api/verify-email', { params: { token } })
  },

  resendVerification() {
    return api.post('/api/send-verification')
  },
}
