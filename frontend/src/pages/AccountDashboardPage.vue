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
          <!-- Name -->
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
          <!-- Birth Date + Gender -->
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
          <!-- Email + Phone -->
          <div class="form-row">
            <div class="form-field">
              <label>{{ $t('account.emailAddress') }}</label>
              <input type="email" v-model="form.email" disabled class="input-disabled" />
            </div>
            <div class="form-field">
              <label>{{ $t('account.mobileNumber') }}</label>
              <PhoneInput
                v-model="form.phone"
                v-model:countryCode="phoneCode"
                placeholder="501234567"
              />
            </div>
          </div>
          <!-- Address + City (inline, same form) -->
          <div class="form-row">
            <div class="form-field">
              <label>{{ $t('account.addressLine1') }}</label>
              <input
                type="text"
                v-model="form.address"
                :placeholder="$t('checkout.addressPlaceholder')"
              />
            </div>
            <div class="form-field">
              <label>{{ $t('checkout.city') }}</label>
              <input type="text" v-model="form.city" :placeholder="$t('checkout.city')" />
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
            <button class="btn-deactivate" @click="showDeactivateModal = true">{{ $t('account.deactivateAccount') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Deactivate Confirmation Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showDeactivateModal" class="modal-overlay" @click.self="closeDeactivateModal">
          <Transition name="modal-scale" appear>
            <div class="modal-container">
              <div class="modal-icon-wrapper">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
              </div>
              <h3 class="modal-title">{{ $t('account.deactivateAccount') }}</h3>
              <p class="modal-description">{{ $t('account.deactivateConfirmMessage') || 'Are you sure you want to deactivate your account? You will be logged out and your account will be disabled. Please contact support to reactivate.' }}</p>
              <div class="modal-actions">
                <button class="modal-btn modal-btn--cancel" @click="closeDeactivateModal" :disabled="deactivating">
                  {{ $t('common.cancel') || 'Cancel' }}
                </button>
                <button class="modal-btn modal-btn--danger" @click="confirmDeactivate" :disabled="deactivating">
                  <svg v-if="deactivating" class="btn-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="12"/></svg>
                  {{ deactivating ? ($t('common.loading') || 'Processing...') : ($t('account.confirmDeactivate') || 'Yes, Deactivate') }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { fetchProfile, updateProfile, deactivateAccountApi, fetchAddresses, addAddress, updateAddress } from '@/api/services'
import { useI18n } from 'vue-i18n'
import PhoneInput from '@/components/common/PhoneInput.vue'
import { useCountries } from '@/composables/useCountries'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const { countries } = useCountries()

const isLoading = ref(true)
const saving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const phoneCode = ref('+966')
const rawUserPhone = ref('')
const showDeactivateModal = ref(false)
const deactivating = ref(false)

// Track the user's existing default address ID for update vs. create
const defaultAddressId = ref<number | null>(null)

const form = ref({
  firstName: '',
  lastName: '',
  birthDate: '',
  gender: '',
  email: '',
  phone: '',         // number digits only (code in phoneCode ref)
  address: '',       // addressLine1 from default address
  city: '',          // city from default address
  promotionalMessages: true,
})

/**
 * Parse a full phone string (e.g. "+923488092160") into { code, number }
 * using the live countries list sorted longest-first.
 */
function parsePhoneWithCountries(
  raw: string,
  countryList: any[]
): { code: string; number: string } {
  if (!raw) return { code: '+966', number: '' }
  const sorted = [...countryList].sort((a, b) => b.phone_code.length - a.phone_code.length)
  for (const c of sorted) {
    if (raw.startsWith(c.phone_code)) {
      return { code: c.phone_code, number: raw.slice(c.phone_code.length) }
    }
  }
  const m = raw.match(/^(\+\d{1,4})(.*)$/)
  if (m) return { code: m[1], number: m[2] }
  return { code: '+966', number: raw }
}

// Re-parse phone whenever the countries list loads (async singleton)
watch(
  () => countries.value.length,
  (len) => {
    if (!len || !rawUserPhone.value) return
    const parsed = parsePhoneWithCountries(rawUserPhone.value, countries.value)
    phoneCode.value = parsed.code
    form.value.phone = parsed.number
  }
)

onMounted(async () => {
  try {
    // Load profile and addresses in parallel
    const [user, addresses] = await Promise.all([
      fetchProfile(),
      fetchAddresses().catch(() => []),
    ])

    // Populate profile fields
    const nameParts = (user.name || '').trim().split(' ')
    form.value.firstName = user.firstName || user.first_name || nameParts[0] || ''
    form.value.lastName  = user.lastName  || user.last_name  || nameParts.slice(1).join(' ') || ''
    form.value.birthDate = user.birthDate || user.birth_date || ''
    form.value.gender    = user.gender || ''
    form.value.email     = user.email  || ''
    form.value.promotionalMessages = user.promotionalMessages ?? user.promotional_messages ?? true

    // Store raw phone; watcher re-parses when countries load
    rawUserPhone.value = user.phone || ''
    if (countries.value.length) {
      const parsed = parsePhoneWithCountries(rawUserPhone.value, countries.value)
      phoneCode.value  = parsed.code
      form.value.phone = parsed.number
    }

    // Populate address fields from default (or first) saved address
    const def = (addresses as any[]).find((a: any) => a.isDefault) || (addresses as any[])[0] || null
    if (def) {
      defaultAddressId.value = def.id
      form.value.address = def.addressLine1 && def.addressLine1 !== '-' ? def.addressLine1 : ''
      form.value.city    = def.city    && def.city    !== '-' ? def.city    : ''
    }
  } catch (e) {
    console.error('Failed to load profile', e)
  } finally {
    isLoading.value = false
  }
})

async function saveProfile() {
  saving.value = true
  successMsg.value = ''
  errorMsg.value   = ''
  try {
    // 1. Save profile info
    const fullPhone = form.value.phone ? `${phoneCode.value}${form.value.phone}` : null
    await updateProfile({
      name:                `${form.value.firstName} ${form.value.lastName}`.trim(),
      firstName:           form.value.firstName,
      lastName:            form.value.lastName,
      birthDate:           form.value.birthDate || null,
      gender:              form.value.gender    || null,
      phone:               fullPhone,
      promotionalMessages: form.value.promotionalMessages,
    } as any)
    await auth.fetchUser()

    // 2. Persist address fields to user_addresses (fire-and-forget style)
    if (form.value.address.trim()) {
      const fullName = `${form.value.firstName} ${form.value.lastName}`.trim()
      const nameParts = fullName.split(' ')
      const addrPayload = {
        firstName:    nameParts[0]              || 'Customer',
        lastName:     nameParts.slice(1).join(' ') || '-',
        phone:        fullPhone || '',
        addressLine1: form.value.address.trim(),
        city:         form.value.city.trim()    || '-',
        country:      '-',
        isDefault:    true,
      } as any

      try {
        if (defaultAddressId.value) {
          await updateAddress(defaultAddressId.value, addrPayload)
        } else {
          const result: any = await addAddress(addrPayload)
          defaultAddressId.value = result?.id ?? null
        }
      } catch { /* don't block profile save on address error */ }
    }

    successMsg.value = t('account.profileSaved')
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

function closeDeactivateModal() {
  if (!deactivating.value) showDeactivateModal.value = false
}

async function confirmDeactivate() {
  deactivating.value = true
  try {
    await deactivateAccountApi()
    showDeactivateModal.value = false
    await auth.logout()
    router.push('/')
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || t('common.error')
    showDeactivateModal.value = false
  } finally {
    deactivating.value = false
  }
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
.profile-form-section { display: flex; flex-direction: column; gap: 2rem; }
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

.btn-save {
  padding: 0.75rem; background: #6b7280; color: white;
  border: none; border-radius: 8px; font-weight: 600;
  font-size: 0.95rem; cursor: pointer; transition: background 0.2s;
}
.btn-save:hover:not(:disabled) { background: #4b5563; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

.msg-success { color: #059669; font-size: 0.875rem; margin: 0; }
.msg-error   { color: #ef4444; font-size: 0.875rem; margin: 0; }

/* Settings cards */
.settings-section { display: flex; flex-direction: column; gap: 1rem; }
.settings-card {
  background: white; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 1.25rem 1.5rem;
}
.settings-card-row { display: flex; align-items: center; gap: 1rem; }
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

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 1.5rem;
}
.modal-container {
  background: white; border-radius: 20px;
  padding: 2.5rem; max-width: 420px; width: 100%;
  text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}
.modal-icon-wrapper {
  width: 72px; height: 72px; margin: 0 auto 1.5rem;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%; background: linear-gradient(135deg,#fef2f2,#fee2e2); color: #ef4444;
}
.modal-title { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0 0 0.75rem; }
.modal-description { font-size: 0.875rem; color: #6b7280; line-height: 1.6; margin: 0 0 2rem; }
.modal-actions { display: flex; gap: 0.75rem; }
.modal-btn {
  flex: 1; padding: 0.75rem 1.25rem; border-radius: 10px;
  font-weight: 600; font-size: 0.9rem; cursor: pointer; border: none;
  transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
}
.modal-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.modal-btn--cancel { background: #f3f4f6; color: #374151; }
.modal-btn--cancel:hover:not(:disabled) { background: #e5e7eb; }
.modal-btn--danger { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 4px 12px rgba(239,68,68,.35); }
.modal-btn--danger:hover:not(:disabled) { background: linear-gradient(135deg,#dc2626,#b91c1c); box-shadow: 0 6px 16px rgba(239,68,68,.45); transform: translateY(-1px); }
.btn-spinner { animation: spin 0.8s linear infinite; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-scale-enter-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.modal-scale-leave-active { transition: all 0.2s ease; }
.modal-scale-enter-from { opacity: 0; transform: scale(0.9); }
.modal-scale-leave-to   { opacity: 0; transform: scale(0.95); }

@media (max-width: 768px) {
  .profile-page-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
