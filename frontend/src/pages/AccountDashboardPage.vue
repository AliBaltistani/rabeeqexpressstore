<template>
  <div class="account-content-inner">
    <div class="profile-header-row">
      <h2>{{ $t('account.myAccount') }}</h2>
      <button class="btn-logout" @click="handleLogout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        {{ $t('account.logout') }}
      </button>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else class="profile-page-grid">
      <!-- Left: Profile Form -->
      <div class="profile-form-section">
        <form @submit.prevent="saveProfile" class="profile-form">
          <div class="form-row">
            <div class="form-field">
              <label>{{ $t('account.firstName') }}</label>
              <input type="text" v-model="form.firstName" :placeholder="$t('account.firstName')" />
            </div>
            <div class="form-field">
              <label>{{ $t('account.lastName') }}</label>
              <input type="text" v-model="form.lastName" :placeholder="$t('account.lastName')" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-field">
              <label>{{ $t('account.birthDate') }}</label>
              <input type="date" v-model="form.birthDate" />
            </div>
            <div class="form-field">
              <label>{{ $t('account.gender') }}</label>
              <select v-model="form.gender">
                <option value="">{{ $t('account.selectGender') }}</option>
                <option value="male">{{ $t('account.male') }}</option>
                <option value="female">{{ $t('account.female') }}</option>
                <option value="other">{{ $t('account.other') }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-field">
              <label>{{ $t('account.emailAddress') }}</label>
              <input type="email" v-model="form.email" disabled class="input-disabled" />
            </div>
            <div class="form-field">
              <label>{{ $t('account.mobileNumber') }}</label>
              <div class="phone-row">
                <select v-model="phoneCode" class="phone-code-select">
                  <option value="+966">+966</option>
                  <option value="+971">+971</option>
                  <option value="+92">+92</option>
                  <option value="+20">+20</option>
                  <option value="+962">+962</option>
                  <option value="+1">+1</option>
                  <option value="+44">+44</option>
                </select>
                <input type="tel" v-model="form.phone" placeholder="3488092100" />
              </div>
            </div>
          </div>
          <button type="submit" class="btn-save" :disabled="saving">
            {{ saving ? $t('account.saving') : $t('account.save') }}
          </button>
          <p v-if="successMsg" class="msg-success">{{ successMsg }}</p>
          <p v-if="errorMsg" class="msg-error">{{ errorMsg }}</p>
        </form>
      </div>

      <!-- Right: Settings Cards -->
      <div class="settings-section">
        <div class="settings-card">
          <div class="settings-card-row">
            <div class="settings-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="settings-card-info">
              <strong>{{ $t('account.promotionalMessages') }}</strong>
              <p>{{ $t('account.promotionalMessagesDesc') }}</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.promotionalMessages" @change="saveProfile" />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
        <div class="settings-card settings-card--danger">
          <div class="settings-card-row">
            <div class="settings-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <div class="settings-card-info">
              <strong>{{ $t('account.deactivateAccount') }}</strong>
              <p>{{ $t('account.deactivateAccountDesc') }}</p>
            </div>
            <button class="btn-deactivate">{{ $t('account.deactivateAccount') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { fetchProfile, updateProfile } from '@/api/services'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()

const isLoading = ref(true)
const saving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const phoneCode = ref('+966')

const form = ref({
  firstName: '',
  lastName: '',
  birthDate: '',
  gender: '',
  email: '',
  phone: '',
  promotionalMessages: true,
})

onMounted(async () => {
  try {
    const user = await fetchProfile()
    form.value.firstName = user.firstName || user.first_name || ''
    form.value.lastName = user.lastName || user.last_name || ''
    form.value.birthDate = user.birthDate || user.birth_date || ''
    form.value.gender = user.gender || ''
    form.value.email = user.email || ''
    form.value.phone = user.phone || ''
    form.value.promotionalMessages = user.promotionalMessages ?? user.promotional_messages ?? true
  } catch (e) {
    console.error('Failed to load profile', e)
  } finally {
    isLoading.value = false
  }
})

async function saveProfile() {
  saving.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    const payload: any = {
      name: `${form.value.firstName} ${form.value.lastName}`.trim(),
      firstName: form.value.firstName,
      lastName: form.value.lastName,
      birthDate: form.value.birthDate || null,
      gender: form.value.gender || null,
      phone: form.value.phone ? `${phoneCode.value}${form.value.phone}` : null,
      promotionalMessages: form.value.promotionalMessages,
    }
    await updateProfile(payload)
    successMsg.value = t('account.profileSaved')
    // Refresh auth user
    await auth.fetchUser()
    setTimeout(() => { successMsg.value = '' }, 3000)
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || t('common.error')
  } finally {
    saving.value = false
  }
}

async function handleLogout() {
  await auth.logout()
  router.push('/')
}
</script>

<style scoped>
.account-content-inner { flex: 1; }

.profile-header-row {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 2rem;
}
.profile-header-row h2 { font-size: 1.5rem; color: #111827; margin: 0; }

.btn-logout {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1.25rem; border: 1px solid #ef4444;
  border-radius: 8px; background: white; color: #ef4444;
  font-weight: 600; font-size: 0.875rem; cursor: pointer;
  transition: all 0.2s;
}
.btn-logout:hover { background: #fef2f2; }

.profile-page-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;
  align-items: start;
}

/* Form */
.profile-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-field { display: flex; flex-direction: column; gap: 0.375rem; }
.form-field label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
.form-field input, .form-field select {
  padding: 0.625rem 0.875rem; border: 1px solid #d1d5db;
  border-radius: 8px; font-size: 0.9rem; color: #111;
  background: #fff; transition: border-color 0.2s;
}
.form-field input:focus, .form-field select:focus { outline: none; border-color: #6b7280; }
.input-disabled { background: #f3f4f6 !important; color: #9ca3af !important; cursor: not-allowed; }

.phone-row { display: flex; gap: 0; }
.phone-code-select {
  width: 85px; padding: 0.625rem 0.5rem;
  border: 1px solid #d1d5db; border-right: none;
  border-radius: 8px 0 0 8px; background: #f9fafb;
  font-size: 0.875rem; font-weight: 600; cursor: pointer;
}
html[dir="rtl"] .phone-code-select { border-radius: 0 8px 8px 0; border-right: 1px solid #d1d5db; border-left: none; }
.phone-row input { border-radius: 0 8px 8px 0; flex: 1; }
html[dir="rtl"] .phone-row input { border-radius: 8px 0 0 8px; }

.btn-save {
  padding: 0.75rem; background: #6b7280; color: white;
  border: none; border-radius: 8px; font-weight: 600;
  font-size: 0.95rem; cursor: pointer; transition: background 0.2s;
}
.btn-save:hover:not(:disabled) { background: #4b5563; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

.msg-success { color: #059669; font-size: 0.875rem; margin: 0; }
.msg-error { color: #ef4444; font-size: 0.875rem; margin: 0; }

/* Settings cards */
.settings-section { display: flex; flex-direction: column; gap: 1rem; }
.settings-card {
  background: white; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 1.25rem 1.5rem;
}
.settings-card-row {
  display: flex; align-items: center; gap: 1rem;
}
.settings-card-icon { color: #6b7280; flex-shrink: 0; }
.settings-card-info { flex: 1; }
.settings-card-info strong { display: block; color: #111; font-size: 0.95rem; margin-bottom: 0.25rem; }
.settings-card-info p { margin: 0; color: #6b7280; font-size: 0.8125rem; line-height: 1.5; }

/* Toggle */
.toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
  background-color: #d1d5db; border-radius: 26px; transition: 0.3s;
}
.toggle-slider:before {
  position: absolute; content: ""; height: 20px; width: 20px;
  left: 3px; bottom: 3px; background-color: white;
  border-radius: 50%; transition: 0.3s;
}
.toggle-switch input:checked + .toggle-slider { background-color: #10b981; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }

/* Deactivate */
.btn-deactivate {
  padding: 0.5rem 1rem; border: 1px solid #ef4444;
  border-radius: 8px; background: transparent; color: #ef4444;
  font-weight: 600; font-size: 0.8125rem; cursor: pointer;
  white-space: nowrap; transition: all 0.2s; flex-shrink: 0;
}
.btn-deactivate:hover { background: #fef2f2; }

/* Loading */
.loading-state { text-align: center; padding: 4rem; }
.spinner { width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top-color: #6b7280; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .profile-page-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
