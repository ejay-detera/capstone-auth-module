<template>
  <div class="verification-wrapper">
    <div class="modal-box">
      <div class="modal-content animate-in">

        <!-- Loading State -->
        <template v-if="status === 'loading'">
          <div class="otp-icon-wrap">
            <Loader2 :size="38" class="otp-icon animate-spin" />
          </div>
          <h2 class="modal-title">Verifying Email...</h2>
          <p class="modal-subtitle">Please wait while we confirm your email address.</p>
        </template>

        <!-- Success State -->
        <template v-else-if="status === 'success'">
          <div class="success-icon-wrap">
            <CheckCircle2 :size="32" class="success-icon" />
          </div>
          <h2 class="modal-title">Email Verified!</h2>
          <p class="modal-subtitle">Your email has been successfully verified. You can now access all features.</p>
          <router-link to="/" class="modal-btn" style="text-decoration: none;">
            Go to Dashboard
          </router-link>
        </template>

        <!-- Expired State -->
        <template v-else-if="status === 'expired'">
          <div class="error-icon-wrap">
            <XCircle :size="32" class="error-icon" />
          </div>
          <h2 class="modal-title">Link Expired</h2>
          <p class="modal-subtitle">This verification link has expired or is invalid.</p>
          
          <button 
            @click="resendVerification" 
            class="modal-btn"
            :disabled="resending"
          >
            <Loader2 v-if="resending" class="btn-spinner animate-spin" />
            {{ resending ? 'Sending...' : 'Resend Verification Email' }}
          </button>
          
          <p v-if="resendMessage" :class="resendError ? 'text-error' : 'text-success'" class="status-msg">
            {{ resendMessage }}
          </p>
        </template>

        <!-- Already Verified State -->
        <template v-else-if="status === 'already-verified'">
          <div class="info-icon-wrap">
            <Info :size="32" class="info-icon" />
          </div>
          <h2 class="modal-title">Already Verified</h2>
          <p class="modal-subtitle">Your email is already verified. You're all set!</p>
          <router-link to="/" class="modal-btn" style="text-decoration: none;">
            Go to Dashboard
          </router-link>
        </template>

        <!-- Error State -->
        <template v-else-if="status === 'error'">
          <div class="error-icon-wrap">
            <XCircle :size="32" class="error-icon" />
          </div>
          <h2 class="modal-title">Verification Failed</h2>
          <p class="modal-subtitle">{{ errorMessage || 'An unexpected error occurred.' }}</p>
          <router-link to="/" class="modal-btn modal-btn-secondary" style="text-decoration: none;">
            Back to Home
          </router-link>
        </template>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Loader2, CheckCircle2, XCircle, Info } from 'lucide-vue-next'

const route = useRoute()
const {
  verifyEmail,
  verifyStatus: status,
  verifyErrorMessage: errorMessage,
  resendVerification,
  resending,
  resendMessage,
  resendError,
} = useAuth()

onMounted(() => {
  const token = route.query.token as string | null
  verifyEmail(token)
})
</script>

<style scoped>
.verification-wrapper {
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

/* Icon Wrappers */
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

.success-icon-wrap {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  background: #d1fae5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}
.success-icon { color: #059669; }

.error-icon-wrap {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  background: #fee2e2;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}
.error-icon { color: #dc2626; }

.info-icon-wrap {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  background: #dbeafe;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}
.info-icon { color: #2563eb; }

/* Text Styles */
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

/* Button Styles */
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

.modal-btn-secondary {
  background: #64748b;
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

.status-msg {
  font-size: 13px;
  font-weight: 500;
  margin-top: 14px;
}
.text-error { color: #dc2626; }
.text-success { color: #059669; }
</style>
