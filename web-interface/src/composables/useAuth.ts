// ─── useAuth Composable ──────────────────────────────────────────────────────
// Manages authentication state, token storage, and session lifecycle.
// Wraps authService with reactive Vue state for component consumption.

import { ref } from 'vue'
import { authService } from '@/services/authService'
import type { LoginCredentials, ResetPasswordPayload } from '@/types'

export function useAuth() {
  // ── Login ──────────────────────────────────────────────────────────────────
  const loginLoading = ref(false)
  const loginErrors = ref<Record<string, string[]>>({})
  const loginGeneralError = ref('')

  const login = async (credentials: LoginCredentials) => {
    loginLoading.value = true
    loginErrors.value = {}
    loginGeneralError.value = ''

    try {
      const response = await authService.login(credentials)
      const { user } = response.data

      localStorage.setItem('user', JSON.stringify(user))

      return { success: true, user }
    } catch (error: any) {
      if (error.response) {
        if (error.response.status === 422) {
          loginErrors.value = error.response.data.errors
        } else if (error.response.status === 429) {
          loginGeneralError.value = error.response.data.message
        } else {
          loginGeneralError.value = error.response.data.message || 'An error occurred during login.'
        }
      } else {
        loginGeneralError.value = 'Cannot connect to the server.'
      }
      return { success: false, user: null }
    } finally {
      loginLoading.value = false
    }
  }

  // ── Logout ─────────────────────────────────────────────────────────────────
  const logout = async () => {
    try {
      await authService.logout()
    } catch {
      // Proceed with local cleanup even if the server call fails
    } finally {
      localStorage.removeItem('user')
    }
  }

  /**
   * Clear local auth state without calling the server.
   * Used by idle-timeout handlers and route guards that need synchronous cleanup.
   */
  const clearLocalAuth = () => {
    localStorage.removeItem('user')
  }

  // ── Forgot Password ───────────────────────────────────────────────────────
  const forgotLoading = ref(false)
  const forgotMessage = ref('')
  const forgotError = ref('')

  const forgotPassword = async (email: string) => {
    forgotLoading.value = true
    forgotMessage.value = ''
    forgotError.value = ''

    try {
      const response = await authService.forgotPassword(email)
      forgotMessage.value = response.data.message || 'If an account exists with that email, we have sent a reset link.'
    } catch (err: any) {
      forgotError.value = err.response?.data?.message || 'Something went wrong. Please try again.'
    } finally {
      forgotLoading.value = false
    }
  }

  // ── Reset Password ─────────────────────────────────────────────────────────
  const resetLoading = ref(false)
  const resetSuccess = ref(false)
  const resetErrors = ref<Record<string, string[]>>({})
  const resetGeneralError = ref('')

  const resetPassword = async (payload: ResetPasswordPayload) => {
    resetLoading.value = true
    resetErrors.value = {}
    resetGeneralError.value = ''

    try {
      await authService.resetPassword(payload)
      resetSuccess.value = true
    } catch (error: any) {
      if (error.response?.status === 422) {
        resetErrors.value = error.response.data.errors
      } else {
        resetGeneralError.value = error.response?.data?.message || 'Failed to reset password. The link may have expired.'
      }
    } finally {
      resetLoading.value = false
    }
  }

  // ── Email Verification ─────────────────────────────────────────────────────
  const verifyStatus = ref<'loading' | 'success' | 'expired' | 'already-verified' | 'error'>('loading')
  const verifyErrorMessage = ref('')

  const verifyEmail = async (token: string | null) => {
    if (!token) {
      verifyStatus.value = 'error'
      verifyErrorMessage.value = 'No verification token provided.'
      return
    }

    try {
      const response = await authService.verifyEmail(token)
      if (response.data.message === 'Email already verified.') {
        verifyStatus.value = 'already-verified'
      } else {
        verifyStatus.value = 'success'
      }
    } catch (error: any) {
      if (error.response?.status === 400) {
        verifyStatus.value = 'expired'
      } else {
        verifyStatus.value = 'error'
        verifyErrorMessage.value = error.response?.data?.message || 'Verification failed.'
      }
    }
  }

  const resending = ref(false)
  const resendMessage = ref('')
  const resendError = ref(false)

  const resendVerification = async () => {
    resending.value = true
    resendMessage.value = ''
    resendError.value = false

    try {
      await authService.resendVerification()
      resendMessage.value = 'Verification email sent successfully.'
    } catch (error: any) {
      resendError.value = true
      resendMessage.value = error.response?.data?.message || 'Failed to resend verification email.'
    } finally {
      resending.value = false
    }
  }

  return {
    // Login
    login,
    loginLoading,
    loginErrors,
    loginGeneralError,
    // Logout
    logout,
    clearLocalAuth,
    // Forgot Password
    forgotPassword,
    forgotLoading,
    forgotMessage,
    forgotError,
    // Reset Password
    resetPassword,
    resetLoading,
    resetSuccess,
    resetErrors,
    resetGeneralError,
    // Email Verification
    verifyEmail,
    verifyStatus,
    verifyErrorMessage,
    resendVerification,
    resending,
    resendMessage,
    resendError,
  }
}
