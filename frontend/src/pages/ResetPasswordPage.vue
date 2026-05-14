<template>
  <div class="reset-password-page container">
    <div class="auth-box">
      <h2>{{ $t('auth.resetPassword') }}</h2>

      <div v-if="successMsg" class="auth-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
          <p>{{ successMsg }}</p>
          <router-link to="/login" class="auth-link">{{ $t('auth.login') }}</router-link>
        </div>
      </div>

      <form v-else @submit.prevent="handleReset" class="auth-form">
        <div class="auth-field">
          <label>{{ $t('auth.email') }}</label>
          <input type="email" v-model="form.email" required class="auth-input" :placeholder="$t('auth.email')" />
        </div>
        <div class="auth-field">
          <label>{{ $t('auth.password') }}</label>
          <input type="password" v-model="form.password" required class="auth-input" minlength="8" :placeholder="$t('auth.password')" />
        </div>
        <div class="auth-field">
          <label>{{ $t('auth.confirmPassword') }}</label>
          <input type="password" v-model="form.password_confirmation" required class="auth-input" minlength="8" :placeholder="$t('auth.confirmPassword')" />
        </div>
        <p v-if="errorMsg" class="auth-error">{{ errorMsg }}</p>
        <button type="submit" class="auth-btn" :disabled="isLoading">
          {{ isLoading ? $t('common.loading') : $t('auth.resetPassword') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { resetPasswordApi } from '@/api/services'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const { t } = useI18n()

const form = ref({
  email: '',
  token: '',
  password: '',
  password_confirmation: '',
})
const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

onMounted(() => {
  form.value.token = (route.query.token as string) || ''
  form.value.email = (route.query.email as string) || ''
})

async function handleReset() {
  isLoading.value = true
  errorMsg.value = ''
  try {
    await resetPasswordApi({
      email: form.value.email,
      token: form.value.token,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })
    successMsg.value = t('auth.passwordResetSuccess') || 'Your password has been reset successfully.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || t('common.error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.reset-password-page {
  padding: 4rem 1rem;
  display: flex;
  justify-content: center;
}
.auth-box {
  max-width: 400px;
  width: 100%;
  background: #fff;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.auth-box h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: var(--store-text-primary, #111827);
  text-align: center;
}
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.auth-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.auth-field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--store-text-primary, #111827);
}
.auth-input {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}
.auth-input:focus {
  border-color: var(--color-primary, #858585);
}
.auth-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin: 0;
}
.auth-success {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 1rem;
}
.auth-success svg {
  flex-shrink: 0;
  color: #16a34a;
  margin-top: 2px;
}
.auth-success p {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: #166534;
  line-height: 1.5;
}
.auth-link {
  color: var(--color-primary, #858585);
  font-size: 0.875rem;
  text-decoration: underline;
}
.auth-btn {
  padding: 0.875rem 1rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  margin-top: 0.5rem;
}
.auth-btn:hover:not(:disabled) {
  opacity: 0.9;
}
.auth-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
