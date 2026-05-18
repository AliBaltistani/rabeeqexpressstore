<template>
  <div class="checkout-page">
    <!-- ORDER HEADER -->
    <div class="checkout-header">
      <div class="checkout-header__inner container">
        <div class="checkout-header__left">
          <div class="checkout-header__logo">
            <img :src="settings.storeSettings.logo || ''" :alt="settings.storeSettings.storeName" class="checkout-header__logo-img" />
            <span class="checkout-header__logo-label">{{ settings.storeSettings.storeName }}</span>
          </div>
          <div class="checkout-header__thumbs">
            <img v-for="item in cart.items" :key="item.id" :src="item.image || ''" :alt="item.productName" class="checkout-header__thumb" />
          </div>
        </div>
        <div class="checkout-header__right">
          <div class="checkout-header__title">{{ $t('checkout.totalOrder') }}</div>
          <div class="checkout-header__total">{{ cart.total?.formatted || '' }}</div>
          <button class="checkout-header__coupon-btn" @click="showCoupon = !showCoupon">{{ $t('checkout.useCoupon') }}</button>
        </div>
      </div>
      <div v-if="showCoupon" class="checkout-coupon container">
        <div class="checkout-coupon__row">
          <input type="text" v-model="couponCode" :placeholder="$t('checkout.enterCouponCode')" class="checkout-coupon__input" />
          <button class="checkout-coupon__apply" @click="applyCoupon" :disabled="couponLoading">{{ $t('checkout.apply') }}</button>
        </div>
        <p v-if="couponMsg" class="checkout-coupon__msg" :class="{ error: couponError }">{{ couponMsg }}</p>
      </div>
      <div class="checkout-header__details-toggle container">
        <button class="checkout-details-btn" @click="showOrderDetails = !showOrderDetails">{{ $t('checkout.orderDetails') }}</button>
      </div>
    </div>

    <!-- STEPS -->
    <div class="container checkout-body">

      <!-- STEP 1: Auth / Guest -->
      <section class="checkout-step" :class="{ completed: currentStep > 1 }">
        <div class="checkout-step__header">
          <div class="checkout-step__num" :class="{ done: currentStep > 1 }">{{ currentStep > 1 ? '✓' : '1' }}</div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title" v-if="currentStep === 1 && authMode !== 'guest'">{{ $t('checkout.loginRegister') }}</h2>
            <h2 class="checkout-step__title" v-else-if="currentStep === 1 && authMode === 'guest'">{{ $t('checkout.welcomeGuest') }}</h2>
            <h2 class="checkout-step__title" v-else>{{ step1Summary }}</h2>
            <p class="checkout-step__subtitle" v-if="currentStep === 1 && authMode !== 'guest'">{{ $t('checkout.loginSubtitle') }}</p>
            <p class="checkout-step__subtitle" v-else-if="currentStep === 1 && authMode === 'guest'">{{ $t('checkout.guestSubtitle') }}</p>
          </div>
          <button v-if="currentStep === 1 && authMode !== 'guest' && settings.storeSettings.features.guestCheckout" class="checkout-step__side-btn" @click="authMode = 'guest'">{{ $t('checkout.purchaseAsGuest') }}</button>
          <button v-if="currentStep > 1" class="checkout-edit-btn" @click="currentStep = 1">{{ $t('checkout.edit') }}</button>
        </div>

        <div v-if="currentStep === 1" class="checkout-step__content">
          <!-- Auth mode tabs -->
          <div v-if="authMode !== 'guest'" class="auth-tabs">
            <button class="auth-tab" :class="{ active: authMode === 'login' }" @click="authMode = 'login'">{{ $t('auth.login') }}</button>
            <button class="auth-tab" :class="{ active: authMode === 'register' }" @click="authMode = 'register'">{{ $t('auth.register') }}</button>
            <button class="auth-tab" :class="{ active: authMode === 'otp' }" @click="authMode = 'otp'">OTP</button>
          </div>

          <!-- Login Form -->
          <form v-if="authMode === 'login'" @submit.prevent="handleLogin" class="checkout-auth-form">
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('auth.email') }} <span class="req">*</span></label>
              <input type="email" v-model="loginForm.email" class="checkout-input" :class="{ 'input-error': errors.loginEmail }" />
              <span v-if="errors.loginEmail" class="field-error">{{ errors.loginEmail }}</span>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('auth.password') }} <span class="req">*</span></label>
              <input type="password" v-model="loginForm.password" class="checkout-input" :class="{ 'input-error': errors.loginPassword }" />
              <span v-if="errors.loginPassword" class="field-error">{{ errors.loginPassword }}</span>
            </div>
            <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
            <button type="submit" class="checkout-btn" :disabled="authLoading">{{ authLoading ? $t('common.loading') : $t('auth.login') }}</button>
            <p class="checkout-alt-text"><router-link to="/forgot-password">{{ $t('auth.forgotPassword') }}</router-link></p>
          </form>

          <!-- Register Form -->
          <form v-if="authMode === 'register'" @submit.prevent="handleRegister" class="checkout-auth-form">
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('auth.name') }} <span class="req">*</span></label>
              <input type="text" v-model="registerForm.name" class="checkout-input" :class="{ 'input-error': errors.regName }" />
              <span v-if="errors.regName" class="field-error">{{ errors.regName }}</span>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('auth.email') }} <span class="req">*</span></label>
              <input type="email" v-model="registerForm.email" class="checkout-input" :class="{ 'input-error': errors.regEmail }" />
              <span v-if="errors.regEmail" class="field-error">{{ errors.regEmail }}</span>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('auth.password') }} <span class="req">*</span></label>
                <input type="password" v-model="registerForm.password" class="checkout-input" :class="{ 'input-error': errors.regPassword }" />
                <span v-if="errors.regPassword" class="field-error">{{ errors.regPassword }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('auth.confirmPassword') }} <span class="req">*</span></label>
                <input type="password" v-model="registerForm.password_confirmation" class="checkout-input" :class="{ 'input-error': errors.regConfirm }" />
                <span v-if="errors.regConfirm" class="field-error">{{ errors.regConfirm }}</span>
              </div>
            </div>
            <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
            <button type="submit" class="checkout-btn" :disabled="authLoading">{{ authLoading ? $t('common.loading') : $t('auth.register') }}</button>
          </form>

          <!-- OTP Login Form -->
          <div v-if="authMode === 'otp'" class="checkout-auth-form">
            <!-- Step A: Email -->
            <template v-if="otpStep === 'email'">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('loginModal.emailLabel') }} <span class="req">*</span></label>
                <input type="email" v-model="otpEmail" class="checkout-input" :class="{ 'input-error': errors.otpEmail }" :placeholder="$t('loginModal.emailPlaceholder')" @keydown.enter.prevent="handleSendCheckoutOtp" />
                <span v-if="errors.otpEmail" class="field-error">{{ errors.otpEmail }}</span>
              </div>
              <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
              <button class="checkout-btn" :disabled="authLoading" @click="handleSendCheckoutOtp">{{ authLoading ? $t('common.loading') : $t('loginModal.continue') }}</button>
            </template>
            <!-- Step B: OTP Code -->
            <template v-else>
              <p class="checkout-step__subtitle" style="margin:0 0 1rem;text-align:center;">{{ $t('loginModal.otpSubtitle', { email: otpEmail }) }}</p>
              <div class="checkout-otp-row">
                <input v-for="(_, idx) in 4" :key="idx" :ref="(el) => { if (el) checkoutOtpRefs[idx] = el as HTMLInputElement }" type="text" inputmode="numeric" maxlength="1" class="checkout-otp-box" :class="{ 'input-error': errors.otpCode }" :value="otpCodeDigits[idx]" @input="handleCheckoutOtpInput(idx, $event)" @keydown.backspace="handleCheckoutOtpBackspace(idx, $event)" @paste="handleCheckoutOtpPaste($event)" />
              </div>
              <span v-if="errors.otpCode" class="field-error" style="text-align:center;display:block;">{{ errors.otpCode }}</span>
              <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
              <button class="checkout-btn" :disabled="authLoading || otpCodeDigits.join('').length < 4" @click="handleVerifyCheckoutOtp">{{ authLoading ? $t('common.loading') : $t('loginModal.verify') }}</button>
              <div style="text-align:center;margin-top:0.75rem;">
                <span v-if="otpResendCooldown > 0" class="checkout-alt-text">{{ $t('loginModal.resendIn', { seconds: otpResendCooldown }) }}</span>
                <button v-else class="checkout-step__side-btn" @click="handleSendCheckoutOtp" :disabled="authLoading">{{ $t('loginModal.resendCode') }}</button>
              </div>
              <button class="checkout-step__side-btn" style="margin-top:0.5rem;" @click="otpStep = 'email'">{{ $t('loginModal.back') }}</button>
            </template>
          </div>

          <!-- Guest Form -->
          <form v-if="authMode === 'guest'" @submit.prevent="handleGuest" class="checkout-auth-form">
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.firstName') }} <span class="req">*</span></label>
                <input type="text" v-model="guestForm.firstName" class="checkout-input" :class="{ 'input-error': errors.gFirstName }" />
                <span v-if="errors.gFirstName" class="field-error">{{ errors.gFirstName }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.lastName') }} <span class="req">*</span></label>
                <input type="text" v-model="guestForm.lastName" class="checkout-input" :class="{ 'input-error': errors.gLastName }" />
                <span v-if="errors.gLastName" class="field-error">{{ errors.gLastName }}</span>
              </div>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.email') }} <span class="req">*</span></label>
                <input type="email" v-model="guestForm.email" class="checkout-input" :class="{ 'input-error': errors.gEmail }" />
                <span v-if="errors.gEmail" class="field-error">{{ errors.gEmail }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
                <div class="checkout-phone-row">
                  <select v-model="guestCountryCode" class="checkout-country-code">
                    <option v-for="cc in countryCodes" :key="cc.code" :value="cc.code">{{ cc.flag }} {{ cc.code }}</option>
                  </select>
                  <input type="tel" v-model="guestForm.phone" class="checkout-input checkout-input--phone" :class="{ 'input-error': errors.gPhone }" />
                </div>
                <span v-if="errors.gPhone" class="field-error">{{ errors.gPhone }}</span>
              </div>
            </div>
            <button type="submit" class="checkout-btn">{{ $t('checkout.continueAsGuest') }}</button>
            <p class="checkout-alt-text">{{ $t('checkout.alreadyHaveAccount') }} <a href="#" @click.prevent="authMode = 'login'">{{ $t('auth.login') }}</a></p>
          </form>
        </div>
      </section>

      <div class="checkout-divider"></div>

      <!-- STEP 2: Shipping Address -->
      <section class="checkout-step" :class="{ locked: currentStep < 2, completed: currentStep > 2 }">
        <div class="checkout-step__header">
          <div class="checkout-step__num" :class="{ done: currentStep > 2 }">{{ currentStep > 2 ? '✓' : '2' }}</div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">{{ $t('checkout.shippingAddress') }}</h2>
            <p class="checkout-step__subtitle" v-if="currentStep > 2">{{ addressForm.city }}, {{ addressForm.street }}</p>
            <p class="checkout-step__subtitle" v-else>{{ $t('checkout.ensureAddress') }}</p>
          </div>
          <button v-if="currentStep > 2" class="checkout-edit-btn" @click="currentStep = 2">{{ $t('checkout.edit') }}</button>
        </div>
        <div v-if="currentStep === 2" class="checkout-step__content">
          <form @submit.prevent="submitAddress">
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.firstName') }} <span class="req">*</span></label>
                <input type="text" v-model="addressForm.firstName" class="checkout-input" :class="{ 'input-error': errors.addrFirstName }" />
                <span v-if="errors.addrFirstName" class="field-error">{{ errors.addrFirstName }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.lastName') }} <span class="req">*</span></label>
                <input type="text" v-model="addressForm.lastName" class="checkout-input" :class="{ 'input-error': errors.addrLastName }" />
                <span v-if="errors.addrLastName" class="field-error">{{ errors.addrLastName }}</span>
              </div>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
              <div class="checkout-phone-row">
                <select v-model="addrCountryCode" class="checkout-country-code">
                  <option v-for="cc in countryCodes" :key="cc.code" :value="cc.code">{{ cc.flag }} {{ cc.code }}</option>
                </select>
                <input type="tel" v-model="addressForm.phone" class="checkout-input checkout-input--phone" :class="{ 'input-error': errors.addrPhone }" />
              </div>
              <span v-if="errors.addrPhone" class="field-error">{{ errors.addrPhone }}</span>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.country') }} <span class="req">*</span></label>
                <input type="text" v-model="addressForm.country" class="checkout-input" :class="{ 'input-error': errors.addrCountry }" />
                <span v-if="errors.addrCountry" class="field-error">{{ errors.addrCountry }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.region') }}</label>
                <input type="text" v-model="addressForm.state" class="checkout-input" />
              </div>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.city') }} <span class="req">*</span></label>
                <input type="text" v-model="addressForm.city" class="checkout-input" :class="{ 'input-error': errors.addrCity }" />
                <span v-if="errors.addrCity" class="field-error">{{ errors.addrCity }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.district') }}</label>
                <input type="text" v-model="addressForm.district" class="checkout-input" />
              </div>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.street') }} <span class="req">*</span></label>
                <input type="text" v-model="addressForm.street" class="checkout-input" :class="{ 'input-error': errors.addrStreet }" />
                <span v-if="errors.addrStreet" class="field-error">{{ errors.addrStreet }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.postalCode') }}</label>
                <input type="text" v-model="addressForm.postalCode" class="checkout-input" />
              </div>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('checkout.buildingNo') }}</label>
              <input type="text" v-model="addressForm.buildingNo" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">{{ $t('checkout.buildingDesc') || 'Building Description' }}</label>
              <textarea v-model="addressForm.buildingDesc" class="checkout-input checkout-textarea" rows="2" :placeholder="$t('checkout.buildingDescPlaceholder') || 'e.g. Villa, Apartment 3B, near the mosque...'"></textarea>
            </div>

            <!-- Deliver to someone else -->
            <label class="checkout-checkbox">
              <input type="checkbox" v-model="deliverToOther" />
              <span>{{ $t('checkout.deliverToOther') || 'Deliver order to someone else?' }}</span>
            </label>
            <div v-if="deliverToOther" class="checkout-other-recipient">
              <div class="checkout-form-grid">
                <div class="checkout-field">
                  <label class="checkout-label">{{ $t('checkout.recipientName') || "Recipient's Name" }} <span class="req">*</span></label>
                  <input type="text" v-model="recipientForm.name" class="checkout-input" />
                </div>
                <div class="checkout-field">
                  <label class="checkout-label">{{ $t('checkout.recipientPhone') || "Recipient's Phone" }} <span class="req">*</span></label>
                  <div class="checkout-phone-row">
                    <select v-model="recipientCountryCode" class="checkout-country-code">
                      <option v-for="cc in countryCodes" :key="cc.code" :value="cc.code">{{ cc.flag }} {{ cc.code }}</option>
                    </select>
                    <input type="tel" v-model="recipientForm.phone" class="checkout-input checkout-input--phone" />
                  </div>
                </div>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.recipientEmail') || "Recipient's Email" }} ({{ $t('common.optional') || 'Optional' }})</label>
                <input type="email" v-model="recipientForm.email" class="checkout-input" />
              </div>
            </div>

            <!-- SMS opt-in -->
            <label class="checkout-checkbox">
              <input type="checkbox" v-model="smsUpdates" />
              <span>{{ $t('checkout.smsUpdates') || 'Get order updates via SMS' }}</span>
            </label>

            <p v-if="addressError" class="auth-error-msg">{{ addressError }}</p>
            <button type="submit" class="checkout-btn" :disabled="shippingLoading">{{ shippingLoading ? $t('common.loading') : $t('checkout.save') }}</button>
          </form>
        </div>
      </section>

      <div class="checkout-divider"></div>

      <!-- STEP 3: Shipping Company -->
      <section class="checkout-step" :class="{ locked: currentStep < 3, completed: currentStep > 3 }">
        <div class="checkout-step__header">
          <div class="checkout-step__num" :class="{ done: currentStep > 3 }">{{ currentStep > 3 ? '✓' : '3' }}</div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">{{ $t('checkout.shippingCompany') }}</h2>
            <p class="checkout-step__subtitle" v-if="currentStep > 3">{{ selectedShipping?.name }}</p>
            <p class="checkout-step__subtitle" v-else>{{ $t('checkout.selectShipping') }}</p>
          </div>
          <button v-if="currentStep > 3" class="checkout-edit-btn" @click="currentStep = 3">{{ $t('checkout.edit') }}</button>
        </div>
        <div v-if="currentStep === 3" class="checkout-step__content">
          <div v-if="shippingLoading" class="empty-shipping">
            <p>{{ $t('common.loading') }}...</p>
          </div>
          <div v-else-if="shippingRatesFetched && shippingOptions.length === 0" class="empty-shipping">
            <p>{{ $t('checkout.noShippingRates') }}</p>
            <button class="checkout-btn" @click="currentStep = 4">{{ $t('search.next') || 'Next' }} →</button>
          </div>
          <div v-else class="checkout-shipping-options">
            <label v-for="opt in shippingOptions" :key="opt.id" class="checkout-shipping-card" :class="{ selected: selectedShippingId === opt.id }">
              <input type="radio" name="shipping" :value="opt.id" v-model="selectedShippingId" class="checkout-radio" />
              <div class="checkout-shipping-info">
                <span class="checkout-shipping-name">{{ opt.name }}</span>
                <span class="checkout-shipping-time" v-if="opt.time">{{ opt.time }}</span>
              </div>
              <span class="checkout-shipping-price">{{ typeof opt.price === 'object' ? (opt.price as any).formatted : opt.price }}</span>
            </label>
          </div>
          <span v-if="errors.shipping" class="field-error">{{ errors.shipping }}</span>
          <button v-if="shippingOptions.length > 0" class="checkout-btn" @click="submitShipping">{{ $t('checkout.confirmShipping') }}</button>
        </div>
      </section>

      <div class="checkout-divider"></div>

      <!-- STEP 4: Payment -->
      <section class="checkout-step" :class="{ locked: currentStep < 4 }">
        <div class="checkout-step__header">
          <div class="checkout-step__num">4</div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">{{ $t('checkout.payment') }}</h2>
            <p class="checkout-step__subtitle">{{ selectedPaymentName }}</p>
          </div>
        </div>
        <div v-if="currentStep === 4" class="checkout-step__content">
          <!-- Payment Methods from admin -->
          <div class="checkout-payment-methods">
            <label v-for="pm in paymentMethods" :key="pm.id" class="checkout-payment-card" :class="{ selected: selectedPayment === pm.id }">
              <input type="radio" name="payment" :value="pm.id" v-model="selectedPayment" class="checkout-radio" />
              <span class="checkout-payment-name">{{ pm.name }}</span>
              <span v-if="pm.fee" class="checkout-payment-fee">+{{ pm.fee }}</span>
            </label>
          </div>
          <span v-if="errors.payment" class="field-error">{{ errors.payment }}</span>

          <!-- Stripe Card Details -->
          <div v-if="selectedPayment === 'stripe'" class="checkout-card-form">
            <p class="card-form-note">{{ $t('checkout.cardDetails') }}</p>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">Card Number <span class="req">*</span></label>
                <input type="text" v-model="cardNumber" placeholder="4242 4242 4242 4242" class="checkout-input" maxlength="19" />
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.cardHolderName') }} <span class="req">*</span></label>
                <input type="text" v-model="cardName" class="checkout-input" />
              </div>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">MM/YY <span class="req">*</span></label>
                <input type="text" v-model="cardExpiry" placeholder="12/28" class="checkout-input" maxlength="5" />
              </div>
              <div class="checkout-field">
                <label class="checkout-label">CVV <span class="req">*</span></label>
                <input type="text" v-model="cardCvv" placeholder="123" class="checkout-input" maxlength="4" />
              </div>
            </div>
          </div>

          <!-- Bank Transfer Info -->
          <div v-if="selectedPayment === 'bank_transfer'" class="checkout-card-form">
            <p class="card-form-note">{{ $t('checkout.bankTransferNote') || 'You will receive bank details after placing your order. Your order will be confirmed once payment is received.' }}</p>
          </div>

          <!-- T&C -->
          <label class="checkout-checkbox checkout-checkbox--terms">
            <input type="checkbox" v-model="agreeTerms" />
            <span>{{ $t('checkout.agreeTerms') }}</span>
          </label>
          <span v-if="errors.terms" class="field-error">{{ errors.terms }}</span>

          <!-- Order Summary -->
          <div class="checkout-order-summary">
            <div class="summary-row"><span>{{ $t('cart.totalProductsCost') }}</span><span>{{ cart.subtotal?.formatted }}</span></div>
            <div v-if="cart.discount?.raw" class="summary-row discount"><span>{{ $t('checkout.discount') || 'Discount' }}</span><span>-{{ cart.discount?.formatted }}</span></div>
            <div class="summary-row"><span>{{ $t('checkout.shippingCompany') }}</span><span>{{ selectedShipping ? (typeof selectedShipping.price === 'object' ? (selectedShipping.price as any).formatted : selectedShipping.price) : '—' }}</span></div>
            <div class="summary-row total"><span>{{ $t('checkout.totalOrder') }}</span><span>{{ cart.total?.formatted }}</span></div>
          </div>

          <button class="checkout-btn checkout-btn--pay" @click="confirmPayment" :disabled="orderLoading">
            {{ orderLoading ? $t('common.loading') : $t('checkout.confirmPayment') }}
          </button>
          <p v-if="orderError" class="auth-error-msg">{{ orderError }}</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cartStore'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { fetchShippingRates, placeOrder } from '@/api/services'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const settings = useSettingsStore()
const { t } = useI18n()

// ── State ──
const currentStep = ref(1)
const showCoupon = ref(false)
const showOrderDetails = ref(false)
const couponCode = ref('')
const couponLoading = ref(false)
const couponMsg = ref('')
const couponError = ref(false)

const authMode = ref<'login' | 'register' | 'guest' | 'otp'>('login')
const authLoading = ref(false)
const authError = ref('')

const errors = reactive<Record<string, string>>({})

// Login
const loginForm = ref({ email: '', password: '' })

// Register
const registerForm = ref({ name: '', email: '', password: '', password_confirmation: '' })

// Guest
const guestForm = ref({ firstName: '', lastName: '', email: '', phone: '' })

// OTP checkout
const otpEmail = ref('')
const otpStep = ref<'email' | 'code'>('email')
const otpCodeDigits = ref(['', '', '', ''])
const checkoutOtpRefs = ref<HTMLInputElement[]>([])
const otpResendCooldown = ref(0)
let otpCooldownTimer: ReturnType<typeof setInterval> | null = null

// Address
const addressForm = ref({ firstName: '', lastName: '', phone: '', country: '', state: '', city: '', district: '', street: '', postalCode: '', buildingNo: '', buildingDesc: '' })
const addressError = ref('')
const shippingLoading = ref(false)
const shippingRatesFetched = ref(false)

// Country codes
const countryCodes = [
  { code: '+966', flag: '🇸🇦' },
  { code: '+971', flag: '🇦🇪' },
  { code: '+20', flag: '🇪🇬' },
  { code: '+962', flag: '🇯🇴' },
  { code: '+965', flag: '🇰🇼' },
  { code: '+973', flag: '🇧🇭' },
  { code: '+968', flag: '🇴🇲' },
  { code: '+974', flag: '🇶🇦' },
  { code: '+1', flag: '🇺🇸' },
  { code: '+44', flag: '🇬🇧' },
  { code: '+91', flag: '🇮🇳' },
  { code: '+92', flag: '🇵🇰' },
  { code: '+90', flag: '🇹🇷' },
  { code: '+33', flag: '🇫🇷' },
  { code: '+49', flag: '🇩🇪' },
]
const guestCountryCode = ref('+966')
const addrCountryCode = ref('+966')
const recipientCountryCode = ref('+966')

// Deliver to someone else
const deliverToOther = ref(false)
const recipientForm = ref({ name: '', phone: '', email: '' })
const smsUpdates = ref(false)

// Shipping
const shippingOptions = ref<any[]>([])
const selectedShippingId = ref<number | null>(null)
const selectedShipping = computed(() => shippingOptions.value.find((s: any) => s.id === selectedShippingId.value))

// Payment
const selectedPayment = ref('')
const selectedPaymentName = computed(() => {
  const pm = paymentMethods.value.find((p: any) => p.id === selectedPayment.value)
  return pm?.name || ''
})
const paymentMethods = computed(() => settings.storeSettings.paymentMethods || [])
const cardNumber = ref('')
const cardName = ref('')
const cardExpiry = ref('')
const cardCvv = ref('')

watch(cardNumber, (val) => {
  const cleaned = val.replace(/\D/g, '')
  let formatted = cleaned.replace(/(.{4})/g, '$1 ').trim()
  if (cleaned.length > 16) formatted = formatted.substring(0, 19)
  if (cardNumber.value !== formatted) cardNumber.value = formatted
})

watch(cardExpiry, (val) => {
  let cleaned = val.replace(/\D/g, '')
  let formatted = cleaned
  if (cleaned.length >= 2) {
    if (parseInt(cleaned.substring(0, 2)) > 12) cleaned = '12' + cleaned.substring(2)
    formatted = cleaned.substring(0, 2) + '/' + cleaned.substring(2, 4)
  }
  if (cardExpiry.value !== formatted) cardExpiry.value = formatted
})

watch(cardCvv, (val) => {
  const cleaned = val.replace(/\D/g, '').substring(0, 4)
  if (cardCvv.value !== cleaned) cardCvv.value = cleaned
})
const agreeTerms = ref(false)
const orderLoading = ref(false)
const orderError = ref('')

// Step 1 summary
const step1Summary = computed(() => {
  if (auth.isAuthenticated && auth.user) return `${auth.user.name} (${auth.user.email})`
  if (authMode.value === 'guest') return `${guestForm.value.firstName} ${guestForm.value.lastName} (${guestForm.value.email})`
  return ''
})

// ── Helpers ──
function clearErrors() { Object.keys(errors).forEach(k => delete errors[k]) }

function isEmail(v: string) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) }

// ── Step 1 Actions ──
async function handleLogin() {
  clearErrors()
  authError.value = ''
  if (!loginForm.value.email.trim()) errors.loginEmail = t('checkout.required') || 'Required'
  if (!loginForm.value.password) errors.loginPassword = t('checkout.required') || 'Required'
  if (!isEmail(loginForm.value.email)) errors.loginEmail = t('checkout.invalidEmail') || 'Invalid email'
  if (Object.keys(errors).length) return

  authLoading.value = true
  try {
    await auth.login(loginForm.value.email, loginForm.value.password)
    // Pre-fill address from user
    if (auth.user) {
      const parts = auth.user.name?.split(' ') || []
      addressForm.value.firstName = parts[0] || ''
      addressForm.value.lastName = parts.slice(1).join(' ') || ''
      addressForm.value.phone = auth.user.phone || ''
    }
    currentStep.value = 2
  } catch (e: any) {
    authError.value = e.response?.data?.message || t('common.error')
  } finally {
    authLoading.value = false
  }
}

async function handleRegister() {
  clearErrors()
  authError.value = ''
  if (!registerForm.value.name.trim()) errors.regName = t('checkout.required') || 'Required'
  if (!registerForm.value.email.trim()) errors.regEmail = t('checkout.required') || 'Required'
  else if (!isEmail(registerForm.value.email)) errors.regEmail = t('checkout.invalidEmail') || 'Invalid email'
  if (!registerForm.value.password) errors.regPassword = t('checkout.required') || 'Required'
  else if (registerForm.value.password.length < 8) errors.regPassword = t('checkout.minPassword') || 'Min 8 characters'
  if (registerForm.value.password !== registerForm.value.password_confirmation) errors.regConfirm = t('checkout.passwordMismatch') || 'Passwords do not match'
  if (Object.keys(errors).length) return

  authLoading.value = true
  try {
    await auth.register(registerForm.value)
    if (auth.user) {
      const parts = auth.user.name?.split(' ') || []
      addressForm.value.firstName = parts[0] || ''
      addressForm.value.lastName = parts.slice(1).join(' ') || ''
    }
    currentStep.value = 2
  } catch (e: any) {
    authError.value = e.response?.data?.message || t('common.error')
  } finally {
    authLoading.value = false
  }
}

// ── OTP at Checkout ──
async function handleSendCheckoutOtp() {
  clearErrors()
  authError.value = ''
  if (!otpEmail.value.trim()) { errors.otpEmail = t('checkout.required'); return }
  if (!isEmail(otpEmail.value)) { errors.otpEmail = t('checkout.invalidEmail'); return }

  authLoading.value = true
  const result = await auth.sendOtp(otpEmail.value)
  authLoading.value = false

  if (result.success) {
    otpStep.value = 'code'
    otpCodeDigits.value = ['', '', '', '']
    startOtpCooldown((result as any).cooldown || 60)
    nextTick(() => checkoutOtpRefs.value[0]?.focus())
  } else {
    authError.value = (result as any).message || t('common.error')
  }
}

async function handleVerifyCheckoutOtp() {
  clearErrors()
  authError.value = ''
  const code = otpCodeDigits.value.join('')
  if (code.length < 4) { errors.otpCode = t('loginModal.invalidOtp'); return }

  authLoading.value = true
  const result = await auth.verifyOtp(otpEmail.value, code)
  authLoading.value = false

  if (result.success) {
    if (auth.user) {
      const parts = auth.user.name?.split(' ') || []
      addressForm.value.firstName = parts[0] || ''
      addressForm.value.lastName = parts.slice(1).join(' ') || ''
      addressForm.value.phone = auth.user.phone || ''
    }
    currentStep.value = 2
  } else {
    authError.value = (result as any).message || t('loginModal.invalidOtp')
    otpCodeDigits.value = ['', '', '', '']
    nextTick(() => checkoutOtpRefs.value[0]?.focus())
  }
}

function handleCheckoutOtpInput(idx: number, event: Event) {
  const target = event.target as HTMLInputElement
  const val = target.value.replace(/\D/g, '')
  otpCodeDigits.value[idx] = val.slice(-1)
  if (val && idx < 3) nextTick(() => checkoutOtpRefs.value[idx + 1]?.focus())
  if (otpCodeDigits.value.join('').length === 4) handleVerifyCheckoutOtp()
}

function handleCheckoutOtpBackspace(idx: number, event: KeyboardEvent) {
  if (!otpCodeDigits.value[idx] && idx > 0) {
    event.preventDefault()
    otpCodeDigits.value[idx - 1] = ''
    nextTick(() => checkoutOtpRefs.value[idx - 1]?.focus())
  }
}

function handleCheckoutOtpPaste(event: ClipboardEvent) {
  event.preventDefault()
  const paste = event.clipboardData?.getData('text')?.replace(/\D/g, '') || ''
  for (let i = 0; i < 4; i++) otpCodeDigits.value[i] = paste[i] || ''
  if (paste.length >= 4) nextTick(() => handleVerifyCheckoutOtp())
}

function startOtpCooldown(seconds: number) {
  otpResendCooldown.value = seconds
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
  otpCooldownTimer = setInterval(() => {
    otpResendCooldown.value--
    if (otpResendCooldown.value <= 0 && otpCooldownTimer) {
      clearInterval(otpCooldownTimer)
      otpCooldownTimer = null
    }
  }, 1000)
}

function handleGuest() {
  clearErrors()
  if (!guestForm.value.firstName.trim()) errors.gFirstName = t('checkout.required') || 'Required'
  if (!guestForm.value.lastName.trim()) errors.gLastName = t('checkout.required') || 'Required'
  if (!guestForm.value.email.trim()) errors.gEmail = t('checkout.required') || 'Required'
  else if (!isEmail(guestForm.value.email)) errors.gEmail = t('checkout.invalidEmail') || 'Invalid email'
  if (!guestForm.value.phone.trim()) errors.gPhone = t('checkout.required') || 'Required'
  if (Object.keys(errors).length) return

  // Pre-fill address name/phone from guest
  addressForm.value.firstName = guestForm.value.firstName
  addressForm.value.lastName = guestForm.value.lastName
  addressForm.value.phone = guestForm.value.phone
  currentStep.value = 2
}

// ── Step 2: Address ──
async function submitAddress() {
  clearErrors()
  addressError.value = ''
  if (!addressForm.value.firstName.trim()) errors.addrFirstName = t('checkout.required') || 'Required'
  if (!addressForm.value.lastName.trim()) errors.addrLastName = t('checkout.required') || 'Required'
  if (!addressForm.value.phone.trim()) errors.addrPhone = t('checkout.required') || 'Required'
  if (!addressForm.value.country.trim()) errors.addrCountry = t('checkout.required') || 'Required'
  if (!addressForm.value.city.trim()) errors.addrCity = t('checkout.required') || 'Required'
  if (!addressForm.value.street.trim()) errors.addrStreet = t('checkout.required') || 'Required'
  if (Object.keys(errors).length) return

  shippingLoading.value = true
  shippingRatesFetched.value = false
  try {
    const rates = await fetchShippingRates({ country: addressForm.value.country, state: addressForm.value.state })
    shippingRatesFetched.value = true
    if (rates && rates.length) {
      shippingOptions.value = rates
      selectedShippingId.value = rates[0].id
    } else {
      shippingOptions.value = []
    }
    currentStep.value = 3
  } catch (e) {
    shippingRatesFetched.value = true
    addressError.value = t('checkout.shippingRatesError') || 'Could not fetch shipping rates'
  } finally {
    shippingLoading.value = false
  }
}

// ── Step 3: Shipping ──
function submitShipping() {
  clearErrors()
  if (!selectedShippingId.value) { errors.shipping = t('checkout.required') || 'Please select a shipping option'; return }
  currentStep.value = 4
}

// ── Coupon ──
async function applyCoupon() {
  if (!couponCode.value.trim()) return
  couponLoading.value = true
  couponMsg.value = ''
  couponError.value = false
  const result = await cart.applyCoupon(couponCode.value.trim())
  couponLoading.value = false
  if (result.success) {
    couponMsg.value = t('checkout.couponApplied')
    showCoupon.value = false
  } else {
    couponError.value = true
    couponMsg.value = result.message || t('common.error')
  }
}

// ── Step 4: Payment ──
async function confirmPayment() {
  clearErrors()
  orderError.value = ''
  if (!selectedPayment.value) { errors.payment = t('checkout.selectPayment') || 'Select a payment method'; return }
  if (!agreeTerms.value) { errors.terms = t('checkout.agreeTermsRequired') || 'You must agree to the terms'; return }

  if (selectedPayment.value === 'stripe') {
    const rawCard = cardNumber.value.replace(/\s/g, '')
    if (rawCard.length < 15 || rawCard.length > 16) {
      orderError.value = t('checkout.invalidCardNumber') || 'Invalid card number format'
      return
    }
    if (!cardName.value.trim()) {
      orderError.value = t('checkout.invalidCardName') || 'Please provide cardholder name'
      return
    }
    
    if (cardExpiry.value.length < 5) {
      orderError.value = t('checkout.invalidExpiry') || 'Invalid expiry date (MM/YY)'
      return
    }
    const [month, year] = cardExpiry.value.split('/')
    const currentYear = parseInt(new Date().getFullYear().toString().substring(2, 4))
    const currentMonth = new Date().getMonth() + 1
    if (!month || !year || parseInt(month) < 1 || parseInt(month) > 12) {
      orderError.value = t('checkout.invalidExpiry') || 'Invalid expiry date (MM/YY)'
      return
    }
    if (parseInt(year) < currentYear || (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
      orderError.value = t('checkout.expiredCard') || 'This card appears to be expired'
      return
    }
    if (cardCvv.value.length < 3 || cardCvv.value.length > 4) {
      orderError.value = t('checkout.invalidCvv') || 'Invalid CVV (3 or 4 digits)'
      return
    }
  }

  orderLoading.value = true
  try {
    const isGuestMode = authMode.value === 'guest' && !auth.isAuthenticated
    const payload: any = {
      shippingAddress: {
        firstName: addressForm.value.firstName,
        lastName: addressForm.value.lastName,
        phone: addressForm.value.phone,
        addressLine1: addressForm.value.street,
        city: addressForm.value.city,
        country: addressForm.value.country,
        state: addressForm.value.state,
        postalCode: addressForm.value.postalCode,
      },
      paymentMethod: selectedPayment.value,
      shippingRateId: selectedShippingId.value,
      couponCode: couponCode.value || cart.couponCode || undefined,
      notes: '',
      currency: settings.currentCurrencyCode,
    }
    if (isGuestMode) {
      payload.guestEmail = guestForm.value.email
      payload.guestName = `${guestForm.value.firstName} ${guestForm.value.lastName}`
      payload.guestPhone = guestForm.value.phone
    }

    const response = await placeOrder(payload)
    cart.clearCart()
    router.push('/checkout/success/' + response.orderNumber)
  } catch (e: any) {
    orderError.value = e.response?.data?.message || t('common.error')
  } finally {
    orderLoading.value = false
  }
}

onUnmounted(() => {
  if (otpCooldownTimer) {
    clearInterval(otpCooldownTimer)
  }
})

// ── Init ──
onMounted(() => {
  if (auth.isAuthenticated) {
    authMode.value = 'login'
    currentStep.value = 2
    if (auth.user) {
      const parts = auth.user.name?.split(' ') || []
      addressForm.value.firstName = parts[0] || ''
      addressForm.value.lastName = parts.slice(1).join(' ') || ''
      addressForm.value.phone = auth.user.phone || ''
    }
  }
  if (paymentMethods.value.length) {
    selectedPayment.value = paymentMethods.value[0].id
  }
})
</script>

<style scoped>
.checkout-page { background: var(--bg-secondary, #f5f5f5); min-height: 100vh; padding-bottom: var(--space-2xl); }
.container { max-width: 900px; margin: 0 auto; padding: 0 var(--container-padding, 1rem); }

/* Header */
.checkout-header { background: var(--bg-primary, #fff); border-bottom: 1px solid var(--product-border-color, #eee); padding: var(--space-lg) 0 0; }
.checkout-header__inner { display: flex; justify-content: space-between; align-items: flex-start; }
.checkout-header__left { display: flex; align-items: center; gap: var(--space-lg); }
.checkout-header__logo { display: flex; flex-direction: column; align-items: center; gap: var(--space-xs); }
.checkout-header__logo-img { width: 56px; height: 56px; border-radius: var(--radius-full); object-fit: cover; border: 2px solid var(--product-border-color, #eee); }
.checkout-header__logo-label { font-size: 0.625rem; color: var(--footer-text-color, #374151); }
.checkout-header__thumbs { display: flex; gap: var(--space-xs); }
.checkout-header__thumb { width: 32px; height: 32px; object-fit: contain; border-radius: var(--radius-sm); }
.checkout-header__right { text-align: right; }
.checkout-header__title { font-size: 1.125rem; font-weight: 700; color: var(--store-text-primary); }
.checkout-header__total { font-size: 1.5rem; font-weight: 700; color: var(--store-text-primary); }
.checkout-header__coupon-btn { background: none; border: none; color: var(--color-primary); font-size: 0.8125rem; font-weight: 500; cursor: pointer; text-decoration: underline; margin-top: var(--space-xs); }
.checkout-coupon { padding: var(--space-lg) 0; }
.checkout-coupon__row { display: flex; gap: var(--space-sm); }
.checkout-coupon__input { flex: 1; padding: 0.625rem 0.875rem; border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-md); font-size: 0.875rem; outline: none; }
.checkout-coupon__input:focus { border-color: var(--color-primary); }
.checkout-coupon__apply { padding: 0.625rem var(--space-lg); background: var(--color-primary-reverse, #060606); color: var(--bg-primary, #fff); border: none; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 600; cursor: pointer; }
.checkout-coupon__apply:disabled { opacity: 0.6; cursor: not-allowed; }
.checkout-coupon__msg { font-size: 0.8125rem; margin: var(--space-sm) 0 0; color: var(--color-primary); }
.checkout-coupon__msg.error { color: var(--promotion-bg, #ff0000); }
.checkout-header__details-toggle { display: flex; justify-content: center; padding: var(--space-lg) 0; }
.checkout-details-btn { padding: 0.375rem var(--space-lg); border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-full); background: var(--bg-primary, #fff); font-size: 0.8125rem; color: var(--store-text-primary); cursor: pointer; transition: all var(--transition-normal); }
.checkout-details-btn:hover { border-color: var(--color-primary); }

/* Phone with country code */
.checkout-phone-row { display: flex; gap: 0; }
.checkout-country-code { width: auto; min-width: 100px; padding: 0.625rem 0.5rem; border: 1px solid var(--product-border-color, #eee); border-right: none; border-radius: var(--radius-md) 0 0 var(--radius-md); font-size: 0.8125rem; color: var(--store-text-primary); background: var(--bg-secondary, #f5f5f5); outline: none; cursor: pointer; }
html[dir="rtl"] .checkout-country-code { border-right: 1px solid var(--product-border-color, #eee); border-left: none; border-radius: 0 var(--radius-md) var(--radius-md) 0; }
.checkout-input--phone { border-radius: 0 var(--radius-md) var(--radius-md) 0; flex: 1; }
html[dir="rtl"] .checkout-input--phone { border-radius: var(--radius-md) 0 0 var(--radius-md); }
.checkout-textarea { resize: vertical; font-family: inherit; min-height: 56px; }
.checkout-other-recipient { padding: var(--space-md) var(--space-lg); border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-xl); margin: var(--space-sm) 0 var(--space-md); background: var(--bg-secondary, #f5f5f5); }

/* Steps */
.checkout-body { padding-top: 0; }
.checkout-step { background: var(--bg-primary, #fff); border-radius: var(--radius-xl); padding: var(--space-lg); transition: opacity var(--transition-normal); }
.checkout-step.locked { opacity: 0.45; pointer-events: none; }
.checkout-step.completed { opacity: 1; }
.checkout-step__header { display: flex; align-items: flex-start; gap: var(--space-lg); }
.checkout-step__num { width: 32px; height: 32px; border-radius: var(--radius-full); background: var(--bg-secondary, #f5f5f5); color: var(--footer-text-color, #374151); display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 700; flex-shrink: 0; }
.checkout-step__num.done { background: var(--color-primary); color: var(--bg-primary, #fff); }
.checkout-step__title-wrap { flex: 1; }
.checkout-step__title { font-size: 1.125rem; font-weight: 700; color: var(--store-text-primary); margin: 0; line-height: 1.3; }
.checkout-step__subtitle { font-size: 0.8125rem; color: var(--footer-text-color, #374151); margin: 0.125rem 0 0; }
.checkout-step__side-btn { background: none; border: none; font-size: 0.875rem; font-weight: 500; color: var(--color-primary); cursor: pointer; text-decoration: underline; white-space: nowrap; }
.checkout-edit-btn { display: flex; align-items: center; gap: var(--space-xs); padding: 0.375rem var(--space-md); border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-md); background: var(--bg-primary, #fff); font-size: 0.8125rem; color: var(--store-text-primary); cursor: pointer; white-space: nowrap; }
.checkout-edit-btn:hover { border-color: var(--color-primary); }
.checkout-step__content { margin-top: var(--space-lg); }
.checkout-divider { height: 1px; background: var(--product-border-color, #eee); }

/* Auth Tabs */
.auth-tabs { display: flex; gap: 0; margin-bottom: var(--space-lg); border-bottom: 2px solid var(--product-border-color, #eee); }
.auth-tab { flex: 1; padding: var(--space-lg) var(--space-md); background: none; border: none; font-size: 0.9375rem; font-weight: 600; color: var(--color-primary-light, #ababab); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all var(--transition-normal); }
.auth-tab.active { color: var(--store-text-primary); border-bottom-color: var(--color-primary); }
.checkout-auth-form { display: flex; flex-direction: column; gap: var(--space-md); }

/* Forms */
.checkout-form-grid { display: grid; grid-template-columns: 1fr; gap: var(--space-md); }
@media (min-width: 600px) { .checkout-form-grid { grid-template-columns: 1fr 1fr; } }
.checkout-field { display: flex; flex-direction: column; gap: var(--space-xs); }
.checkout-label { font-size: 0.875rem; font-weight: 600; color: var(--store-text-primary); }
.req { color: var(--promotion-bg, #ff0000); }
.checkout-input { padding: 0.625rem 0.875rem; border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-md); font-size: 0.875rem; outline: none; color: var(--store-text-primary); background: var(--bg-primary, #fff); width: 100%; box-sizing: border-box; transition: border-color var(--transition-normal); }
.checkout-input:focus { border-color: var(--color-primary); }
.checkout-input.input-error { border-color: var(--promotion-bg, #ff0000); }
.checkout-otp-row { display: flex; gap: var(--space-sm); justify-content: center; margin: var(--space-md) 0; direction: ltr; }
.checkout-otp-box { width: 48px; height: 56px; text-align: center; font-size: 1.25rem; font-weight: 700; font-family: monospace; border: 1.5px solid var(--product-border-color, #eee); border-radius: var(--radius-md); outline: none; background: var(--bg-primary, #fff); color: var(--store-text-primary); transition: all var(--transition-normal); caret-color: var(--color-primary); }
.checkout-otp-box:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb, 79, 70, 229), 0.1); }
.checkout-otp-box.input-error { border-color: var(--promotion-bg, #ff0000); }
.field-error { font-size: 0.75rem; color: var(--promotion-bg, #ff0000); }
.auth-error-msg { color: var(--promotion-bg, #ff0000); font-size: 0.875rem; margin: var(--space-xs) 0; padding: var(--space-sm) var(--space-lg); background: var(--bg-secondary, #f5f5f5); border-radius: var(--radius-md); }
.checkout-alt-text { display: flex; align-items: center; justify-content: center; gap: var(--space-xs); font-size: 0.8125rem; color: var(--footer-text-color, #374151); margin-top: var(--space-sm); }
.checkout-alt-text a { color: var(--color-primary); text-decoration: underline; }

/* Buttons */
.checkout-btn { display: block; width: 100%; padding: 0.875rem var(--space-md); background: var(--color-primary); color: var(--bg-primary, #fff); border: none; border-radius: var(--radius-md); font-size: 0.9375rem; font-weight: 700; cursor: pointer; transition: all var(--transition-normal); margin-top: var(--space-md); }
.checkout-btn:hover:not(:disabled) { background: var(--color-primary-dark); }
.checkout-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.checkout-btn--pay { background: var(--color-primary-reverse, #060606); }
.checkout-btn--pay:hover:not(:disabled) { background: var(--color-primary-dark); }

/* Checkboxes */
.checkout-checkbox { display: flex; align-items: flex-start; gap: var(--space-sm); margin: var(--space-md) 0; cursor: pointer; font-size: 0.8125rem; color: var(--store-text-primary); }
.checkout-checkbox input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--color-primary); flex-shrink: 0; margin-top: 1px; }
.checkout-checkbox--terms { font-size: 0.75rem; color: var(--footer-text-color, #374151); line-height: 1.5; }

/* Shipping Cards */
.checkout-shipping-options { display: flex; flex-direction: column; gap: var(--space-lg); margin-bottom: var(--space-sm); }
.checkout-shipping-card { display: flex; align-items: center; gap: var(--space-md); padding: var(--space-md) var(--space-lg); border: 1.5px solid var(--product-border-color, #eee); border-radius: var(--radius-xl); cursor: pointer; transition: border-color var(--transition-normal); }
.checkout-shipping-card.selected { border-color: var(--color-primary); background: var(--bg-secondary, #f5f5f5); }
.checkout-radio { width: 18px; height: 18px; accent-color: var(--color-primary); flex-shrink: 0; }
.checkout-shipping-info { flex: 1; }
.checkout-shipping-name { display: block; font-size: 0.9375rem; font-weight: 700; color: var(--store-text-primary); }
.checkout-shipping-time { font-size: 0.75rem; color: var(--footer-text-color, #374151); }
.checkout-shipping-price { font-size: 1rem; font-weight: 700; color: var(--store-text-primary); white-space: nowrap; }
.empty-shipping { text-align: center; padding: var(--space-xl); color: var(--footer-text-color, #374151); }

/* Payment */
.checkout-payment-methods { display: flex; flex-wrap: wrap; gap: var(--space-lg); margin-bottom: var(--space-lg); }
.checkout-payment-card { display: flex; align-items: center; gap: var(--space-sm); padding: var(--space-lg) var(--space-lg); border: 1.5px solid var(--product-border-color, #eee); border-radius: var(--radius-xl); cursor: pointer; transition: border-color var(--transition-normal); min-width: 100px; }
.checkout-payment-card.selected { border-color: var(--color-primary); background: var(--bg-secondary, #f5f5f5); }
.checkout-payment-name { font-size: 0.875rem; font-weight: 600; color: var(--store-text-primary); }
.checkout-payment-fee { font-size: 0.75rem; color: var(--footer-text-color, #374151); }
.checkout-card-form { border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-xl); padding: var(--space-lg); margin-bottom: var(--space-md); }
.card-form-note { margin: 0 0 var(--space-md); font-size: 0.875rem; color: var(--footer-text-color, #374151); }

/* Order Summary */
.checkout-order-summary { border: 1px solid var(--product-border-color, #eee); border-radius: var(--radius-xl); padding: var(--space-md) var(--space-lg); margin: var(--space-lg) 0 0; }
.summary-row { display: flex; justify-content: space-between; padding: var(--space-sm) 0; font-size: 0.875rem; color: var(--store-text-primary); }
.summary-row.discount { color: var(--color-primary); }
.summary-row.total { font-size: 1.125rem; font-weight: 700; border-top: 1px solid var(--product-border-color, #eee); padding-top: var(--space-lg); margin-top: var(--space-xs); }
</style>
