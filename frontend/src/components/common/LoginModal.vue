<template>
  <Teleport to="body">
    <Transition name="login-modal">
      <div v-if="visible" class="login-modal-overlay" @click.self="close">
        <div class="login-modal" role="dialog" aria-modal="true" :aria-label="$t('loginModal.title')">
          <!-- Close button -->
          <button class="login-modal__close" @click="close" :aria-label="$t('common.close')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>

          <!-- Step 1: Email/Phone Input -->
          <div v-if="step === 'email'" class="login-modal__step">
            <div class="login-modal__icon">
              <!-- Icon changes by active tab -->
              <svg v-if="activeTab === 'email'" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>
              </svg>
              <svg v-else width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
            </div>
            <h2 class="login-modal__title">{{ $t('loginModal.title') }}</h2>
            <!-- Dynamic subtitle by OTP mode -->
            <p class="login-modal__subtitle">
              <template v-if="otpMode === 'phone'">Enter your phone number to receive a one-time code via SMS.</template>
              <template v-else-if="otpMode === 'both'">Enter your email or phone number to receive a one-time code.</template>
              <template v-else>{{ $t('loginModal.subtitle') }}</template>
            </p>

            <!-- OTP channel tabs (only when mode = 'both') -->
            <div v-if="otpMode === 'both'" class="login-modal__tabs">
              <button
                :class="['login-modal__tab', { active: activeTab === 'email' }]"
                type="button"
                @click="switchTab('email')"
              >{{ $t('auth.email') }}</button>
              <button
                :class="['login-modal__tab', { active: activeTab === 'phone' }]"
                type="button"
                @click="switchTab('phone')"
              >{{ $t('auth.phone') }}</button>
            </div>

            <form @submit.prevent="handleSendOtp" class="login-modal__form">
              <div class="login-modal__field">
                <!-- Email input -->
                <template v-if="activeTab === 'email'">
                  <label class="login-modal__label">{{ $t('loginModal.emailLabel') }}</label>
                  <input
                    ref="emailInputRef"
                    type="email"
                    v-model="email"
                    class="login-modal__input"
                    :class="{ 'input-error': emailError }"
                    :placeholder="$t('loginModal.emailPlaceholder')"
                    autocomplete="email"
                    @keydown.enter.prevent="handleSendOtp"
                  />
                </template>
                <!-- Phone input -->
                <template v-else>
                  <label class="login-modal__label">{{ $t('auth.phone') }}</label>
                  <PhoneInput
                    v-model="phone"
                    v-model:countryCode="countryCode"
                    placeholder="501234567"
                    :error="!!emailError"
                    @enter="handleSendOtp"
                  />
                </template>
                <span v-if="emailError" class="login-modal__error">{{ emailError }}</span>
              </div>
              <button type="submit" class="login-modal__btn login-modal__btn--primary" :disabled="sending">
                <span v-if="sending" class="login-modal__spinner"></span>
                {{ sending ? $t('common.loading') : $t('loginModal.continue') }}
              </button>
            </form>

            <!-- Social login — hidden in phone-only mode -->
            <template v-if="otpMode !== 'phone'">
              <div class="login-modal__divider">
                <span>{{ $t('loginModal.orContinueWith') }}</span>
              </div>
              <div class="login-modal__socials">
                <button class="login-modal__social-btn login-modal__social-btn--google" @click="showComingSoon" type="button">
                  <svg width="20" height="20" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                  <span>{{ $t('loginModal.google') }}</span>
                </button>
                <button class="login-modal__social-btn login-modal__social-btn--facebook" @click="showComingSoon" type="button">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                  <span>{{ $t('loginModal.facebook') }}</span>
                </button>
                <button class="login-modal__social-btn login-modal__social-btn--apple" @click="showComingSoon" type="button">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                  <span>{{ $t('loginModal.apple') }}</span>
                </button>
              </div>
            </template>
          </div>

          <!-- Step 2: OTP Verification -->
          <div v-if="step === 'otp'" class="login-modal__step">
            <button class="login-modal__back" @click="step = 'email'" type="button">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
              {{ $t('loginModal.back') }}
            </button>

            <div class="login-modal__icon">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
            <h2 class="login-modal__title">{{ $t('loginModal.otpTitle') }}</h2>
            <p class="login-modal__subtitle">
              {{ activeTab === 'phone'
                ? $t('loginModal.otpSubtitlePhone', { phone })
                : $t('loginModal.otpSubtitle', { email }) }}
            </p>

            <!-- OTP Input Boxes -->
            <div class="login-modal__otp-row">
              <input
                v-for="(_, idx) in 4"
                :key="idx"
                :ref="(el) => { if (el) otpRefs[idx] = el as HTMLInputElement }"
                type="text"
                inputmode="numeric"
                maxlength="1"
                class="login-modal__otp-box"
                :class="{ 'input-error': otpError }"
                :value="otpDigits[idx]"
                @input="handleOtpInput(idx, $event)"
                @keydown.backspace="handleOtpBackspace(idx, $event)"
                @paste="handleOtpPaste($event)"
              />
            </div>
            <span v-if="otpError" class="login-modal__error login-modal__error--center">{{ otpError }}</span>

            <button
              class="login-modal__btn login-modal__btn--primary"
              @click="handleVerifyOtp"
              :disabled="verifying || otpDigits.join('').length < 4"
            >
              <span v-if="verifying" class="login-modal__spinner"></span>
              {{ verifying ? $t('common.loading') : $t('loginModal.verify') }}
            </button>

            <!-- Resend -->
            <div class="login-modal__resend">
              <template v-if="resendCooldown > 0">
                <span class="login-modal__resend-timer">{{ $t('loginModal.resendIn', { seconds: resendCooldown }) }}</span>
              </template>
              <template v-else>
                <button class="login-modal__resend-btn" @click="handleResendOtp" :disabled="resending" type="button">
                  {{ resending ? $t('common.loading') : $t('loginModal.resendCode') }}
                </button>
              </template>
            </div>
          </div>

          <!-- Coming Soon Toast -->
          <Transition name="toast-fade">
            <div v-if="comingSoonVisible" class="login-modal__toast">
              {{ $t('loginModal.comingSoon') }}
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted, computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { useI18n } from 'vue-i18n'
import PhoneInput from '@/components/common/PhoneInput.vue'
import { normalizePhoneNumber } from '@/composables/usePhoneOtp'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{
  (e: 'update:visible', val: boolean): void
  (e: 'authenticated'): void
}>()

const auth = useAuthStore()
const settingsStore = useSettingsStore()
const { t } = useI18n()

const otpMode = computed(() => settingsStore.storeSettings.features.otpMode)
const activeTab = ref<'email' | 'phone'>('email')

// Keep activeTab in sync with otpMode — fires immediately and whenever otpMode changes
// (settingsStore loads asynchronously from /init, so this MUST be a watch not a one-time ref)
watch(otpMode, (mode) => {
  if (mode === 'phone') activeTab.value = 'phone'
  else if (mode === 'email') activeTab.value = 'email'
  // 'both' → keep whatever the user selected
}, { immediate: true })

function switchTab(tab: 'email' | 'phone') {
  activeTab.value = tab
  emailError.value = ''
}

// State
const step = ref<'email' | 'otp'>('email')
const email = ref('')
const phone = ref('')
const countryCode = ref('+966')
const emailError = ref('')
const sending = ref(false)

const otpDigits = ref(['', '', '', ''])
const otpRefs = ref<HTMLInputElement[]>([])
const otpError = ref('')
const verifying = ref(false)

const resendCooldown = ref(0)
const resending = ref(false)
let cooldownTimer: ReturnType<typeof setInterval> | null = null

const comingSoonVisible = ref(false)
let comingSoonTimer: ReturnType<typeof setTimeout> | null = null

const emailInputRef = ref<HTMLInputElement | null>(null)

// Helpers
function isEmail(v: string) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) }

function close() {
  emit('update:visible', false)
}

function resetState() {
  step.value = 'email'
  email.value = ''
  phone.value = ''
  emailError.value = ''
  otpDigits.value = ['', '', '', '']
  otpError.value = ''
  resendCooldown.value = 0
  if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null }
  // Re-sync tab with current mode on every modal open
  if (otpMode.value === 'phone') activeTab.value = 'phone'
  else if (otpMode.value === 'email') activeTab.value = 'email'
}

function startCooldown(seconds: number) {
  resendCooldown.value = seconds
  if (cooldownTimer) clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0 && cooldownTimer) {
      clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}

// Watch visibility
watch(() => props.visible, (val) => {
  if (val) {
    resetState()
    nextTick(() => emailInputRef.value?.focus())
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

// Escape key
function onEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.visible) close()
}
if (typeof window !== 'undefined') window.addEventListener('keydown', onEscape)
onUnmounted(() => {
  if (typeof window !== 'undefined') window.removeEventListener('keydown', onEscape)
  if (cooldownTimer) clearInterval(cooldownTimer)
  if (comingSoonTimer) clearTimeout(comingSoonTimer)
  document.body.style.overflow = ''
})

// ── Step 1: Send OTP ──
async function handleSendOtp() {
  emailError.value = ''
  let result: any

  try {
    if (activeTab.value === 'phone') {
      if (!phone.value.trim()) { emailError.value = t('checkout.required'); return }
      const fullPhone = normalizePhoneNumber(countryCode.value, phone.value)
      if (!fullPhone) {
        emailError.value = 'Invalid phone number format'
        return
      }
      sending.value = true
      result = await auth.sendPhoneOtp(fullPhone)
    } else {
      if (!email.value.trim()) { emailError.value = t('checkout.required'); return }
      if (!isEmail(email.value)) { emailError.value = t('checkout.invalidEmail'); return }
      sending.value = true
      result = await auth.sendOtp(email.value)
    }
    sending.value = false

    if (result.success) {
      step.value = 'otp'
      startCooldown((result as any).cooldown || 60)
      nextTick(() => otpRefs.value[0]?.focus())
    } else {
      emailError.value = (result as any).message || t('common.error')
    }
  } catch (error: any) {
    sending.value = false
    emailError.value = error.message || t('common.error')
  }
}


// ── OTP Input Management ──
function handleOtpInput(idx: number, event: Event) {
  const target = event.target as HTMLInputElement
  const val = target.value.replace(/\D/g, '')
  otpDigits.value[idx] = val.slice(-1)
  otpError.value = ''

  if (val && idx < 3) {
    nextTick(() => otpRefs.value[idx + 1]?.focus())
  }
  // Auto-submit when all 4 digits entered
  if (otpDigits.value.join('').length === 4) {
    handleVerifyOtp()
  }
}

function handleOtpBackspace(idx: number, event: KeyboardEvent) {
  if (!otpDigits.value[idx] && idx > 0) {
    event.preventDefault()
    otpDigits.value[idx - 1] = ''
    nextTick(() => otpRefs.value[idx - 1]?.focus())
  }
}

function handleOtpPaste(event: ClipboardEvent) {
  event.preventDefault()
  const paste = event.clipboardData?.getData('text')?.replace(/\D/g, '') || ''
  for (let i = 0; i < 4; i++) {
    otpDigits.value[i] = paste[i] || ''
  }
  nextTick(() => {
    const nextEmpty = otpDigits.value.findIndex(d => !d)
    if (nextEmpty >= 0) otpRefs.value[nextEmpty]?.focus()
    else otpRefs.value[3]?.focus()
  })
  if (paste.length >= 4) {
    nextTick(() => handleVerifyOtp())
  }
}

// ── Step 2: Verify OTP ──
async function handleVerifyOtp() {
  otpError.value = ''
  const code = otpDigits.value.join('')
  if (code.length < 4) { otpError.value = t('loginModal.invalidOtp'); return }

  verifying.value = true
  let result: any

  try {
    if (activeTab.value === 'phone') {
      const fullPhone = normalizePhoneNumber(countryCode.value, phone.value)
      if (!fullPhone) {
        otpError.value = 'Invalid phone number format'
        verifying.value = false
        return
      }
      result = await auth.verifyPhoneOtp(fullPhone, code)
    } else {
      result = await auth.verifyOtp(email.value, code)
    }
    verifying.value = false

    if (result.success) {
      emit('authenticated')
      close()
    } else {
      otpError.value = (result as any).message || t('loginModal.invalidOtp')
      otpDigits.value = ['', '', '', '']
      nextTick(() => otpRefs.value[0]?.focus())
    }
  } catch (error: any) {
    verifying.value = false
    otpError.value = error.message || t('common.error')
  }
}

// ── Resend ──
async function handleResendOtp() {
  resending.value = true
  let result: any

  try {
    if (activeTab.value === 'phone') {
      const fullPhone = normalizePhoneNumber(countryCode.value, phone.value)
      if (!fullPhone) {
        otpError.value = 'Invalid phone number format'
        resending.value = false
        return
      }
      result = await auth.resendPhoneOtp(fullPhone)
    } else {
      result = await auth.resendOtp(email.value)
    }
    resending.value = false

    if (result.success) {
      startCooldown((result as any).cooldown || 60)
      otpDigits.value = ['', '', '', '']
      otpError.value = ''
      nextTick(() => otpRefs.value[0]?.focus())
    } else {
      otpError.value = (result as any).message || t('common.error')
    }
  } catch (error: any) {
    resending.value = false
    otpError.value = error.message || t('common.error')
  }
}

// ── Social (Coming Soon) ──
function showComingSoon() {
  comingSoonVisible.value = true
  if (comingSoonTimer) clearTimeout(comingSoonTimer)
  comingSoonTimer = setTimeout(() => { comingSoonVisible.value = false }, 2500)
}
</script>

<style scoped>
/* Overlay */
.login-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

/* Modal */
.login-modal {
  position: relative;
  background: var(--bg-primary, #fff);
  border-radius: var(--radius-2xl, 16px);
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: 420px;
  padding: 2rem 2rem 1.75rem;
  overflow: hidden;
}

.login-modal__close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: none;
  border: none;
  color: var(--footer-text-color, #666);
  cursor: pointer;
  padding: 4px;
  border-radius: var(--radius-full, 50%);
  transition: all 0.2s;
  z-index: 1;
}
html[dir="rtl"] .login-modal__close { right: auto; left: 1rem; }
.login-modal__close:hover { background: var(--bg-secondary, #f5f5f5); color: var(--store-text-primary, #1a1a1a); }

.login-modal__back {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  color: var(--footer-text-color, #666);
  font-size: 0.8125rem;
  cursor: pointer;
  padding: 0;
  margin-bottom: 1rem;
  transition: color 0.2s;
}
.login-modal__back:hover { color: var(--color-primary); }

/* Step content */
.login-modal__step {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.login-modal__icon { margin-bottom: 1rem; }

.login-modal__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--store-text-primary, #1a1a1a);
  margin: 0 0 0.375rem;
  text-align: center;
}

.login-modal__subtitle {
  font-size: 0.875rem;
  color: var(--footer-text-color, #666);
  margin: 0 0 1.25rem;
  text-align: center;
  line-height: 1.4;
  word-break: break-word;
}

/* Form */
.login-modal__form { width: 100%; }

.login-modal__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}

.login-modal__label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--store-text-primary, #1a1a1a);
}

.login-modal__input {
  padding: 0.75rem 1rem;
  border: 1.5px solid var(--product-border-color, #e5e5e5);
  border-radius: var(--radius-lg, 12px);
  font-size: 0.9375rem;
  outline: none;
  color: var(--store-text-primary, #1a1a1a);
  background: var(--bg-primary, #fff);
  width: 100%;
  box-sizing: border-box;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.login-modal__input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb, 79, 70, 229), 0.1); }
.login-modal__input.input-error { border-color: #ef4444; }

.login-modal__error {
  font-size: 0.75rem;
  color: #ef4444;
}
.login-modal__error--center { text-align: center; display: block; margin-top: 0.5rem; }

/* Buttons */
.login-modal__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.875rem 1rem;
  border: none;
  border-radius: var(--radius-lg, 12px);
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.login-modal__btn--primary {
  background: var(--color-primary);
  color: #fff;
}
.login-modal__btn--primary:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }
.login-modal__btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* OTP Channel Tabs */
.login-modal__tabs {
  display: flex;
  border: 1.5px solid var(--product-border-color, #e5e5e5);
  border-radius: var(--radius-lg, 12px);
  overflow: hidden;
  margin-bottom: 1rem;
  width: 100%;
}
.login-modal__tab {
  flex: 1;
  padding: 0.6rem;
  background: var(--bg-secondary, #f9fafb);
  border: none;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  color: var(--footer-text-color, #666);
  transition: all 0.15s;
}
.login-modal__tab.active {
  background: var(--color-primary);
  color: #fff;
  font-weight: 600;
}

.login-modal__spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: login-spin 0.6s linear infinite;
}
@keyframes login-spin { to { transform: rotate(360deg); } }

/* Divider */
.login-modal__divider {
  display: flex;
  align-items: center;
  width: 100%;
  margin: 1.25rem 0;
  gap: 0.75rem;
}
.login-modal__divider::before,
.login-modal__divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--product-border-color, #e5e5e5);
}
.login-modal__divider span {
  font-size: 0.75rem;
  color: var(--footer-text-color, #999);
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Social buttons */
.login-modal__socials {
  display: flex;
  gap: 0.625rem;
  width: 100%;
}

.login-modal__social-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 0.5rem;
  border: 1.5px solid var(--product-border-color, #e5e5e5);
  border-radius: var(--radius-lg, 12px);
  background: var(--bg-primary, #fff);
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--store-text-primary, #1a1a1a);
  cursor: pointer;
  transition: all 0.2s;
}
.login-modal__social-btn:hover { border-color: var(--color-primary); background: var(--bg-secondary, #f9fafb); }

/* OTP boxes */
.login-modal__otp-row {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  margin: 0.5rem 0 1rem;
  direction: ltr; /* OTP digits always LTR */
}

.login-modal__otp-box {
  width: 56px;
  height: 60px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  font-family: monospace;
  border: 1.5px solid var(--product-border-color, #e5e5e5);
  border-radius: var(--radius-lg, 12px);
  outline: none;
  color: var(--store-text-primary, #1a1a1a);
  background: var(--bg-primary, #fff);
  transition: border-color 0.2s, box-shadow 0.2s;
  caret-color: var(--color-primary);
}
.login-modal__otp-box:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb, 79, 70, 229), 0.1);
}
.login-modal__otp-box.input-error { border-color: #ef4444; }

/* Resend */
.login-modal__resend {
  text-align: center;
  margin-top: 1rem;
  min-height: 2rem;
}

.login-modal__resend-timer {
  font-size: 0.8125rem;
  color: var(--footer-text-color, #999);
}

.login-modal__resend-btn {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  padding: 0;
}
.login-modal__resend-btn:hover { opacity: 0.8; }
.login-modal__resend-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Toast */
.login-modal__toast {
  position: absolute;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
  background: var(--color-primary-reverse, #1a1a1a);
  color: #fff;
  padding: 0.625rem 1.25rem;
  border-radius: var(--radius-full, 24px);
  font-size: 0.8125rem;
  font-weight: 500;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 1;
}

/* Transitions */
.login-modal-enter-active { transition: opacity 0.25s ease; }
.login-modal-enter-active .login-modal { animation: modal-slide-up 0.3s ease; }
.login-modal-leave-active { transition: opacity 0.2s ease; }
.login-modal-enter-from, .login-modal-leave-to { opacity: 0; }

@keyframes modal-slide-up {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.toast-fade-enter-active { transition: all 0.3s ease; }
.toast-fade-leave-active { transition: all 0.3s ease; }
.toast-fade-enter-from, .toast-fade-leave-to { opacity: 0; transform: translate(-50%, 8px); }

/* Mobile */
@media (max-width: 480px) {
  .login-modal { padding: 1.5rem 1.25rem; }
  .login-modal__otp-box { width: 48px; height: 52px; font-size: 1.25rem; }
  .login-modal__socials { flex-direction: column; }
}
</style>
