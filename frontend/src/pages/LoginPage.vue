<template>
  <div class="login-page container">
    <div class="auth-box">
      <h2>{{ $t('auth.login') }}</h2>

      <!-- ─── OTP Mode Tabs (shown when 'both') ──────────────────── -->
      <div v-if="otpMode === 'both'" class="otp-tabs">
        <button
          :class="['otp-tab', { active: activeTab === 'email' }]"
          type="button"
          @click="activeTab = 'email'; resetOtp()"
        >{{ $t('auth.email') }}</button>
        <button
          :class="['otp-tab', { active: activeTab === 'phone' }]"
          type="button"
          @click="activeTab = 'phone'; resetOtp()"
        >{{ $t('auth.phone') }}</button>
      </div>

      <!-- ─── Password Login Form ────────────────────────────────── -->
      <form v-if="!showOtpInput" @submit.prevent="handleLogin" class="auth-form">
        <!-- Email field — shown for email or both modes -->
        <div v-if="activeTab === 'email'" class="auth-field">
          <label>{{ $t('auth.email') }}</label>
          <input type="email" v-model="form.email" required class="auth-input" :placeholder="$t('auth.email')" />
        </div>
        <!-- Phone field — shown for phone or both(phone tab) modes -->
        <div v-if="activeTab === 'phone'" class="auth-field">
          <label>{{ $t('auth.phone') }}</label>
          <PhoneInput
            v-model="form.phone"
            v-model:countryCode="countryCode"
            placeholder="501234567"
            autocomplete="tel-national"
          />
        </div>
        <!-- Password only for email+password flow -->
        <div v-if="activeTab === 'email'" class="auth-field">
          <label>{{ $t('auth.password') }}</label>
          <input type="password" v-model="form.password" class="auth-input" />
        </div>

        <p v-if="errorMsg" class="auth-error">{{ errorMsg }}</p>

        <button type="submit" class="auth-btn" :disabled="isLoading">
          {{ isLoading ? $t('common.loading') : $t('auth.login') }}
        </button>

        <!-- OTP alternative for email mode -->
        <button
          v-if="activeTab === 'email'"
          type="button"
          class="auth-link-btn"
          @click.prevent="handleSendOtp"
        >{{ $t('auth.loginWithOtp', 'Login with OTP instead') }}</button>
      </form>

      <!-- ─── OTP Input Step ─────────────────────────────────────── -->
      <div v-else class="auth-form">
        <p class="otp-hint">
          {{ activeTab === 'phone'
            ? $t('auth.otpSentPhone', { phone: form.phone })
            : $t('auth.otpSentEmail', { email: form.email }) }}
        </p>
        <div class="auth-field">
          <label>{{ $t('auth.verificationCode', 'Verification Code') }}</label>
          <input
            type="text"
            v-model="otpCode"
            maxlength="4"
            class="auth-input otp-code-input"
            placeholder="0000"
            inputmode="numeric"
            autofocus
          />
        </div>
        <p v-if="errorMsg" class="auth-error">{{ errorMsg }}</p>
        <button class="auth-btn" :disabled="isLoading" @click="handleVerifyOtp">
          {{ isLoading ? $t('common.loading') : $t('auth.verify', 'Verify') }}
        </button>
        <button type="button" class="auth-link-btn" :disabled="cooldownLeft > 0" @click="handleSendOtp">
          {{ cooldownLeft > 0
            ? $t('auth.resendIn', { s: cooldownLeft })
            : $t('auth.resendCode', 'Resend Code') }}
        </button>
        <button type="button" class="auth-link-btn secondary" @click="showOtpInput = false; errorMsg = ''">
          ← {{ $t('auth.back', 'Back') }}
        </button>
      </div>

      <div class="auth-footer">
        <router-link to="/register">{{ $t('auth.register') }}</router-link>
      </div>

      <!-- Social Login -->
      <template v-if="anySocialEnabled && !showOtpInput">
        <div class="auth-divider">
          <span>{{ $t('loginModal.orContinueWith') }}</span>
        </div>
        <div class="auth-socials">
          <button
            v-if="socialEnabled.google"
            class="auth-social-btn auth-social-btn--google"
            @click="handleGoogleLogin"
            :disabled="socialLoading"
            type="button"
          >
            <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            {{ $t('loginModal.google') }}
          </button>
          <button
            v-if="socialEnabled.facebook"
            class="auth-social-btn auth-social-btn--facebook"
            @click="handleFacebookLogin"
            :disabled="socialLoading"
            type="button"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            {{ $t('loginModal.facebook') }}
          </button>
          <button
            v-if="socialEnabled.apple"
            class="auth-social-btn auth-social-btn--apple"
            @click="handleAppleLogin"
            :disabled="socialLoading"
            type="button"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
            {{ $t('loginModal.apple') }}
          </button>
        </div>
        <p v-if="socialError" class="auth-error" style="text-align:center">{{ socialError }}</p>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { useI18n } from 'vue-i18n'
import PhoneInput from '@/components/common/PhoneInput.vue'
import { normalizePhoneNumber } from '@/composables/usePhoneOtp'
import { useGoogleLogin, useFacebookLogin, useAppleLogin } from '@/composables/useSocialLogin'
import apiClient from '@/api/client'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const { t } = useI18n()

const otpMode = computed(() => settingsStore.storeSettings.features.otpMode)

// Reactive sync: otpMode loads async from /init, so activeTab MUST be a watch not a one-time ref
const activeTab = ref<'email' | 'phone'>('email')
watch(otpMode, (mode) => {
  if (mode === 'phone') activeTab.value = 'phone'
  else if (mode === 'email') activeTab.value = 'email'
  // 'both' → keep user's selection
}, { immediate: true })

const form = ref({ email: '', phone: '', password: '' })
const countryCode = ref('+966')
const isLoading = ref(false)
const errorMsg = ref('')
const showOtpInput = ref(false)
const otpCode = ref('')
const cooldownLeft = ref(0)
let cooldownTimer: ReturnType<typeof setInterval> | null = null

function resetOtp() {
  showOtpInput.value = false
  otpCode.value = ''
  errorMsg.value = ''
  stopCooldown()
}

function startCooldown(seconds: number) {
  cooldownLeft.value = seconds
  cooldownTimer = setInterval(() => {
    cooldownLeft.value--
    if (cooldownLeft.value <= 0) stopCooldown()
  }, 1000)
}

function stopCooldown() {
  if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null }
  cooldownLeft.value = 0
}

onUnmounted(stopCooldown)

// ─── Social Login ──────────────────────────────────────────────────────────
const socialEnabled = computed(() => ({
  google: settingsStore.storeSettings.features.socialLogin.google.enabled,
  facebook: settingsStore.storeSettings.features.socialLogin.facebook.enabled,
  apple: settingsStore.storeSettings.features.socialLogin.apple.enabled,
}))
const anySocialEnabled = computed(() =>
  socialEnabled.value.google || socialEnabled.value.facebook || socialEnabled.value.apple
)
const socialClientIds = computed(() => ({
  google: settingsStore.storeSettings.features.socialLogin.google.clientId,
  facebook: settingsStore.storeSettings.features.socialLogin.facebook.clientId,
  apple: settingsStore.storeSettings.features.socialLogin.apple.clientId,
}))
const socialLoading = ref(false)
const socialError = ref('')

async function handleGoogleLogin() {
  socialError.value = ''
  if (!socialClientIds.value.google) { socialError.value = 'Google not configured'; return }
  socialLoading.value = true
  try {
    const { loginWithGoogle } = useGoogleLogin(socialClientIds.value.google)
    const { token, name } = await loginWithGoogle()
    const result = await authStore.socialLogin('google', token, name)
    if (result.success) router.push('/account')
    else socialError.value = result.message || 'Google login failed'
  } catch (e: any) {
    socialError.value = e.message || 'Google login failed'
  } finally {
    socialLoading.value = false
  }
}

async function handleFacebookLogin() {
  socialError.value = ''
  if (!socialClientIds.value.facebook) { socialError.value = 'Facebook not configured'; return }
  socialLoading.value = true
  try {
    const { loginWithFacebook } = useFacebookLogin(socialClientIds.value.facebook)
    const { token } = await loginWithFacebook()
    const result = await authStore.socialLogin('facebook', token)
    if (result.success) router.push('/account')
    else socialError.value = result.message || 'Facebook login failed'
  } catch (e: any) {
    socialError.value = e.message || 'Facebook login failed'
  } finally {
    socialLoading.value = false
  }
}

async function handleAppleLogin() {
  socialError.value = ''
  if (!socialClientIds.value.apple) { socialError.value = 'Apple not configured'; return }
  socialLoading.value = true
  try {
    const { loginWithApple } = useAppleLogin(socialClientIds.value.apple)
    const { token, name } = await loginWithApple()
    const result = await authStore.socialLogin('apple', token, name)
    if (result.success) router.push('/account')
    else socialError.value = result.message || 'Apple login failed'
  } catch (e: any) {
    socialError.value = e.message || 'Apple login failed'
  } finally {
    socialLoading.value = false
  }
}

async function handleLogin() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    if (activeTab.value === 'phone' || (otpMode.value !== 'email' && !form.value.password)) {
      // Phone mode or email with no password → OTP flow
      await handleSendOtp()
      return
    }
    // Standard email+password login
    const result = await authStore.login(form.value.email, form.value.password)
    if (result?.token) {
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

async function handleSendOtp() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    let result
    if (activeTab.value === 'phone') {
      if (!form.value.phone.trim()) {
        errorMsg.value = t('checkout.required')
        isLoading.value = false
        return
      }
      const fullPhone = normalizePhoneNumber(countryCode.value, form.value.phone)
      if (!fullPhone) {
        errorMsg.value = 'Invalid phone number format'
        isLoading.value = false
        return
      }
      result = await authStore.sendPhoneOtp(fullPhone)
    } else {
      if (!form.value.email.trim()) {
        errorMsg.value = t('checkout.required')
        isLoading.value = false
        return
      }
      result = await authStore.sendOtp(form.value.email)
    }

    if (result.success) {
      showOtpInput.value = true
      otpCode.value = ''
      startCooldown((result as any).cooldown ?? 60)
    } else {
      errorMsg.value = result.message || t('common.error')
    }
  } finally {
    isLoading.value = false
  }
}


async function handleVerifyOtp() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    let result
    if (activeTab.value === 'phone') {
      const fullPhone = normalizePhoneNumber(countryCode.value, form.value.phone)
      if (!fullPhone) {
        errorMsg.value = 'Invalid phone number format'
        isLoading.value = false
        return
      }
      result = await authStore.verifyPhoneOtp(fullPhone, otpCode.value)
    } else {
      result = await authStore.verifyOtp(form.value.email, otpCode.value)
    }

    if (result.success) {
      router.push('/account')
    } else {
      errorMsg.value = result.message || t('auth.invalidCode', 'Invalid or expired code')
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.login-page {
  padding: 4rem 1rem;
  display: flex;
  justify-content: center;
}
.auth-box {
  max-width: 420px;
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
/* OTP Mode Tabs */
.otp-tabs {
  display: flex;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.otp-tab {
  flex: 1;
  padding: 0.65rem;
  background: #f9fafb;
  border: none;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}
.otp-tab.active {
  background: var(--color-primary, #858585);
  color: #fff;
  font-weight: 600;
}
/* Form */
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
.auth-input:focus { border-color: var(--color-primary, #858585); }
.otp-code-input {
  font-size: 1.5rem;
  text-align: center;
  letter-spacing: 0.5rem;
  font-weight: 700;
}
.otp-hint {
  font-size: 0.875rem;
  color: #6b7280;
  text-align: center;
  margin: 0;
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
}
.auth-btn:hover:not(:disabled) { opacity: 0.9; }
.auth-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.auth-link-btn {
  background: none;
  border: none;
  color: var(--color-primary, #858585);
  font-size: 0.875rem;
  cursor: pointer;
  text-decoration: underline;
  padding: 0;
  text-align: center;
}
.auth-link-btn.secondary { color: #9ca3af; }
.auth-link-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.auth-footer {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.875rem;
}
.auth-footer a {
  color: var(--color-primary, #858585);
  text-decoration: underline;
}
/* Social login section */
.auth-divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 1.5rem 0 1.25rem;
}
.auth-divider::before,
.auth-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e5e7eb;
}
.auth-divider span {
  font-size: 0.75rem;
  color: #9ca3af;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.auth-socials {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.auth-social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  padding: 0.75rem 1rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--store-text-primary, #111827);
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}
.auth-social-btn:hover:not(:disabled) {
  border-color: var(--color-primary, #858585);
  background: #f9fafb;
}
.auth-social-btn:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
