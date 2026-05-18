<template>
  <div class="account-content-inner">
      <div class="header-row">
        <h2>{{ $t('account.profile') }}</h2>
      </div>

      <div class="profile-sections">
        <!-- Personal Info Form -->
        <div class="profile-card">
          <div class="card-header">
            <h3>{{ $t('profile.personalInfo') || 'Personal Information' }}</h3>
          </div>
          <form @submit.prevent="updateProfile" class="profile-form">
            <div class="form-group">
              <label>{{ $t('auth.name') }}</label>
              <input type="text" v-model="profileForm.name" required />
            </div>
            <div class="form-group">
              <label>{{ $t('auth.email') }}</label>
              <input type="email" :value="auth.user?.email" disabled class="disabled-input" />
              <small class="help-text">{{ $t('profile.emailCannotBeChanged') || 'Email address cannot be changed.' }}</small>
            </div>
            <div class="form-group">
              <label>{{ $t('auth.phone') }}</label>
              <input type="tel" v-model="profileForm.phone" />
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary" :disabled="isUpdatingProfile">
                {{ isUpdatingProfile ? ($t('common.loading') || 'Saving...') : ($t('checkout.save') || 'Save Changes') }}
              </button>
            </div>
            <div v-if="profileSuccess" class="alert-success">{{ $t('profile.updateSuccess') || 'Profile updated successfully.' }}</div>
            <div v-if="profileError" class="alert-error">{{ profileError }}</div>
          </form>
        </div>

        <!-- Password Update Form -->
        <div class="profile-card">
          <div class="card-header">
            <h3>{{ $t('profile.changePassword') || 'Change Password' }}</h3>
          </div>
          <form @submit.prevent="updatePassword" class="profile-form">
            <div class="form-group">
              <label>{{ $t('profile.currentPassword') || 'Current Password' }}</label>
              <input type="password" v-model="passwordForm.currentPassword" required />
            </div>
            <div class="form-group">
              <label>{{ $t('profile.newPassword') || 'New Password' }}</label>
              <input type="password" v-model="passwordForm.password" required minlength="8" />
            </div>
            <div class="form-group">
              <label>{{ $t('auth.confirmPassword') }}</label>
              <input type="password" v-model="passwordForm.password_confirmation" required minlength="8" />
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary" :disabled="isUpdatingPassword">
                {{ isUpdatingPassword ? ($t('common.loading') || 'Saving...') : ($t('profile.updatePassword') || 'Update Password') }}
              </button>
            </div>
            <div v-if="passwordSuccess" class="alert-success">{{ $t('profile.passwordSuccess') || 'Password updated successfully.' }}</div>
            <div v-if="passwordError" class="alert-error">{{ passwordError }}</div>
          </form>
        </div>
      </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import apiClient from '@/api/client'

const auth = useAuthStore()
const router = useRouter()

const profileForm = ref({
  name: auth.user?.name || '',
  phone: auth.user?.phone || ''
})
const isUpdatingProfile = ref(false)
const profileSuccess = ref(false)
const profileError = ref('')

const passwordForm = ref({
  currentPassword: '',
  password: '',
  password_confirmation: ''
})
const isUpdatingPassword = ref(false)
const passwordSuccess = ref(false)
const passwordError = ref('')

async function updateProfile() {
  isUpdatingProfile.value = true
  profileSuccess.value = false
  profileError.value = ''
  try {
    const response = await apiClient.put('/profile', profileForm.value)
    if (response.data?.data) {
      auth.user = response.data.data
    }
    profileSuccess.value = true
    setTimeout(() => { profileSuccess.value = false }, 3000)
  } catch (error: any) {
    profileError.value = error.response?.data?.message || 'Failed to update profile'
  } finally {
    isUpdatingProfile.value = false
  }
}

async function updatePassword() {
  isUpdatingPassword.value = true
  passwordSuccess.value = false
  passwordError.value = ''
  
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'Passwords do not match.'
    isUpdatingPassword.value = false
    return
  }

  try {
    await apiClient.put('/profile/password', passwordForm.value)
    passwordSuccess.value = true
    passwordForm.value = { currentPassword: '', password: '', password_confirmation: '' }
    setTimeout(() => { passwordSuccess.value = false }, 3000)
  } catch (error: any) {
    passwordError.value = error.response?.data?.message || 'Failed to update password'
  } finally {
    isUpdatingPassword.value = false
  }
}

onMounted(() => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  }
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}

.header-row {
  margin-bottom: 2rem;
}

.header-row h2 {
  font-size: 1.5rem;
  color: var(--store-text-primary, #111827);
  margin: 0;
}

.profile-sections {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  max-width: 600px;
}

.profile-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  overflow: hidden;
}

.card-header {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}
.card-header h3 {
  margin: 0;
  font-size: 1.125rem;
  color: #111827;
}

.profile-form {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}
.form-group input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9375rem;
  color: #111827;
}
.form-group input:focus {
  outline: none;
  border-color: var(--color-primary, #858585);
  box-shadow: 0 0 0 1px var(--color-primary, #858585);
}
.disabled-input {
  background: #f9fafb;
  color: #9ca3af !important;
  cursor: not-allowed;
}
.help-text {
  font-size: 0.75rem;
  color: #6b7280;
}

.form-actions {
  margin-top: 0.5rem;
}

.btn-primary {
  padding: 0.625rem 1.25rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-primary:hover {
  opacity: 0.9;
}
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.alert-success {
  padding: 0.75rem;
  background: #d1fae5;
  color: #065f46;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-top: 1rem;
}
.alert-error {
  padding: 0.75rem;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-top: 1rem;
}
</style>
