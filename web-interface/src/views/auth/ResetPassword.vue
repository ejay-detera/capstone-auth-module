<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Loader2, Eye, EyeOff, CheckCircle2, LockKeyhole } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const {
  resetPassword,
  resetLoading: isLoading,
  resetSuccess: isSuccess,
  resetErrors: errors,
  resetGeneralError: generalError,
} = useAuth()

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const showPassword = ref(false)

onMounted(() => {
  form.token = route.query.token as string || ''
  form.email = route.query.email as string || ''
  
  if (!form.token || !form.email) {
    generalError.value = 'Invalid or missing reset token. Please request a new link.'
  }
})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleSubmit = async () => {
  await resetPassword(form)
  if (isSuccess.value) {
    setTimeout(() => {
      router.push('/')
    }, 3000)
  }
}
</script>

<template>
  <div class="reset-wrapper">
    <div class="modal-box">
      
      <!-- Success State -->
      <template v-if="isSuccess">
        <div class="modal-content animate-in">
          <div class="success-icon-wrap">
            <CheckCircle2 :size="32" class="success-icon" />
          </div>
          <h2 class="modal-title">Password Reset!</h2>
          <p class="modal-subtitle">Your password has been updated. Redirecting to login...</p>
        </div>
      </template>

      <!-- Form State -->
      <template v-else>
        <div class="modal-content">
          <div class="otp-icon-wrap">
            <LockKeyhole :size="38" class="otp-icon" />
          </div>

          <h2 class="modal-title">Set New Password</h2>
          <p class="modal-subtitle">
            Please choose a strong password to secure your account.
          </p>

          <div v-if="generalError" class="modal-error" role="alert">{{ generalError }}</div>

          <form @submit.prevent="handleSubmit" class="form-wrapper">
            <!-- Password Field -->
            <div class="input-group">
              <div class="relative w-full">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="New Password"
                  class="modal-input"
                  :class="{ 'modal-input--error': errors.password }"
                  required
                />
                <button
                  type="button"
                  @click="togglePassword"
                  class="password-toggle"
                >
                  <Eye v-if="!showPassword" :size="20" />
                  <EyeOff v-else :size="20" />
                </button>
              </div>
              <p v-if="errors.password" class="error-text">
                {{ errors.password[0] }}
              </p>
            </div>

            <!-- Confirm Password Field -->
            <div class="input-group">
              <div class="relative w-full">
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  placeholder="Confirm New Password"
                  class="modal-input"
                  required
                />
              </div>
            </div>

            <button
              type="submit"
              class="modal-btn"
              :disabled="isLoading || !!generalError"
            >
              <Loader2 v-if="isLoading" class="btn-spinner animate-spin" />
              {{ isLoading ? 'Updating Password...' : 'Reset Password' }}
            </button>
          </form>

        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.reset-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f4f4f8;
  padding: 16px;
}

.modal-box {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(37, 37, 120, 0.18);
  width: 100%;
  max-width: 420px;
  padding: 40px 40px 36px;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
  animation: slideUp 0.22s ease;
}

@keyframes slideUp {
  from { transform: translateY(18px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

.animate-in {
  animation: zoomIn 0.3s ease;
}

@keyframes zoomIn {
  from { transform: scale(0.95); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

.modal-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.otp-icon-wrap {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  background: rgba(37, 37, 120, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.otp-icon { color: #252578; }

.modal-title {
  font-size: 24px;
  font-weight: 700;
  color: #252578;
  margin: 0 0 10px 0;
  line-height: 1.2;
}

.modal-subtitle {
  font-size: 13px;
  color: #666;
  margin: 0 0 22px 0;
  line-height: 1.6;
}

.modal-error {
  width: 100%;
  background: #fff5f5;
  border: 1px solid #f8c0c0;
  color: #c0392b;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 14px;
  text-align: left;
  box-sizing: border-box;
}

.form-wrapper {
  width: 100%;
}

.input-group {
  width: 100%;
  text-align: left;
  margin-bottom: 16px;
}

.relative {
  position: relative;
}

.w-full {
  width: 100%;
}

.password-toggle {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #888;
  cursor: pointer;
  padding: 0;
  display: flex;
  transition: color 0.2s;
}

.password-toggle:hover {
  color: #252578;
}

.modal-input {
  width: 100%;
  padding: 13px 14px;
  border: 1px solid rgba(3, 4, 94, 0.25);
  border-radius: 8px;
  font-size: 14px;
  font-family: "Poppins", sans-serif;
  color: #333;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.modal-input[type="password"],
.modal-input[type="text"] {
  padding-right: 48px;
}

.modal-input:focus     { border-color: #252578; }
.modal-input--error    { border-color: #e74c3c; }

.error-text {
  font-size: 12px;
  font-weight: 500;
  color: #e74c3c;
  margin: 4px 0 0 4px;
}

.modal-btn {
  width: 100%;
  padding: 14px;
  background: #252578;
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 16px;
  font-weight: 600;
  font-family: "Poppins", sans-serif;
  cursor: pointer;
  margin-top: 8px;
  transition: opacity 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.modal-btn:hover:not(:disabled) { opacity: 0.88; }

.modal-btn:disabled {
  background: #9999bb;
  cursor: not-allowed;
  opacity: 1;
}

.btn-spinner {
  width: 20px;
  height: 20px;
  color: white;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.success-icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #d1fae5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.success-icon { color: #059669; }
</style>
