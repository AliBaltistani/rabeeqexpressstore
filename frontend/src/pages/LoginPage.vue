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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { useI18n } from 'vue-i18n'
import PhoneInput from '@/components/common/PhoneInput.vue'

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
      const fullPhone = countryCode.value + form.value.phone.replace(/^0+/, '')
      result = await authStore.sendPhoneOtp(fullPhone)
    } else {
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
      result = await authStore.verifyPhoneOtp(form.value.phone, otpCode.value)
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
</style>
