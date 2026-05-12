<template>
  <div class="checkout-page">
    <!-- ====== ORDER HEADER ====== -->
    <div class="checkout-header">
      <div class="checkout-header__inner container">
        <div class="checkout-header__left">
          <div class="checkout-header__logo">
            <img src="https://cdn.salla.sa/RvPxw/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png" alt="Eseven Store" class="checkout-header__logo-img" />
            <span class="checkout-header__logo-label">Eseven Store</span>
          </div>
          <div class="checkout-header__thumbs">
            <img v-for="item in cart.items" :key="item.id" :src="item.image" :alt="item.name" class="checkout-header__thumb" />
          </div>
        </div>
        <div class="checkout-header__right">
          <div class="checkout-header__title">Total Order</div>
          <div class="checkout-header__total">{{ cart.total }} <span class="checkout-header__currency">ر.س</span></div>
          <button class="checkout-header__coupon-btn" @click="showCoupon = !showCoupon">Use Coupon?</button>
        </div>
      </div>
      <!-- Coupon Dropdown -->
      <div v-if="showCoupon" class="checkout-coupon container">
        <div class="checkout-coupon__row">
          <input type="text" v-model="couponCode" placeholder="Enter coupon code" class="checkout-coupon__input" />
          <button class="checkout-coupon__apply" @click="applyCoupon">Apply</button>
        </div>
      </div>
      <!-- Order Details toggle -->
      <div class="checkout-header__details-toggle container">
        <button class="checkout-details-btn" @click="showOrderDetails = !showOrderDetails">Order Details</button>
      </div>
    </div>

    <!-- ====== STEPS ====== -->
    <div class="container checkout-body">

      <!-- ─── STEP 1: Login / Register ─── -->
      <section class="checkout-step" :class="{ completed: currentStep > 1 }">
        <div class="checkout-step__header">
          <div class="checkout-step__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title" v-if="currentStep === 1 && !isGuest">Login / Register</h2>
            <h2 class="checkout-step__title" v-else-if="currentStep === 1 && isGuest">Welcome, Dear Guest</h2>
            <h2 class="checkout-step__title" v-else>Welcome, {{ guestForm.firstName || 'Tes' }} {{ guestForm.lastName || 'Ss' }}!</h2>
            <p class="checkout-step__subtitle" v-if="currentStep === 1 && !isGuest">Log In Or Create A New Account To Complete Your Order.</p>
            <p class="checkout-step__subtitle" v-else-if="currentStep === 1 && isGuest">Please Add Your Contact Information</p>
            <p class="checkout-step__subtitle" v-else>{{ guestForm.phone || '+923488092160' }}</p>
          </div>
          <button v-if="currentStep === 1 && !isGuest" class="checkout-step__side-btn" @click="isGuest = true">Purchase as guest ›</button>
        </div>

        <!-- Login form -->
        <div v-if="currentStep === 1 && !isGuest" class="checkout-step__content">
          <label class="checkout-label">Email Address</label>
          <input type="email" v-model="loginEmail" placeholder="your@email.com" class="checkout-input" />
          <button class="checkout-btn" @click="submitLogin">Enter</button>
        </div>

        <!-- Guest form -->
        <div v-if="currentStep === 1 && isGuest" class="checkout-step__content">
          <div class="checkout-form-grid">
            <div class="checkout-field">
              <label class="checkout-label">First Name <span class="req">*</span></label>
              <input type="text" v-model="guestForm.firstName" placeholder="Enter your first name" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Last Name <span class="req">*</span></label>
              <input type="text" v-model="guestForm.lastName" placeholder="Enter your last name" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Email <span class="req">*</span></label>
              <input type="email" v-model="guestForm.email" placeholder="example@mail.com" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Phone Number <span class="req">*</span></label>
              <div class="checkout-phone-input">
                <div class="checkout-phone-prefix">
                  <span class="checkout-phone-arrow">˅</span>
                  <span class="checkout-phone-flag">🇵🇰</span>
                </div>
                <input type="tel" v-model="guestForm.phone" placeholder="+92 301 2345678" class="checkout-input checkout-input--phone" />
              </div>
            </div>
          </div>
          <button class="checkout-btn" @click="submitGuest">Continue As Guest</button>
          <p class="checkout-alt-text">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            Already have an account?
          </p>
        </div>
      </section>

      <div class="checkout-divider" v-if="currentStep >= 1"></div>

      <!-- ─── STEP 2: Shipping Address ─── -->
      <section class="checkout-step" :class="{ locked: currentStep < 2, completed: currentStep > 2 }">
        <div class="checkout-step__header">
          <div class="checkout-step__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 0 0-16 0c0 3 2.7 7 8 11.7z"/></svg>
          </div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">Shipping Address</h2>
            <p class="checkout-step__subtitle" v-if="currentStep < 2">Ensure The Delivery Address Is Accurate For Timely Delivery.</p>
            <p class="checkout-step__subtitle" v-else-if="currentStep > 2">- {{ addressForm.country }} - {{ addressForm.city }} - {{ addressForm.street }}</p>
            <p class="checkout-step__subtitle" v-else>Ensure The Delivery Address Is Accurate For Timely Delivery.</p>
          </div>
          <button v-if="currentStep > 2" class="checkout-edit-btn" @click="currentStep = 2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>

        <div v-if="currentStep === 2" class="checkout-step__content">
          <div class="checkout-form-grid">
            <div class="checkout-field">
              <label class="checkout-label">Country <span class="req">*</span></label>
              <select v-model="addressForm.country" class="checkout-select">
                <option>Saudi Arabia</option>
                <option>Pakistan</option>
                <option>Bahrain</option>
                <option>UAE</option>
                <option>Kuwait</option>
              </select>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Region <span class="req">*</span></label>
              <select v-model="addressForm.region" class="checkout-select">
                <option value="">Search for a region...</option>
                <option>Riyadh</option>
                <option>Jeddah</option>
                <option>Islamabad</option>
              </select>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">City <span class="req">*</span></label>
              <select v-model="addressForm.city" class="checkout-select">
                <option>ISLAMABAD</option>
                <option>RIYADH</option>
                <option>JEDDAH</option>
              </select>
            </div>
            <div class="checkout-field">
              <label class="checkout-label">District <span class="req">*</span></label>
              <input type="text" v-model="addressForm.district" placeholder="E-7" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Street <span class="req">*</span></label>
              <input type="text" v-model="addressForm.street" placeholder="Aurangzeb Road,286" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Postal Code <span class="req">*</span></label>
              <input type="text" v-model="addressForm.postalCode" placeholder="Postal Code" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Building Number (Optional)</label>
              <input type="text" v-model="addressForm.buildingNo" placeholder="286" class="checkout-input" />
            </div>
            <div class="checkout-field">
              <label class="checkout-label">Building Description (Optional)</label>
              <input type="text" v-model="addressForm.buildingDesc" placeholder="Building Description" class="checkout-input" />
            </div>
          </div>
          <label class="checkout-checkbox">
            <input type="checkbox" v-model="deliverToOther" />
            <span>Deliver order to someone else?</span>
          </label>
          <!-- Recipient fields -->
          <div v-if="deliverToOther" class="checkout-recipient">
            <div class="checkout-field">
              <label class="checkout-label">Recipient's Name <span class="req">*</span></label>
              <input type="text" v-model="recipientName" class="checkout-input" />
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">Phone Number <span class="req">*</span></label>
                <div class="checkout-phone-input">
                  <div class="checkout-phone-prefix">
                    <span class="checkout-phone-arrow">˅</span>
                    <span class="checkout-phone-flag">🇵🇰</span>
                  </div>
                  <input type="tel" v-model="recipientPhone" placeholder="+92 301 2345678" class="checkout-input checkout-input--phone" />
                </div>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">Email (Optional)</label>
                <input type="email" v-model="recipientEmail" class="checkout-input" />
              </div>
            </div>
            <label class="checkout-checkbox">
              <input type="checkbox" v-model="smsUpdates" />
              <span>Get order updates via SMS</span>
            </label>
          </div>
          <button class="checkout-btn" @click="submitAddress">Save</button>
        </div>
      </section>

      <div class="checkout-divider" v-if="currentStep >= 2"></div>

      <!-- ─── STEP 3: Shipping Company ─── -->
      <section class="checkout-step" :class="{ locked: currentStep < 3, completed: currentStep > 3 }">
        <div class="checkout-step__header">
          <div class="checkout-step__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          </div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">Shipping Company</h2>
            <p class="checkout-step__subtitle" v-if="currentStep <= 3">Select A Shipping Option That Works Best For You.</p>
            <p class="checkout-step__subtitle" v-else>{{ selectedShipping?.name }}, {{ selectedShipping?.time }}</p>
          </div>
          <button v-if="currentStep > 3" class="checkout-edit-btn" @click="currentStep = 3">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>

        <div v-if="currentStep === 3" class="checkout-step__content">
          <div class="checkout-shipping-options">
            <label
              v-for="opt in shippingOptions"
              :key="opt.id"
              class="checkout-shipping-card"
              :class="{ selected: selectedShippingId === opt.id }"
            >
              <input type="radio" name="shipping" :value="opt.id" v-model="selectedShippingId" class="checkout-radio" />
              <img :src="opt.logo" :alt="opt.name" class="checkout-shipping-logo" />
              <div class="checkout-shipping-info">
                <span class="checkout-shipping-name">{{ opt.name }}</span>
                <span class="checkout-shipping-time">{{ opt.time }}</span>
              </div>
              <span class="checkout-shipping-price">{{ opt.price }} ر.س</span>
            </label>
          </div>
          <button class="checkout-btn" @click="submitShipping">Confirm Shipping Company</button>
        </div>
      </section>

      <div class="checkout-divider" v-if="currentStep >= 3"></div>

      <!-- ─── STEP 4: Additional Information ─── -->
      <section class="checkout-step" :class="{ locked: currentStep < 4, completed: currentStep > 4 }">
        <div class="checkout-step__header">
          <div class="checkout-step__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/></svg>
          </div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">Additional Information And Preferences</h2>
          </div>
          <button v-if="currentStep > 4" class="checkout-edit-btn" @click="currentStep = 4">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>

        <div v-if="currentStep === 4" class="checkout-step__content">
          <div class="checkout-field">
            <label class="checkout-label">Mobile Number <span class="req">*</span></label>
            <div class="checkout-phone-input">
              <div class="checkout-phone-prefix">
                <span class="checkout-phone-arrow">˅</span>
                <span class="checkout-phone-flag">🇵🇰</span>
              </div>
              <input type="tel" v-model="additionalPhone" placeholder="0301 2345678" class="checkout-input checkout-input--phone" />
            </div>
          </div>
          <button class="checkout-btn" @click="submitAdditional">Confirm Information</button>
        </div>
      </section>

      <div class="checkout-divider" v-if="currentStep >= 4"></div>

      <!-- ─── STEP 5: Payment ─── -->
      <section class="checkout-step" :class="{ locked: currentStep < 5 }">
        <div class="checkout-step__header">
          <div class="checkout-step__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>
          <div class="checkout-step__title-wrap">
            <h2 class="checkout-step__title">Payment</h2>
            <p class="checkout-step__subtitle">Mada</p>
          </div>
        </div>

        <div v-if="currentStep === 5" class="checkout-step__content">
          <!-- Payment Methods -->
          <div class="checkout-payment-methods">
            <label
              v-for="pm in paymentMethods"
              :key="pm.id"
              class="checkout-payment-card"
              :class="{ selected: selectedPayment === pm.id }"
            >
              <input type="radio" name="payment" :value="pm.id" v-model="selectedPayment" class="checkout-radio" />
              <img :src="pm.logo" :alt="pm.name" class="checkout-payment-logo" />
            </label>
          </div>

          <!-- Card Details -->
          <div v-if="selectedPayment === 'mada' || selectedPayment === 'visa'" class="checkout-card-form">
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">Card Details <span class="req">*</span></label>
                <div class="checkout-card-input-wrap">
                  <input type="text" v-model="cardNumber" placeholder="Card Number" class="checkout-input" />
                  <div class="checkout-card-expiry">
                    <span class="checkout-card-hint">MM / YY</span>
                    <span class="checkout-card-hint">CVV</span>
                  </div>
                </div>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">Card Holder Name <span class="req">*</span></label>
                <input type="text" v-model="cardName" placeholder="Enter Name" class="checkout-input" />
              </div>
            </div>
            <label class="checkout-checkbox">
              <input type="checkbox" v-model="saveCard" checked />
              <span>Save my card details for future orders</span>
            </label>
          </div>

          <!-- T&C -->
          <label class="checkout-checkbox checkout-checkbox--terms">
            <input type="checkbox" v-model="agreeTerms" />
            <span>By making this payment, I acknowledge that I have read and agree to the terms and conditions of the site and acknowledge that I am 18 years old or over.</span>
          </label>

          <button class="checkout-btn" @click="confirmPayment" :disabled="!agreeTerms">Confirm Payment</button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cartStore'

const router = useRouter()
const cart = useCartStore()

// ─── State ───
const currentStep = ref(1)
const showCoupon = ref(false)
const showOrderDetails = ref(false)
const couponCode = ref('')
const isGuest = ref(false)

// Step 1: Login
const loginEmail = ref('')

// Step 1: Guest
const guestForm = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
})

// Step 2: Address
const addressForm = ref({
  country: 'Pakistan',
  region: '',
  city: 'ISLAMABAD',
  district: '',
  street: '',
  postalCode: '',
  buildingNo: '',
  buildingDesc: '',
})
const deliverToOther = ref(false)
const recipientName = ref('')
const recipientPhone = ref('')
const recipientEmail = ref('')
const smsUpdates = ref(false)

// Step 3: Shipping
const selectedShippingId = ref(2)
const shippingOptions = [
  { id: 1, name: 'Smsa', time: '4 - 12 أيام عمل', price: 40, logo: 'https://cdn.salla.sa/RvPxw/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png' },
  { id: 2, name: 'مدة الشحن', time: 'المتوقع ( من 4 ايام الى 12 ايام )', price: 45, logo: 'https://cdn.salla.sa/RvPxw/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png' },
]
const selectedShipping = computed(() => shippingOptions.find(s => s.id === selectedShippingId.value))

// Step 4: Additional
const additionalPhone = ref('')

// Step 5: Payment
const selectedPayment = ref('mada')
const paymentMethods = [
  { id: 'mada', name: 'Mada', logo: 'https://cdn.salla.sa/pQnGr/Znhz2GnX9rGvBEHSYYPfPXm0jXBfNsqVdlYCOO40.png' },
  { id: 'visa', name: 'Visa', logo: 'https://cdn.salla.sa/pQnGr/PJTQBJ4gMaJnbaNd9hAXDTMu1gPWqnlG6t9MQRuX.png' },
  { id: 'stc', name: 'STC Pay', logo: 'https://cdn.salla.sa/pQnGr/FD0sMPBgzsMV2He3rlVNJr46gkVfrH7aNefqzeDT.png' },
  { id: 'tamara', name: 'Tamara', logo: 'https://cdn.salla.sa/pQnGr/HKJolI8ZGxTIpOVnqhXpHbRWEfPSnHIQTYd9t5XP.png' },
  { id: 'tabby', name: 'Tabby', logo: 'https://cdn.salla.sa/pQnGr/v15gfaJKXfhsBxVgCJxcwAD4xWAEh2SUFHhbV6Ah.png' },
]
const cardNumber = ref('')
const cardName = ref('')
const saveCard = ref(true)
const agreeTerms = ref(true)

// ─── Actions ───
function applyCoupon() {
  if (couponCode.value.trim()) {
    cart.applyCoupon(couponCode.value.trim())
    showCoupon.value = false
  }
}

function submitLogin() {
  if (loginEmail.value.trim()) {
    currentStep.value = 2
  }
}

function submitGuest() {
  if (guestForm.value.firstName && guestForm.value.email) {
    currentStep.value = 2
  }
}

function submitAddress() {
  if (addressForm.value.street || addressForm.value.city) {
    currentStep.value = 3
  }
}

function submitShipping() {
  if (selectedShippingId.value) {
    currentStep.value = 4
  }
}

function submitAdditional() {
  currentStep.value = 5
}

function confirmPayment() {
  if (agreeTerms.value) {
    router.push('/checkout/success/ORD-' + Date.now())
  }
}
</script>

<style scoped>
/* ─── Page ─── */
.checkout-page {
  background: #f9fafb;
  min-height: 100vh;
  padding-bottom: 3rem;
}
.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* ─── Header ─── */
.checkout-header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: 1.25rem 0 0;
  margin-bottom: 0;
}
.checkout-header__inner {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.checkout-header__left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.checkout-header__logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}
.checkout-header__logo-img {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
}
.checkout-header__logo-label {
  font-size: 0.625rem;
  color: #6b7280;
}
.checkout-header__thumbs {
  display: flex;
  gap: 0.25rem;
}
.checkout-header__thumb {
  width: 32px;
  height: 32px;
  object-fit: contain;
  border-radius: 4px;
}
.checkout-header__right {
  text-align: right;
}
.checkout-header__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}
.checkout-header__total {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}
.checkout-header__currency {
  font-size: 1rem;
}
.checkout-header__coupon-btn {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: underline;
  margin-top: 0.25rem;
}

/* Coupon */
.checkout-coupon {
  padding: 0.75rem 0;
}
.checkout-coupon__row {
  display: flex;
  gap: 0.5rem;
}
.checkout-coupon__input {
  flex: 1;
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}
.checkout-coupon__input:focus {
  border-color: var(--color-primary, #858585);
}
.checkout-coupon__apply {
  padding: 0.625rem 1.5rem;
  background: #374151;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
}

/* Order Details toggle */
.checkout-header__details-toggle {
  display: flex;
  justify-content: center;
  padding: 0.75rem 0;
}
.checkout-details-btn {
  padding: 0.375rem 1.25rem;
  border: 1px solid #d1d5db;
  border-radius: 20px;
  background: #fff;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  cursor: pointer;
  transition: all 0.2s;
}
.checkout-details-btn:hover {
  border-color: var(--color-primary, #858585);
}

/* ─── Steps ─── */
.checkout-body {
  padding-top: 0;
}
.checkout-step {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 0;
  transition: opacity 0.3s;
}
.checkout-step.locked {
  opacity: 0.45;
  pointer-events: none;
}
.checkout-step.completed {
  opacity: 1;
}
.checkout-step__header {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}
.checkout-step__icon {
  color: var(--store-text-primary, #111827);
  flex-shrink: 0;
  margin-top: 2px;
}
.checkout-step__title-wrap {
  flex: 1;
}
.checkout-step__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0;
  line-height: 1.3;
}
.checkout-step__subtitle {
  font-size: 0.8125rem;
  color: #6b7280;
  margin: 0.125rem 0 0;
}
.checkout-step__side-btn {
  background: none;
  border: none;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--store-text-primary, #111827);
  cursor: pointer;
  text-decoration: underline;
  white-space: nowrap;
}
.checkout-edit-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.checkout-edit-btn:hover {
  border-color: var(--color-primary, #858585);
}

/* Content */
.checkout-step__content {
  margin-top: 1.25rem;
}
.checkout-divider {
  height: 1px;
  background: #e5e7eb;
  margin: 0;
}

/* ─── Forms ─── */
.checkout-form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}
@media (min-width: 600px) {
  .checkout-form-grid {
    grid-template-columns: 1fr 1fr;
  }
}
.checkout-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}
.checkout-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--store-text-primary, #111827);
}
.req {
  color: #ef4444;
}
.checkout-input {
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  color: var(--store-text-primary, #111827);
  background: #fff;
  width: 100%;
  box-sizing: border-box;
}
.checkout-input:focus {
  border-color: var(--color-primary, #858585);
}
.checkout-select {
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  color: var(--store-text-primary, #111827);
  background: #fff;
  width: 100%;
  box-sizing: border-box;
  cursor: pointer;
}

/* Phone input */
.checkout-phone-input {
  display: flex;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  overflow: hidden;
}
.checkout-phone-prefix {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0 0.625rem;
  background: #f9fafb;
  border-right: 1px solid #d1d5db;
  flex-shrink: 0;
}
.checkout-phone-arrow {
  font-size: 0.75rem;
  color: #9ca3af;
}
.checkout-phone-flag {
  font-size: 1.125rem;
}
.checkout-input--phone {
  border: none;
  border-radius: 0;
}

/* Buttons */
.checkout-btn {
  display: block;
  width: 100%;
  padding: 0.875rem 1rem;
  background: #9ca3af;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 1rem;
}
.checkout-btn:hover:not(:disabled) {
  background: #6b7280;
}
.checkout-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Checkboxes */
.checkout-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin: 1rem 0;
  cursor: pointer;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
}
.checkout-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary, #858585);
  flex-shrink: 0;
  margin-top: 1px;
}
.checkout-checkbox--terms {
  font-size: 0.75rem;
  color: #6b7280;
  line-height: 1.5;
}

.checkout-alt-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #6b7280;
  margin-top: 1rem;
}
.checkout-recipient {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  margin: 0.75rem 0;
}

/* ─── Shipping Cards ─── */
.checkout-shipping-options {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}
.checkout-shipping-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: border-color 0.2s;
}
.checkout-shipping-card.selected {
  border-color: var(--color-primary, #858585);
  background: #fafafa;
}
.checkout-radio {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary, #858585);
  flex-shrink: 0;
}
.checkout-shipping-logo {
  width: 48px;
  height: 36px;
  object-fit: contain;
}
.checkout-shipping-info {
  flex: 1;
}
.checkout-shipping-name {
  display: block;
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}
.checkout-shipping-time {
  font-size: 0.75rem;
  color: #6b7280;
}
.checkout-shipping-price {
  font-size: 1rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  white-space: nowrap;
}

/* ─── Payment ─── */
.checkout-payment-methods {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}
.checkout-payment-card {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: border-color 0.2s;
  min-width: 100px;
}
.checkout-payment-card.selected {
  border-color: var(--color-primary, #858585);
  background: #fafafa;
}
.checkout-payment-logo {
  height: 28px;
  width: auto;
  object-fit: contain;
}
.checkout-card-form {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}
.checkout-card-input-wrap {
  position: relative;
}
.checkout-card-expiry {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  gap: 0.75rem;
}
.checkout-card-hint {
  font-size: 0.75rem;
  color: #9ca3af;
}
</style>
