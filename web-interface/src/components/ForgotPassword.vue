<script setup lang="ts">
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { CheckCircle2, Mail, X } from 'lucide-vue-next'

// ── Emits ─────────────────────────────────────────────────────────────────────
const emit = defineEmits<{ (e: 'close'): void }>()
const closeModal = () => emit('close')

// ── State ─────────────────────────────────────────────────────────────────────
const email = ref('')
const {
  forgotPassword,
  forgotLoading: isLoading,
  forgotMessage: message,
  forgotError: error,
} = useAuth()

const handleSubmit = async () => {
  await forgotPassword(email.value)
}
</script>

<template>
  <!-- Backdrop -->
  <div class="modal-backdrop" @click.self="closeModal">

    <!-- Modal box -->
    <div class="modal-box" role="dialog" aria-modal="true">

      <!-- Close button -->
      <button class="modal-close" @click="closeModal" aria-label="Close modal">
        <X :size="18" />
      </button>

      <!-- ── Success state ── -->
      <template v-if="message">
        <div class="modal-content modal-content--left">
          <div class="success-icon-wrap">
            <CheckCircle2 :size="32" class="success-icon" />
          </div>
          <h2 class="modal-title">Reset Link Sent!</h2>
          <p class="modal-subtitle">{{ message }}</p>
          <div class="success-actions">
            <button class="modal-btn modal-btn--auto" @click="closeModal">
              Back to Login
            </button>
          </div>
        </div>
      </template>

      <!-- ── Email entry ── -->
      <template v-else>
        <div class="modal-content">

          <!-- Mail icon -->
          <div class="otp-icon-wrap">
            <Mail :size="38" class="otp-icon" />
          </div>

          <h2 class="modal-title">Forgot Password?</h2>
          <p class="modal-subtitle">
            Enter your registered email address and we'll send you a reset link.
          </p>

          <div v-if="error" class="modal-error" role="alert">{{ error }}</div>

          <input
            id="fp-email"
            v-model="email"
            type="email"
            placeholder="Email address"
            class="modal-input"
            :class="{ 'modal-input--error': error }"
            required
          />

          <button
            class="modal-btn"
            :disabled="isLoading"
            @click="handleSubmit"
          >
            <span v-if="isLoading" class="btn-spinner" />
            {{ isLoading ? 'Sending Link...' : 'Send Reset Link' }}
          </button>

        </div>
      </template>

    </div>
  </div>
</template>

<style scoped>
/* ── Backdrop ── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(10, 10, 40, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(3px);
  animation: fadeIn 0.18s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ── Modal box ── */
.modal-box {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(37, 37, 120, 0.18);
  width: 100%;
  max-width: 420px;
  position: relative;
  padding: 40px 40px 36px;
  box-sizing: border-box;
  animation: slideUp 0.22s ease;
  font-family: "Poppins", sans-serif;
}

@keyframes slideUp {
  from { transform: translateY(18px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ── Close button ── */
.modal-close {
  position: absolute;
  top: 16px;
  right: 18px;
  background: none;
  border: none;
  color: #888;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  border-radius: 4px;
  transition: color 0.15s;
}

.modal-close:hover { color: #252578; }

/* ── Content ── */
.modal-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.modal-content--left {
  align-items: flex-start;
  text-align: left;
}

/* ── Mail icon ── */
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

/* ── Title / subtitle ── */
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

/* ── Error ── */
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

/* ── Input ── */
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
  margin-bottom: 16px;
  transition: border-color 0.2s;
}

.modal-input:focus     { border-color: #252578; }
.modal-input--error    { border-color: #e74c3c; }

/* ── Primary button ── */
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
  margin-bottom: 12px;
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

.modal-btn--auto {
  width: auto;
  padding: 12px 32px;
}

/* ── Spinner ── */
.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Success ── */
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

.success-actions {
  width: 100%;
  display: flex;
  justify-content: flex-end;
  margin-top: 24px;
}
</style>