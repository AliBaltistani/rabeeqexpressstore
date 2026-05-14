<template>
  <div class="account-page container">
    <aside class="account-sidebar">
      <div class="user-info">
        <div class="avatar">{{ userInitials }}</div>
        <h3>{{ auth.user?.name || 'Guest User' }}</h3>
        <p v-if="auth.isAuthenticated">{{ auth.user?.email || '' }}</p>
      </div>
      <nav class="account-nav">
        <router-link v-if="auth.isAuthenticated" to="/account">{{ $t('account.dashboard') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/orders">{{ $t('account.orders') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/profile" class="active">{{ $t('account.profile') }}</router-link>
        <router-link to="/account/wishlist">{{ $t('common.wishlist') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/addresses">{{ $t('account.addresses') || 'Addresses' }}</router-link>
        <button v-if="auth.isAuthenticated" @click="logout" class="logout-btn">{{ $t('auth.logout') }}</button>
      </nav>
    </aside>

    <main class="account-content">
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
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import apiClient from '@/api/client'

const auth = useAuthStore()
const router = useRouter()

const userInitials = computed(() => {
  const name = auth.user?.name || 'G'
  return name.substring(0, 2).toUpperCase()
})

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

async function logout() {
  await auth.logout()
  router.push('/login')
}

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
.account-page {
  padding: 4rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
@media (min-width: 768px) {
  .account-page {
    flex-direction: row;
  }
}
.account-sidebar {
  width: 100%;
  background: #fff;
  border-radius: 12px;
  padding: 2rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
@media (min-width: 768px) {
  .account-sidebar {
    width: 280px;
    flex-shrink: 0;
  }
}
.user-info {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #f3f4f6;
}
.avatar {
  width: 64px;
  height: 64px;
  background: var(--color-primary, #858585);
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin: 0 auto 1rem;
}
.user-info h3 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
  color: var(--store-text-primary, #111827);
}
.user-info p {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}
.account-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.account-nav a, .logout-btn {
  display: block;
  padding: 0.875rem 1rem;
  border-radius: 8px;
  color: #4b5563;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9375rem;
  transition: all 0.2s;
  text-align: left;
  border: none;
  background: transparent;
  cursor: pointer;
}
html[dir="rtl"] .account-nav a, html[dir="rtl"] .logout-btn {
  text-align: right;
}
.account-nav a:hover, .account-nav a.active {
  background: #f9fafb;
  color: var(--color-primary, #858585);
}
.logout-btn {
  color: #ef4444;
}
.logout-btn:hover {
  background: #fef2f2;
}

.account-content {
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
