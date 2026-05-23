<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next'
import ForgotPasswordModal from '@/components/ForgotPassword.vue'

const router = useRouter()
const {
  login,
  loginLoading: isLoading,
  loginErrors: errors,
  loginGeneralError: generalError,
} = useAuth()

const form = reactive({
  email: '',
  password: ''
})

const showPassword = ref(false)
const showForgot = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  const result = await login(form)

  if (result.success && result.user) {
    const roleName = result.user.profile?.role?.name || result.user.role || ''

    if (['IT Admin', 'Admin', 'Manager', 'Sales', 'Employee', 'Finance Manager', 'Finance Employee', 'Finance', 'Super Admin'].includes(roleName)) {
      router.push('/home')
    } else {
      generalError.value = 'Unrecognized role. Please contact IT Support.'
      localStorage.removeItem('access_token')
      localStorage.removeItem('session_id')
      localStorage.removeItem('user')
    }
  }
}
</script>

<template>
  <!-- Two-panel layout -->
  <div class="login-container">

    <!-- ── LEFT PANEL ── -->
    <div class="left-panel">
      <img class="bg-image" src="@/assets/login.png" alt="Login background" />
      <div class="overlay" />
      <div class="left-text">
        <h1>25 Years of Innovating Diagnostics Solutions</h1>
        <p>ISO 9001:2015 Certified</p>
      </div>
    </div>

    <!-- ── RIGHT PANEL ── -->
    <div class="right-panel">
      <form class="form-box" @submit.prevent="handleLogin">

        <h2 class="title">Welcome Back!</h2>
        <p class="subtitle">Sign in to the Ticketing Management System</p>

        <!-- General / field errors -->
        <div v-if="generalError" class="login-error" role="alert">
          {{ generalError }}
        </div>

        <!-- EMAIL -->
        <div class="input-wrapper">
          <img src="@/assets/email.png" alt="Email" class="input-icon" />
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="Email"
            class="input-field"
            :class="{ 'input-field--error': errors.email }"
            required
          />
        </div>
        <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>

        <!-- PASSWORD -->
        <div class="input-wrapper">
          <img src="@/assets/lock.png" alt="Lock" class="input-icon" />
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Password"
            class="input-field"
            :class="{ 'input-field--error': errors.password }"
            required
          />
          <button
            type="button"
            class="eye-toggle"
            @click="togglePassword"
            aria-label="Toggle password visibility"
          >
            <Eye v-if="!showPassword" :size="18" class="eye-icon" />
            <EyeOff v-else :size="18" class="eye-icon" />
          </button>
        </div>
        <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>

        <!-- SUBMIT -->
        <button
          type="submit"
          class="login-btn"
          :disabled="isLoading"
        >
          <Loader2 v-if="isLoading" class="btn-spinner" :size="18" />
          {{ isLoading ? 'Signing in...' : 'Sign In' }}
        </button>

        <!-- FORGOT PASSWORD -->
        <div class="forgot-row">
          <button type="button" class="forgot-link" @click="showForgot = true">
            Forgot Password?
          </button>
        </div>

        <!-- MODAL -->
        <ForgotPasswordModal v-if="showForgot" @close="showForgot = false" />

      </form>
    </div>

  </div>
</template>

<style scoped>
/* ════════════════════════════════════════
   LOGIN PAGE
════════════════════════════════════════ */

.login-container {
  display: flex;
  height: 100vh;
  background: #f2f7fb;
  font-family: "Poppins", sans-serif;
}

/* ── LEFT SIDE ── */
.left-panel {
  position: relative;
  width: 50%;
  overflow: hidden;
}

.bg-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(37, 37, 120, 0.6);
}

.left-text {
  position: absolute;
  bottom: 60px;
  left: 40px;
  color: white;
}

.left-text h1 {
  font-size: 38px;
  font-weight: 800;
  max-width: 380px;
  line-height: 1.15;
  margin: 0 0 10px 0;
}

.left-text p {
  font-size: 16px;
  font-weight: 400;
  margin: 0;
  opacity: 0.9;
}

/* ── RIGHT SIDE ── */
.right-panel {
  width: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f2f7fb;
}

.form-box {
  width: 65%;
}

/* TITLE */
.title {
  font-size: 42px;
  font-weight: 700;
  color: #252578;
  margin: 0 0 8px 0;
}

/* SUBTITLE */
.subtitle {
  font-size: 16px;
  font-weight: 300;
  color: #656569;
  margin: 0 0 28px 0;
}

/* ── ERROR STATES ── */
.login-error {
  background: #fff5f5;
  border: 1px solid #f8c0c0;
  color: #c0392b;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 400;
  margin-bottom: 14px;
}

.field-error {
  font-size: 12px;
  color: #c0392b;
  margin: -14px 0 12px 4px;
}

/* ── INPUT WRAPPER ── */
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.input-icon {
  position: absolute;
  left: 16px;
  width: 18px;
  height: 18px;
  opacity: 0.4;
  pointer-events: none;
}

/* INPUT FIELDS */
.input-field {
  width: 100%;
  padding: 16px 48px;
  background: #ffffff;
  border: 1px solid rgba(3, 4, 94, 0.25);
  border-radius: 8px;
  font-size: 15px;
  font-weight: 400;
  color: #333;
  font-family: "Poppins", sans-serif;
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.2s;
}

.input-field::placeholder {
  color: rgba(0, 0, 0, 0.35);
}

.input-field:focus {
  border-color: #252578;
}

.input-field--error {
  border-color: #e74c3c;
}

/* EYE TOGGLE */
.eye-toggle {
  position: absolute;
  right: 16px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  color: rgba(0, 0, 0, 0.4);
}

.eye-toggle:hover {
  color: #252578;
}

/* BUTTON */
.login-btn {
  width: 100%;
  padding: 16px;
  background: #252578;
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 18px;
  font-weight: 600;
  font-family: "Poppins", sans-serif;
  cursor: pointer;
  transition: opacity 0.2s;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.login-btn:hover:not(:disabled) {
  opacity: 0.88;
}

.login-btn:disabled {
  background: #9999bb;
  cursor: not-allowed;
  opacity: 1;
}

.btn-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* FORGOT PASSWORD */
.forgot-row {
  text-align: center;
}

.forgot-link {
  font-size: 14px;
  color: #252578;
  font-family: "Poppins", sans-serif;
  font-weight: 400;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .login-container {
    flex-direction: column;
  }

  .left-panel {
    width: 100%;
    height: 220px;
    flex-shrink: 0;
  }

  .right-panel {
    width: 100%;
    height: auto;
    padding: 32px 16px;
  }

  .form-box {
    width: 100%;
  }

  .title {
    font-size: 30px;
  }
}
</style>