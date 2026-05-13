<template>
  <div class="register-page container">
    <div class="auth-box">
      <h2>{{ $t('auth.register') }}</h2>
      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="auth-field">
          <label>{{ $t('auth.name') }}</label>
          <input type="text" v-model="form.name" required class="auth-input" />
        </div>
        <div class="auth-field">
          <label>{{ $t('auth.email') }}</label>
          <input type="email" v-model="form.email" required class="auth-input" />
        </div>
        <div class="auth-field">
          <label>{{ $t('auth.phone') }}</label>
          <input type="tel" v-model="form.phone" required class="auth-input" />
        </div>
        <div class="auth-field">
          <label>{{ $t('auth.password') }}</label>
          <input type="password" v-model="form.password" required class="auth-input" />
        </div>
        <div class="auth-field">
          <label>{{ $t('auth.confirmPassword') }}</label>
          <input type="password" v-model="form.password_confirmation" required class="auth-input" />
        </div>
        <p v-if="errorMsg" class="auth-error">{{ errorMsg }}</p>
        <button type="submit" class="auth-btn" :disabled="isLoading">
          {{ isLoading ? $t('common.loading') : $t('auth.register') }}
        </button>
      </form>
      <div class="auth-footer">
        <router-link to="/login">{{ $t('auth.login') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})
const isLoading = ref(false)
const errorMsg = ref('')

async function handleRegister() {
  if (form.value.password !== form.value.password_confirmation) {
    errorMsg.value = 'Passwords do not match.'
    return
  }
  
  isLoading.value = true
  errorMsg.value = ''
  try {
    const success = await authStore.register(form.value)
    if (success) {
      router.push('/account')
    } else {
      errorMsg.value = t('common.error')
    }
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || t('common.error')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.register-page {
  padding: 4rem 1rem;
  display: flex;
  justify-content: center;
}
.auth-box {
  max-width: 450px;
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
.auth-footer {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.875rem;
}
.auth-footer a {
  color: var(--color-primary, #858585);
  text-decoration: underline;
}
</style>
