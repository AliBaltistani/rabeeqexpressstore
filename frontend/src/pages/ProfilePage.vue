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
              <input type="tel" v-model="profileForm.phone" @input="phoneDirty = true" />
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

        <!-- Phone Verification Card (shown only when phone OTP is enabled) -->
        <div v-if="phoneOtpEnabled && profileForm.phone" class="profile-card">
          <div class="card-header phone-verify-header">
            <h3>{{ 'Phone Verification' }}</h3>
            <span v-if="isPhoneVerified && !phoneDirty" class="badge-verified">✓ Verified</span>
            <span v-else class="badge-unverified">Unverified</span>
          </div>
          <div class="profile-form">
            <p class="help-text">
              {{ isPhoneVerified && !phoneDirty
                ? 'Your phone number is verified.'
                : phoneDirty
                  ? 'You changed your phone number. Save your profile first, then verify the new number.'
                  : 'Verify your phone number to enable SMS features.' }}
            </p>

            <!-- OTP send step -->
            <div v-if="!showPhoneOtp && !isPhoneVerified" class="form-actions">
              <button type="button" class="btn-primary" :disabled="phoneDirty || sendingPhoneOtp" @click="sendPhoneOtp">
                {{ sendingPhoneOtp ? $t('common.loading') : 'Send Verification Code' }}
              </button>
            </div>
            <div v-if="phoneOtpError" class="alert-error">{{ phoneOtpError }}</div>

            <!-- OTP verify step -->
            <template v-if="showPhoneOtp">
              <div class="form-group">
                <label>Verification Code</label>
                <input
                  type="text"
                  v-model="phoneOtpCode"
                  maxlength="4"
                  class="otp-input"
                  placeholder="0000"
                  inputmode="numeric"
                  autofocus
                />
              </div>
              <div class="form-actions phone-verify-actions">
                <button type="button" class="btn-primary" :disabled="verifyingPhone || phoneOtpCode.length < 4" @click="verifyPhone">
                  {{ verifyingPhone ? $t('common.loading') : 'Verify' }}
                </button>
                <button type="button" class="btn-secondary" :disabled="phoneOtpCooldown > 0" @click="sendPhoneOtp">
                  {{ phoneOtpCooldown > 0 ? `Resend in ${phoneOtpCooldown}s` : 'Resend Code' }}
                </button>
              </div>
              <div v-if="phoneOtpError" class="alert-error">{{ phoneOtpError }}</div>
              <div v-if="phoneVerifySuccess" class="alert-success">Phone number verified successfully! ✓</div>
            </template>
          </div>
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'
import apiClient from '@/api/client'

const auth = useAuthStore()
const settingsStore = useSettingsStore()
const router = useRouter()

// ─── OTP mode ───
const phoneOtpEnabled = computed(() => settingsStore.storeSettings.features.phoneOtpEnabled)

// ─── Profile Form ───
const profileForm = ref({
  name: auth.user?.name || '',
  phone: auth.user?.phone || ''
})
const isUpdatingProfile = ref(false)
const profileSuccess = ref(false)
const profileError = ref('')
const phoneDirty = ref(false)

// ─── Phone Verification State ───
const isPhoneVerified = computed(() => !!auth.user?.phoneVerifiedAt)
const showPhoneOtp = ref(false)
const phoneOtpCode = ref('')
const sendingPhoneOtp = ref(false)
const verifyingPhone = ref(false)
const phoneOtpError = ref('')
const phoneVerifySuccess = ref(false)
const phoneOtpCooldown = ref(0)
let cooldownTimer: ReturnType<typeof setInterval> | null = null

function startCooldown(seconds: number) {
  phoneOtpCooldown.value = seconds
  cooldownTimer = setInterval(() => {
    phoneOtpCooldown.value--
    if (phoneOtpCooldown.value <= 0 && cooldownTimer) {
      clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}
onUnmounted(() => { if (cooldownTimer) clearInterval(cooldownTimer) })

async function sendPhoneOtp() {
  phoneOtpError.value = ''
  sendingPhoneOtp.value = true
  const result = await auth.sendProfilePhoneOtp(profileForm.value.phone || undefined)
  sendingPhoneOtp.value = false

  if ((result as any).success) {
    showPhoneOtp.value = true
    phoneOtpCode.value = ''
    startCooldown((result as any).cooldown ?? 60)
  } else {
    phoneOtpError.value = (result as any).message || 'Failed to send code'
  }
}

async function verifyPhone() {
  phoneOtpError.value = ''
  verifyingPhone.value = true
  const result = await auth.verifyProfilePhone(profileForm.value.phone, phoneOtpCode.value)
  verifyingPhone.value = false

  if ((result as any).success) {
    phoneVerifySuccess.value = true
    showPhoneOtp.value = false
    phoneDirty.value = false
    setTimeout(() => { phoneVerifySuccess.value = false }, 3000)
  } else {
    phoneOtpError.value = (result as any).message || 'Invalid or expired code'
  }
}

// ─── Password Form ───
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
    phoneDirty.value = false  // reset dirty after save
    showPhoneOtp.value = false
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
.phone-verify-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.badge-verified {
  font-size: 0.75rem;
  font-weight: 600;
  color: #065f46;
  background: #d1fae5;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
.badge-unverified {
  font-size: 0.75rem;
  font-weight: 600;
  color: #92400e;
  background: #fef3c7;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
.otp-input {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 0.5rem;
  text-align: center;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  outline: none;
  width: 100%;
  max-width: 180px;
}
.otp-input:focus {
  border-color: var(--color-primary, #858585);
  box-shadow: 0 0 0 1px var(--color-primary, #858585);
}
.phone-verify-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}
.btn-secondary {
  padding: 0.625rem 1.25rem;
  background: transparent;
  color: var(--color-primary, #858585);
  border: 1px solid var(--color-primary, #858585);
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-secondary:hover { opacity: 0.8; }
.btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
