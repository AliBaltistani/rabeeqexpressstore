<template>
  <div class="co-page" :class="{ rtl: isRtl }">

    <!-- ═══ TOP BAR ═══ -->
    <header class="co-bar">
      <div class="co-bar__inner">
        <div class="co-bar__left">
          <router-link to="/" class="co-bar__logo-link">
            <img v-if="settings.storeSettings.logo" :src="settings.storeSettings.logo" :alt="settings.storeSettings.storeName" class="co-bar__logo" />
            <span v-else class="co-bar__storename">{{ settings.storeSettings.storeName }}</span>
          </router-link>
          <div class="co-bar__thumbs">
            <img v-for="item in cart.items.slice(0,5)" :key="item.id" :src="item.image || ''" :alt="item.productName" class="co-bar__thumb" />
            <span v-if="cart.items.length > 5" class="co-bar__thumb-more">+{{ cart.items.length - 5 }}</span>
          </div>
        </div>
        <div class="co-bar__right">
          <div class="co-bar__total-label">{{ $t('checkout.totalOrder') }}</div>
          <div class="co-bar__total-value">{{ cart.total?.formatted }}</div>
          <div v-if="cashback" class="co-bar__cashback">🎁 {{ $t('checkout.cashback') }}: {{ cashback }}</div>
          <button class="co-bar__coupon-btn" @click="showCoupon = !showCoupon">{{ $t('checkout.useCoupon') }}</button>
          <button class="co-bar__details-btn" @click="showDetails = !showDetails">{{ $t('checkout.orderDetails') }} {{ showDetails ? '▲' : '▼' }}</button>
        </div>
      </div>
      <!-- Coupon row -->
      <div v-if="showCoupon" class="co-coupon">
        <div class="co-coupon__row">
          <input v-model="couponInput" type="text" :placeholder="$t('checkout.enterCouponCode')" class="co-input co-coupon__field" @keydown.enter="applyCoupon" />
          <button class="co-btn co-btn--sm" :disabled="couponLoading" @click="applyCoupon">{{ couponLoading ? '...' : $t('checkout.apply') }}</button>
        </div>
        <p v-if="couponMsg" class="co-msg" :class="couponOk ? 'co-msg--ok' : 'co-msg--err'">{{ couponMsg }}</p>
      </div>
      <!-- Order details drawer -->
      <transition name="co-slide">
        <div v-if="showDetails" class="co-details">
          <div v-for="item in cart.items" :key="item.id" class="co-details__row">
            <img :src="item.image || ''" class="co-details__img" />
            <div class="co-details__info">
              <span class="co-details__name">{{ item.productName }}</span>
              <span class="co-details__qty">x{{ item.quantity }}</span>
            </div>
            <span class="co-details__price">{{ item.lineTotal?.formatted ?? item.unitPrice?.formatted }}</span>
          </div>
        </div>
      </transition>
    </header>

    <!-- ═══ STEPS ═══ -->
    <main class="co-main">

      <!-- STEP 1 — Auth -->
      <section class="co-step" :class="stepClass(1)">
        <div class="co-step__head" @click="goStep(1)">
          <div class="co-step__num" :class="{ done: step > 1 }">{{ step > 1 ? '✓' : '1' }}</div>
          <div class="co-step__titles">
            <h2 class="co-step__title">{{ step1Title }}</h2>
            <p v-if="step > 1" class="co-step__summary">{{ step1Summary }}</p>
          </div>
          <button v-if="step > 1" class="co-edit-btn" @click.stop="goStep(1)">{{ $t('checkout.edit') }}</button>
        </div>
        <transition name="co-expand">
          <div v-if="step === 1" class="co-step__body">
            <!-- Logged in already -->
            <div v-if="auth.isAuthenticated" class="co-auth-done">
              <p class="co-auth-done__msg">{{ $t('checkout.welcomeBack', { name: auth.user?.name }) }}</p>
              <button class="co-btn" @click="step = 2">{{ $t('checkout.continue') }}</button>
            </div>
            <!-- Email probe -->
            <template v-else-if="authPhase === 'email'">
              <p class="co-step__sub">{{ $t('checkout.loginSubtitle') }}</p>
              <div class="co-field">
                <label class="co-label">{{ $t('checkout.emailAddress') }} <span class="co-req">*</span></label>
                <input v-model="probeEmail" type="email" class="co-input" :class="{ err: errs.email }" @keydown.enter="probeEmailSubmit" />
                <span v-if="errs.email" class="co-err">{{ errs.email }}</span>
              </div>
              <p v-if="authError" class="co-msg co-msg--err">{{ authError }}</p>
              <button class="co-btn" :disabled="authLoading" @click="probeEmailSubmit">{{ authLoading ? '...' : $t('loginModal.continue') }}</button>
              <button class="co-link-btn" @click="authPhase = 'guest'">{{ $t('checkout.purchaseAsGuest') }}</button>
            </template>
            <!-- OTP verify (existing user) -->
            <template v-else-if="authPhase === 'otp'">
              <p class="co-step__sub">{{ $t('loginModal.otpSubtitle', { email: probeEmail }) }}</p>
              <div class="co-otp-row">
                <input v-for="i in 4" :key="i" :ref="el => { if(el) otpRefs[i-1] = el as HTMLInputElement }" type="text" inputmode="numeric" maxlength="1" class="co-otp-box" :class="{ err: errs.otp }" :value="otpDigits[i-1]" @input="otpInput(i-1,$event)" @keydown.backspace="otpBackspace(i-1,$event)" @paste.prevent="otpPaste($event)" />
              </div>
              <span v-if="errs.otp" class="co-err" style="text-align:center;display:block">{{ errs.otp }}</span>
              <p v-if="authError" class="co-msg co-msg--err">{{ authError }}</p>
              <button class="co-btn" :disabled="authLoading || otpDigits.join('').length < 4" @click="verifyOtp">{{ authLoading ? '...' : $t('loginModal.verify') }}</button>
              <div class="co-otp-resend">
                <span v-if="otpCooldown > 0">{{ $t('loginModal.resendIn', { seconds: otpCooldown }) }}</span>
                <button v-else class="co-link-btn" @click="sendOtp">{{ $t('loginModal.resendCode') }}</button>
              </div>
              <button class="co-link-btn" @click="authPhase = 'email'">← {{ $t('loginModal.back') }}</button>
            </template>
            <!-- Register form (new user) -->
            <template v-else-if="authPhase === 'register'">
              <p class="co-step__sub">{{ $t('checkout.loginSubtitle') }}</p>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.firstName') }} <span class="co-req">*</span></label><input v-model="regForm.firstName" type="text" class="co-input" :class="{ err: errs.regFn }" /><span v-if="errs.regFn" class="co-err">{{ errs.regFn }}</span></div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.lastName') }} <span class="co-req">*</span></label><input v-model="regForm.lastName" type="text" class="co-input" :class="{ err: errs.regLn }" /><span v-if="errs.regLn" class="co-err">{{ errs.regLn }}</span></div>
              </div>
              <div class="co-field">
                <label class="co-label">{{ $t('checkout.phoneNumber') }} <span class="co-req">*</span></label>
                <div class="co-phone-row">
                  <select v-model="regForm.dialCode" class="co-dial"><option v-for="c in countries" :key="c.code" :value="c.phone_code">{{ c.phone_code }}</option></select>
                  <input v-model="regForm.phone" type="tel" class="co-input co-input--phone" :class="{ err: errs.regPhone }" />
                </div>
                <span v-if="errs.regPhone" class="co-err">{{ errs.regPhone }}</span>
              </div>
              <label class="co-check"><input type="checkbox" v-model="regForm.mailingList" /><span>{{ $t('checkout.mailingList') }}</span></label>
              <p v-if="authError" class="co-msg co-msg--err">{{ authError }}</p>
              <button class="co-btn" :disabled="authLoading" @click="submitRegister">{{ authLoading ? '...' : $t('auth.register') }}</button>
              <button class="co-link-btn" @click="authPhase = 'email'">← {{ $t('loginModal.back') }}</button>
            </template>
            <!-- OTP after register -->
            <template v-else-if="authPhase === 'otp-reg'">
              <p class="co-step__sub">{{ $t('loginModal.otpSubtitle', { email: probeEmail }) }}</p>
              <div class="co-otp-row">
                <input v-for="i in 4" :key="i" :ref="el => { if(el) otpRefs[i-1] = el as HTMLInputElement }" type="text" inputmode="numeric" maxlength="1" class="co-otp-box" :class="{ err: errs.otp }" :value="otpDigits[i-1]" @input="otpInput(i-1,$event)" @keydown.backspace="otpBackspace(i-1,$event)" @paste.prevent="otpPaste($event)" />
              </div>
              <p v-if="authError" class="co-msg co-msg--err">{{ authError }}</p>
              <button class="co-btn" :disabled="authLoading || otpDigits.join('').length < 4" @click="verifyOtp">{{ authLoading ? '...' : $t('loginModal.verify') }}</button>
            </template>
            <!-- Guest form -->
            <template v-else-if="authPhase === 'guest'">
              <p class="co-step__sub">{{ $t('checkout.guestSubtitle') }}</p>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.firstName') }} <span class="co-req">*</span></label><input v-model="guestForm.firstName" type="text" class="co-input" :class="{ err: errs.gFn }" /><span v-if="errs.gFn" class="co-err">{{ errs.gFn }}</span></div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.lastName') }} <span class="co-req">*</span></label><input v-model="guestForm.lastName" type="text" class="co-input" :class="{ err: errs.gLn }" /><span v-if="errs.gLn" class="co-err">{{ errs.gLn }}</span></div>
              </div>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.email') }} <span class="co-req">*</span></label><input v-model="guestForm.email" type="email" class="co-input" :class="{ err: errs.gEmail }" /><span v-if="errs.gEmail" class="co-err">{{ errs.gEmail }}</span></div>
                <div class="co-field">
                  <label class="co-label">{{ $t('checkout.phoneNumber') }} <span class="co-req">*</span></label>
                  <div class="co-phone-row">
                    <select v-model="guestForm.dialCode" class="co-dial"><option v-for="c in countries" :key="c.code" :value="c.phone_code">{{ c.phone_code }}</option></select>
                    <input v-model="guestForm.phone" type="tel" class="co-input co-input--phone" :class="{ err: errs.gPhone }" />
                  </div>
                  <span v-if="errs.gPhone" class="co-err">{{ errs.gPhone }}</span>
                </div>
              </div>
              <button class="co-btn" @click="submitGuest">{{ $t('checkout.continueAsGuest') }}</button>
              <button class="co-link-btn" @click="authPhase = 'email'">{{ $t('checkout.alreadyHaveAccount') }}</button>
            </template>
          </div>
        </transition>
      </section>

      <!-- STEP 2 — Shipping Address -->
      <section class="co-step" :class="stepClass(2)">
        <div class="co-step__head" @click="goStep(2)">
          <div class="co-step__num" :class="{ done: step > 2 }">{{ step > 2 ? '✓' : '2' }}</div>
          <div class="co-step__titles">
            <h2 class="co-step__title">{{ $t('checkout.shippingAddress') }}</h2>
            <p v-if="step > 2" class="co-step__summary">{{ addr.city }}, {{ addr.street }}</p>
            <p v-else-if="step === 2" class="co-step__sub">{{ $t('checkout.ensureAddress') }}</p>
          </div>
          <button v-if="step > 2" class="co-edit-btn" @click.stop="goStep(2)">{{ $t('checkout.edit') }}</button>
        </div>
        <transition name="co-expand">
          <div v-if="step === 2" class="co-step__body">
            <!-- Address mode toggler -->
            <div class="co-mode-toggle">
              <button class="co-mode-btn" :class="{ active: addrMode === 'map' }" @click="addrMode = 'map'">🗺 {{ $t('checkout.mapMode') }}</button>
              <button class="co-mode-btn" :class="{ active: addrMode === 'manual' }" @click="addrMode = 'manual'">✏️ {{ $t('checkout.manualMode') }}</button>
            </div>
            <!-- Map Mode -->
            <div v-if="addrMode === 'map' && mapsKey" class="co-map-wrap">
              <div id="co-map" class="co-map"></div>
              <button class="co-location-btn" @click="useCurrentLocation">📍 {{ $t('checkout.currentLocation') }}</button>
              <div class="co-field" style="margin-top:0.75rem">
                <label class="co-label">{{ $t('checkout.buildingDesc') }}</label>
                <textarea v-model="addr.buildingDesc" class="co-input co-textarea" rows="2" :placeholder="$t('checkout.buildingDescPlaceholder')"></textarea>
              </div>
              <p v-if="locationError" class="co-msg co-msg--err">{{ locationError }}</p>
            </div>
            <!-- Manual Mode (or fallback when no maps key) -->
            <div v-if="addrMode === 'manual' || !mapsKey">
              <div class="co-grid2">
                <div class="co-field">
                  <label class="co-label">{{ $t('checkout.country') }} <span class="co-req">*</span></label>
                  <select v-model="addr.country" class="co-input" :class="{ err: errs.addrCountry }">
                    <option value="">—</option>
                    <option v-for="c in countries" :key="c.code" :value="c.name">{{ c.name }}</option>
                  </select>
                  <span v-if="errs.addrCountry" class="co-err">{{ errs.addrCountry }}</span>
                </div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.region') }}</label><input v-model="addr.state" type="text" class="co-input" /></div>
              </div>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.city') }} <span class="co-req">*</span></label><input v-model="addr.city" type="text" class="co-input" :class="{ err: errs.addrCity }" /><span v-if="errs.addrCity" class="co-err">{{ errs.addrCity }}</span></div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.district') }}</label><input v-model="addr.district" type="text" class="co-input" /></div>
              </div>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.street') }} <span class="co-req">*</span></label><input v-model="addr.street" type="text" class="co-input" :class="{ err: errs.addrStreet }" /><span v-if="errs.addrStreet" class="co-err">{{ errs.addrStreet }}</span></div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.postalCode') }}</label><input v-model="addr.postalCode" type="text" class="co-input" /></div>
              </div>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.buildingNo') }}</label><input v-model="addr.buildingNo" type="text" class="co-input" /></div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.buildingDesc') }}</label><textarea v-model="addr.buildingDesc" class="co-input co-textarea" rows="2" :placeholder="$t('checkout.buildingDescPlaceholder')"></textarea></div>
              </div>
            </div>
            <!-- Deliver to someone else -->
            <label class="co-check"><input type="checkbox" v-model="deliverOther" /><span>{{ $t('checkout.deliverToOther') }}</span></label>
            <div v-if="deliverOther" class="co-recipient">
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.recipientName') }} <span class="co-req">*</span></label><input v-model="recipient.name" type="text" class="co-input" /></div>
                <div class="co-field">
                  <label class="co-label">{{ $t('checkout.recipientPhone') }} <span class="co-req">*</span></label>
                  <div class="co-phone-row">
                    <select v-model="recipient.dialCode" class="co-dial"><option v-for="c in countries" :key="c.code" :value="c.phone_code">{{ c.phone_code }}</option></select>
                    <input v-model="recipient.phone" type="tel" class="co-input co-input--phone" />
                  </div>
                </div>
              </div>
              <div class="co-field"><label class="co-label">{{ $t('checkout.recipientEmail') }}</label><input v-model="recipient.email" type="email" class="co-input" /></div>
              <label class="co-check"><input type="checkbox" v-model="recipient.sms" /><span>{{ $t('checkout.smsUpdates') }}</span></label>
            </div>
            <p v-if="errs.addr" class="co-msg co-msg--err">{{ errs.addr }}</p>
            <button class="co-btn" :disabled="addrLoading" @click="submitAddress">{{ addrLoading ? '...' : $t('checkout.save') }}</button>
          </div>
        </transition>
      </section>

      <!-- STEP 3 — Shipping Company -->
      <section class="co-step" :class="stepClass(3)">
        <div class="co-step__head" @click="goStep(3)">
          <div class="co-step__num" :class="{ done: step > 3 }">{{ step > 3 ? '✓' : '3' }}</div>
          <div class="co-step__titles">
            <h2 class="co-step__title">{{ $t('checkout.shippingCompany') }}</h2>
            <p v-if="step > 3" class="co-step__summary">{{ selectedRate?.name }} — {{ selectedRate?.price?.formatted }}</p>
            <p v-else-if="step === 3" class="co-step__sub">{{ $t('checkout.selectShipping') }}</p>
          </div>
          <button v-if="step > 3" class="co-edit-btn" @click.stop="goStep(3)">{{ $t('checkout.edit') }}</button>
        </div>
        <transition name="co-expand">
          <div v-if="step === 3" class="co-step__body">
            <div v-if="ratesLoading" class="co-loading">{{ $t('common.loading') }}</div>
            <div v-else-if="rates.length === 0" class="co-empty">{{ $t('checkout.noShippingRates') }}</div>
            <div v-else class="co-ship-list">
              <label v-for="r in rates" :key="r.id" class="co-ship-card" :class="{ selected: selectedRateId === r.id }">
                <input type="radio" name="ship" :value="r.id" v-model="selectedRateId" class="co-radio" />
                <img v-if="r.logo" :src="r.logo" class="co-ship-logo" />
                <div class="co-ship-info">
                  <span class="co-ship-name">{{ r.name }}</span>
                  <span v-if="r.duration" class="co-ship-time">{{ r.duration }}</span>
                </div>
                <span class="co-ship-price">{{ r.price?.formatted ?? r.price }}</span>
              </label>
            </div>
            <span v-if="errs.rate" class="co-err">{{ errs.rate }}</span>
            <button class="co-btn" @click="submitShipping">{{ $t('checkout.confirmShipping') }}</button>
          </div>
        </transition>
      </section>

      <!-- STEP 4 — Additional Info -->
      <section class="co-step" :class="stepClass(4)">
        <div class="co-step__head" @click="goStep(4)">
          <div class="co-step__num" :class="{ done: step > 4 }">{{ step > 4 ? '✓' : '4' }}</div>
          <div class="co-step__titles">
            <h2 class="co-step__title">{{ $t('checkout.additionalInfo') }}</h2>
            <p v-if="step > 4" class="co-step__summary">{{ infoForm.phone || '-' }}</p>
          </div>
          <button v-if="step > 4" class="co-edit-btn" @click.stop="goStep(4)">{{ $t('checkout.edit') }}</button>
        </div>
        <transition name="co-expand">
          <div v-if="step === 4" class="co-step__body">
            <div class="co-field">
              <label class="co-label">{{ $t('checkout.phoneNumber') }} <span class="co-req">*</span></label>
              <div class="co-phone-row">
                <select v-model="infoForm.dialCode" class="co-dial"><option v-for="c in countries" :key="c.code" :value="c.phone_code">{{ c.phone_code }}</option></select>
                <input v-model="infoForm.phone" type="tel" class="co-input co-input--phone" :class="{ err: errs.infoPhone }" />
              </div>
              <span v-if="errs.infoPhone" class="co-err">{{ errs.infoPhone }}</span>
            </div>
            <div class="co-field"><label class="co-label">{{ $t('checkout.notes') }}</label><textarea v-model="infoForm.notes" class="co-input co-textarea" rows="3" :placeholder="$t('checkout.notesPlaceholder')"></textarea></div>
            <button class="co-btn" @click="submitInfo">{{ $t('checkout.confirmInfo') }}</button>
          </div>
        </transition>
      </section>

      <!-- STEP 5 — Payment -->
      <section class="co-step" :class="stepClass(5)">
        <div class="co-step__head">
          <div class="co-step__num">5</div>
          <div class="co-step__titles">
            <h2 class="co-step__title">{{ $t('checkout.payment') }}</h2>
            <p class="co-step__sub">{{ selectedPaymentName }}</p>
          </div>
        </div>
        <transition name="co-expand">
          <div v-if="step === 5" class="co-step__body">
            <div class="co-pay-list">
              <label v-for="pm in paymentMethods" :key="pm.id" class="co-pay-card" :class="{ selected: selectedPayment === pm.id }">
                <input type="radio" name="pay" :value="pm.id" v-model="selectedPayment" class="co-radio" />
                <img v-if="pm.logo" :src="pm.logo" class="co-pay-logo" />
                <span class="co-pay-name">{{ pm.name }}</span>
                <span v-if="pm.fee" class="co-pay-fee">+{{ pm.fee }}</span>
              </label>
            </div>
            <span v-if="errs.payment" class="co-err">{{ errs.payment }}</span>
            <!-- Card form -->
            <div v-if="isCardMethod" class="co-card-form">
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">{{ $t('checkout.cardNumber') }} <span class="co-req">*</span></label><input v-model="card.number" type="text" class="co-input" maxlength="19" placeholder="4242 4242 4242 4242" /></div>
                <div class="co-field"><label class="co-label">{{ $t('checkout.cardHolderName') }} <span class="co-req">*</span></label><input v-model="card.name" type="text" class="co-input" /></div>
              </div>
              <div class="co-grid2">
                <div class="co-field"><label class="co-label">MM/YY <span class="co-req">*</span></label><input v-model="card.expiry" type="text" class="co-input" maxlength="5" placeholder="12/28" /></div>
                <div class="co-field"><label class="co-label">CVV <span class="co-req">*</span></label><input v-model="card.cvv" type="text" class="co-input" maxlength="4" placeholder="123" /></div>
              </div>
              <label class="co-check"><input type="checkbox" v-model="card.save" /><span>{{ $t('checkout.saveCard') }}</span></label>
            </div>
            <!-- Order summary -->
            <div class="co-summary">
              <div class="co-summary__row"><span>{{ $t('cart.totalProductsCost') }}</span><span>{{ cart.subtotal?.formatted }}</span></div>
              <div v-if="cart.discount?.raw" class="co-summary__row co-summary__row--disc"><span>{{ $t('checkout.discount') }}</span><span>-{{ cart.discount?.formatted }}</span></div>
              <div class="co-summary__row"><span>{{ $t('checkout.shippingCompany') }}</span><span>{{ selectedRate ? (selectedRate.price?.formatted ?? selectedRate.price) : '—' }}</span></div>
              <div class="co-summary__row co-summary__row--total"><span>{{ $t('checkout.totalOrder') }}</span><span>{{ orderTotal }}</span></div>
            </div>
            <!-- Terms -->
            <label class="co-check co-check--terms"><input type="checkbox" v-model="agreeTerms" /><span>{{ termsText }}</span></label>
            <span v-if="errs.terms" class="co-err">{{ errs.terms }}</span>
            <p v-if="orderError" class="co-msg co-msg--err">{{ orderError }}</p>
            <button class="co-btn co-btn--pay" :disabled="orderLoading" @click="confirmPayment">{{ orderLoading ? $t('common.loading') : $t('checkout.confirmPayment') }}</button>
          </div>
        </transition>
      </section>

    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cartStore'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { checkEmailApi, fetchActiveCountries, fetchShippingRates, placeOrder, addAddress, fetchWallet, type CountryOption } from '@/api/services'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const settings = useSettingsStore()
const { t } = useI18n()
const isRtl = computed(() => settings.isRtl)

// ── Step state ──
const step = ref(auth.isAuthenticated ? 2 : 1)
const stepClass = (n: number) => ({
  'co-step--active': step.value === n,
  'co-step--done': step.value > n,
  'co-step--locked': step.value < n,
})
function goStep(n: number) { if (step.value > n) step.value = n }

// ── Top bar ──
const showCoupon = ref(false)
const showDetails = ref(false)
const couponInput = ref(cart.couponCode || '')
const couponLoading = ref(false)
const couponMsg = ref('')
const couponOk = ref(false)
const cashback = ref<string | null>(null)

async function applyCoupon() {
  if (!couponInput.value.trim()) return
  couponLoading.value = true; couponMsg.value = ''
  const r = await cart.applyCoupon(couponInput.value.trim())
  couponLoading.value = false
  couponOk.value = r.success
  couponMsg.value = r.success ? t('checkout.couponApplied') : (r.message || t('common.error'))
  if (r.success) { showCoupon.value = false; couponInput.value = cart.couponCode || couponInput.value }
}

// ── Countries (for phone codes + address country dropdown) ──
const countries = ref<CountryOption[]>([])
async function loadCountries() {
  try { countries.value = await fetchActiveCountries() } catch { /* silent */ }
}

// ── Maps key ──
const mapsKey = computed(() => (settings as any).storeSettings?.googleMapsApiKey || null)

// ── Step 1 — Auth ──
type AuthPhase = 'email' | 'otp' | 'register' | 'otp-reg' | 'guest'
const authPhase = ref<AuthPhase>('email')
const authLoading = ref(false)
const authError = ref('')
const probeEmail = ref('')
const errs = reactive<Record<string, string>>({})
const clearErrs = () => Object.keys(errs).forEach(k => delete errs[k])
const isEmail = (v: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)

// OTP
const otpDigits = ref(['','','',''])
const otpRefs = ref<HTMLInputElement[]>([])
const otpCooldown = ref(0)
let otpTimer: ReturnType<typeof setInterval> | null = null

function startCooldown(s: number) {
  otpCooldown.value = s
  if (otpTimer) clearInterval(otpTimer)
  otpTimer = setInterval(() => { if (--otpCooldown.value <= 0 && otpTimer) { clearInterval(otpTimer); otpTimer = null } }, 1000)
}

async function probeEmailSubmit() {
  clearErrs(); authError.value = ''
  if (!probeEmail.value.trim()) { errs.email = t('checkout.required'); return }
  if (!isEmail(probeEmail.value)) { errs.email = t('checkout.invalidEmail'); return }
  authLoading.value = true
  try {
    const { exists } = await checkEmailApi(probeEmail.value)
    if (exists) { await sendOtp(); authPhase.value = 'otp' }
    else authPhase.value = 'register'
  } catch { authError.value = t('common.error') }
  finally { authLoading.value = false }
}

async function sendOtp() {
  authLoading.value = true
  const r = await auth.sendOtp(probeEmail.value)
  authLoading.value = false
  if (r.success) { otpDigits.value = ['','','','']; startCooldown((r as any).cooldown || 60); nextTick(() => otpRefs.value[0]?.focus()) }
  else authError.value = (r as any).message || t('common.error')
}

async function verifyOtp() {
  clearErrs(); authError.value = ''
  const code = otpDigits.value.join('')
  if (code.length < 4) { errs.otp = t('loginModal.invalidOtp'); return }
  authLoading.value = true
  const r = await auth.verifyOtp(probeEmail.value, code)
  authLoading.value = false
  if (r.success) {
    if (auth.user) { addr.firstName = auth.user.name?.split(' ')[0] || ''; addr.lastName = auth.user.name?.split(' ').slice(1).join(' ') || ''; addr.phone = auth.user.phone || '' }
    step.value = 2
  } else { authError.value = (r as any).message || t('loginModal.invalidOtp'); otpDigits.value = ['','','','']; nextTick(() => otpRefs.value[0]?.focus()) }
}

function otpInput(idx: number, e: Event) {
  const v = (e.target as HTMLInputElement).value.replace(/\D/g, '')
  otpDigits.value[idx] = v.slice(-1)
  if (v && idx < 3) nextTick(() => otpRefs.value[idx+1]?.focus())
  if (otpDigits.value.join('').length === 4) verifyOtp()
}
function otpBackspace(idx: number, e: KeyboardEvent) {
  if (!otpDigits.value[idx] && idx > 0) { e.preventDefault(); otpDigits.value[idx-1]=''; nextTick(() => otpRefs.value[idx-1]?.focus()) }
}
function otpPaste(e: ClipboardEvent) {
  const p = e.clipboardData?.getData('text')?.replace(/\D/g,'') || ''
  for (let i=0;i<4;i++) otpDigits.value[i] = p[i]||''
  if (p.length>=4) nextTick(verifyOtp)
}

// Register form
const regForm = reactive({ firstName: '', lastName: '', phone: '', dialCode: '+966', mailingList: false })
async function submitRegister() {
  clearErrs(); authError.value = ''
  if (!regForm.firstName.trim()) { errs.regFn = t('checkout.required'); return }
  if (!regForm.lastName.trim()) { errs.regLn = t('checkout.required'); return }
  if (!regForm.phone.trim()) { errs.regPhone = t('checkout.required'); return }
  authLoading.value = true
  try {
    const name = `${regForm.firstName} ${regForm.lastName}`
    const phone = `${regForm.dialCode}${regForm.phone}`
    await auth.register({ name, email: probeEmail.value, password: Math.random().toString(36).slice(2) + 'Aa1!', password_confirmation: '' } as any)
    await sendOtp(); authPhase.value = 'otp-reg'
  } catch (e: any) { authError.value = e.response?.data?.message || t('common.error') }
  finally { authLoading.value = false }
}

// Guest form
const guestForm = reactive({ firstName: '', lastName: '', email: '', phone: '', dialCode: '+966' })
function submitGuest() {
  clearErrs()
  if (!guestForm.firstName.trim()) { errs.gFn = t('checkout.required'); return }
  if (!guestForm.lastName.trim()) { errs.gLn = t('checkout.required'); return }
  if (!guestForm.email.trim() || !isEmail(guestForm.email)) { errs.gEmail = t('checkout.invalidEmail'); return }
  if (!guestForm.phone.trim()) { errs.gPhone = t('checkout.required'); return }
  addr.firstName = guestForm.firstName; addr.lastName = guestForm.lastName; addr.phone = guestForm.dialCode + guestForm.phone
  step.value = 2
}

const step1Title = computed(() => {
  if (auth.isAuthenticated) return t('checkout.loginRegister')
  if (authPhase.value === 'guest') return t('checkout.welcomeGuest')
  return t('checkout.loginRegister')
})
const step1Summary = computed(() => {
  if (auth.isAuthenticated && auth.user) return `${auth.user.name} · ${auth.user.phone || auth.user.email}`
  if (authPhase.value === 'guest') return `${guestForm.firstName} ${guestForm.lastName} (${guestForm.email})`
  return probeEmail.value
})

// ── Step 2 — Address ──
const addrMode = ref<'map'|'manual'>('manual')
const addr = reactive({ firstName: '', lastName: '', phone: '', country: '', state: '', city: '', district: '', street: '', postalCode: '', buildingNo: '', buildingDesc: '' })
const deliverOther = ref(false)
const recipient = reactive({ name: '', phone: '', dialCode: '+966', email: '', sms: false })
const addrLoading = ref(false)
const locationError = ref('')

function useCurrentLocation() {
  locationError.value = ''
  if (!navigator.geolocation) { locationError.value = 'Geolocation not supported'; return }
  navigator.geolocation.getCurrentPosition(
    pos => { addr.buildingDesc = `Lat: ${pos.coords.latitude.toFixed(5)}, Lng: ${pos.coords.longitude.toFixed(5)}` },
    () => { locationError.value = 'Could not get location' }
  )
}

async function submitAddress() {
  clearErrs()
  if (!addr.country.trim()) { errs.addrCountry = t('checkout.required'); return }
  if (!addr.city.trim()) { errs.addrCity = t('checkout.required'); return }
  if (!addr.street.trim()) { errs.addrStreet = t('checkout.required'); return }
  addrLoading.value = true
  try {
    // Save address to API if authenticated
    if (auth.isAuthenticated) {
      await addAddress({ address_line_1: addr.street, city: addr.city, state: addr.state, country: addr.country, postal_code: addr.postalCode, phone: addr.phone } as any).catch(() => {})
    }
    // Fetch shipping rates
    rates.value = await fetchShippingRates({ country: addr.country, state: addr.state })
    if (rates.value.length) selectedRateId.value = rates.value[0].id
    step.value = 3
  } catch { errs.addr = t('checkout.shippingRatesError') }
  finally { addrLoading.value = false }
}

// ── Step 3 — Shipping ──
const rates = ref<any[]>([])
const ratesLoading = ref(false)
const selectedRateId = ref<number|null>(null)
const selectedRate = computed(() => rates.value.find(r => r.id === selectedRateId.value))

function submitShipping() {
  clearErrs()
  if (!selectedRateId.value) { errs.rate = t('checkout.required'); return }
  step.value = 4
}

// ── Step 4 — Info ──
const infoForm = reactive({ phone: '', dialCode: '+966', notes: '' })
function submitInfo() {
  clearErrs()
  if (!infoForm.phone.trim()) { errs.infoPhone = t('checkout.required'); return }
  step.value = 5
}

// ── Step 5 — Payment ──
const paymentMethods = computed(() => settings.storeSettings.paymentMethods || [])
const selectedPayment = ref('')
const selectedPaymentName = computed(() => paymentMethods.value.find((p:any) => p.id === selectedPayment.value)?.name || '')
const cardMethods = ['stripe','card','mada','visa','mastercard','credit_card']
const isCardMethod = computed(() => cardMethods.some(k => selectedPayment.value.toLowerCase().includes(k)) || selectedPayment.value === 'stripe')
const card = reactive({ number: '', name: '', expiry: '', cvv: '', save: false })
const agreeTerms = ref(false)
const termsText = computed(() => (settings.storeSettings as any).termsText || t('checkout.agreeTerms'))
const orderLoading = ref(false)
const orderError = ref('')

// Card formatters
watch(() => card.number, v => { const c = v.replace(/\D/g,''); const f = c.replace(/(.{4})/g,'$1 ').trim().substring(0,19); if (card.number !== f) card.number = f })
watch(() => card.expiry, v => { let c = v.replace(/\D/g,''); if (c.length>=2) c = c.substring(0,2)+'/'+c.substring(2,4); if (card.expiry !== c) card.expiry = c })
watch(() => card.cvv, v => { const c = v.replace(/\D/g,'').substring(0,4); if (card.cvv !== c) card.cvv = c })

const orderTotal = computed(() => {
  const sub = cart.subtotal?.raw || 0
  const disc = cart.discount?.raw || 0
  const ship = selectedRate.value?.price?.raw ?? (typeof selectedRate.value?.price === 'number' ? selectedRate.value.price : 0)
  const sym = (cart.total?.formatted || '').replace(/[\d.,]/g,'').trim() || 'SAR'
  return `${sym} ${(sub - disc + ship).toFixed(2)}`
})

async function confirmPayment() {
  clearErrs(); orderError.value = ''
  if (!selectedPayment.value) { errs.payment = t('checkout.selectPayment'); return }
  if (!agreeTerms.value) { errs.terms = t('checkout.agreeTermsRequired'); return }
  if (isCardMethod.value) {
    if (card.number.replace(/\s/g,'').length < 15) { orderError.value = t('checkout.invalidCardNumber'); return }
    if (!card.name.trim()) { orderError.value = t('checkout.invalidCardName'); return }
    if (card.expiry.length < 5) { orderError.value = t('checkout.invalidExpiry'); return }
    if (card.cvv.length < 3) { orderError.value = t('checkout.invalidCvv'); return }
  }
  orderLoading.value = true
  try {
    const isGuest = authPhase.value === 'guest' && !auth.isAuthenticated
    const fullPhone = `${infoForm.dialCode}${infoForm.phone}`
    const payload: any = {
      shippingAddress: { firstName: addr.firstName || guestForm.firstName, lastName: addr.lastName || guestForm.lastName, phone: addr.phone || fullPhone, addressLine1: addr.street, city: addr.city, country: addr.country, state: addr.state, postalCode: addr.postalCode },
      paymentMethod: selectedPayment.value,
      shippingRateId: selectedRateId.value,
      couponCode: cart.couponCode || undefined,
      notes: infoForm.notes || undefined,
      currency: settings.currentCurrencyCode,
    }
    if (isGuest) { payload.guestEmail = guestForm.email; payload.guestName = `${guestForm.firstName} ${guestForm.lastName}`; payload.guestPhone = guestForm.dialCode + guestForm.phone }
    const res = await placeOrder(payload)
    await cart.clearCart()
    if (auth.isAuthenticated) { try { const w = await fetchWallet(); cashback.value = (w as any).balance?.formatted || null } catch {} }
    router.push({ name: 'order-success', params: { orderNumber: res.orderNumber } })
  } catch (e: any) { orderError.value = e.response?.data?.message || t('common.error') }
  finally { orderLoading.value = false }
}

// ── Init ──
onMounted(async () => {
  await Promise.all([cart.loadCart(), loadCountries()])
  if (auth.isAuthenticated) {
    step.value = 2
    if (auth.user) { addr.firstName = auth.user.name?.split(' ')[0] || ''; addr.lastName = auth.user.name?.split(' ').slice(1).join(' ') || ''; addr.phone = auth.user.phone || '' }
  }
  if (countries.value.length) { regForm.dialCode = countries.value[0].phone_code; guestForm.dialCode = countries.value[0].phone_code; recipient.dialCode = countries.value[0].phone_code; infoForm.dialCode = countries.value[0].phone_code }
})
onUnmounted(() => { if (otpTimer) clearInterval(otpTimer) })
</script>

<style scoped>
/* ── Variables ── */
.co-page { --co-primary: #1a1a2e; --co-accent: #e94560; --co-accent2: #0f3460; --co-bg: #f7f8fc; --co-white: #fff; --co-border: #e2e6ed; --co-radius: 12px; --co-shadow: 0 2px 16px rgba(0,0,0,.08); --co-text: #1a1a2e; --co-muted: #7b8a9a; --co-done: #22c55e; min-height: 100vh; background: var(--co-bg); font-family: 'Inter', 'Segoe UI', sans-serif; color: var(--co-text); }

/* ── Top Bar ── */
.co-bar { background: var(--co-primary); color: #fff; padding: 0.75rem 0; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(0,0,0,.25); }
.co-bar__inner { max-width: 860px; margin: 0 auto; padding: 0 1.25rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.co-bar__left { display: flex; align-items: center; gap: 1rem; flex: 1; }
.co-bar__logo-link { display: flex; align-items: center; text-decoration: none; }
.co-bar__logo { height: 38px; width: auto; object-fit: contain; }
.co-bar__storename { font-size: 1.1rem; font-weight: 700; color: #fff; }
.co-bar__thumbs { display: flex; gap: 4px; }
.co-bar__thumb { width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 2px solid rgba(255,255,255,.2); }
.co-bar__thumb-more { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,.15); border-radius: 6px; font-size: .75rem; font-weight: 600; color: #fff; }
.co-bar__right { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.co-bar__total-label { font-size: .75rem; opacity: .7; }
.co-bar__total-value { font-size: 1.25rem; font-weight: 700; color: #fff; }
.co-bar__cashback { background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.4); border-radius: 6px; padding: .25rem .6rem; font-size: .78rem; color: #4ade80; }
.co-bar__coupon-btn, .co-bar__details-btn { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); color: #fff; padding: .35rem .8rem; border-radius: 8px; font-size: .82rem; cursor: pointer; transition: background .2s; }
.co-bar__coupon-btn:hover, .co-bar__details-btn:hover { background: rgba(255,255,255,.22); }
.co-coupon { max-width: 860px; margin: .6rem auto 0; padding: 0 1.25rem; }
.co-coupon__row { display: flex; gap: .5rem; }
.co-coupon__field { flex: 1; }
.co-details { max-width: 860px; margin: .5rem auto 0; padding: .75rem 1.25rem; background: rgba(255,255,255,.06); border-top: 1px solid rgba(255,255,255,.1); }
.co-details__row { display: flex; align-items: center; gap: .75rem; padding: .4rem 0; border-bottom: 1px solid rgba(255,255,255,.07); }
.co-details__img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }
.co-details__info { flex: 1; display: flex; flex-direction: column; }
.co-details__name { font-size: .85rem; color: #e2e8f0; }
.co-details__qty { font-size: .75rem; color: rgba(255,255,255,.5); }
.co-details__price { font-weight: 600; color: #fff; font-size: .9rem; }

/* ── Main ── */
.co-main { max-width: 860px; margin: 1.5rem auto 3rem; padding: 0 1.25rem; display: flex; flex-direction: column; gap: 1rem; }

/* ── Step ── */
.co-step { background: var(--co-white); border-radius: var(--co-radius); box-shadow: var(--co-shadow); border: 2px solid transparent; transition: border-color .25s; overflow: hidden; }
.co-step--active { border-color: var(--co-accent); }
.co-step--done { border-color: var(--co-done); }
.co-step--locked { opacity: .65; pointer-events: none; }
.co-step--locked .co-step__head { cursor: default; }
.co-step__head { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; cursor: pointer; user-select: none; }
.co-step__num { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: var(--co-border); font-weight: 700; font-size: 1rem; color: var(--co-muted); flex-shrink: 0; transition: background .25s, color .25s; }
.co-step--active .co-step__num { background: var(--co-accent); color: #fff; }
.co-step__num.done { background: var(--co-done); color: #fff; }
.co-step__titles { flex: 1; min-width: 0; }
.co-step__title { margin: 0; font-size: 1rem; font-weight: 700; color: var(--co-text); }
.co-step__summary { margin: .2rem 0 0; font-size: .82rem; color: var(--co-muted); }
.co-step__sub { margin: .15rem 0 0; font-size: .82rem; color: var(--co-muted); }
.co-edit-btn { border: 1px solid var(--co-border); background: transparent; color: var(--co-accent); font-size: .82rem; font-weight: 600; padding: .3rem .75rem; border-radius: 8px; cursor: pointer; transition: background .2s; white-space: nowrap; }
.co-edit-btn:hover { background: #fef2f2; }
.co-step__body { padding: 0 1.25rem 1.25rem; }

/* ── Auth done ── */
.co-auth-done__msg { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--co-text); }

/* ── Mode toggle ── */
.co-mode-toggle { display: flex; gap: .5rem; margin-bottom: 1rem; }
.co-mode-btn { border: 1px solid var(--co-border); background: transparent; padding: .4rem 1rem; border-radius: 8px; cursor: pointer; font-size: .84rem; transition: all .2s; }
.co-mode-btn.active { background: var(--co-accent); color: #fff; border-color: var(--co-accent); }

/* ── Map ── */
.co-map-wrap { margin-bottom: .75rem; }
.co-map { width: 100%; height: 280px; background: #e8eaed; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--co-muted); font-size: .9rem; }
.co-location-btn { margin-top: .5rem; border: 1px solid var(--co-accent); background: transparent; color: var(--co-accent); padding: .4rem 1rem; border-radius: 8px; cursor: pointer; font-size: .84rem; }

/* ── Grid ── */
.co-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
@media (max-width: 540px) { .co-grid2 { grid-template-columns: 1fr; } }

/* ── Form ── */
.co-field { display: flex; flex-direction: column; gap: .3rem; margin-bottom: .75rem; }
.co-label { font-size: .82rem; font-weight: 600; color: var(--co-muted); }
.co-req { color: var(--co-accent); }
.co-input { width: 100%; padding: .65rem .9rem; border: 1.5px solid var(--co-border); border-radius: 8px; font-size: .92rem; background: #fff; color: var(--co-text); transition: border-color .2s; box-sizing: border-box; }
.co-input:focus { outline: none; border-color: var(--co-accent); }
.co-input.err { border-color: var(--co-accent); }
.co-textarea { resize: vertical; min-height: 64px; }
.co-err { font-size: .78rem; color: var(--co-accent); }
.co-msg { font-size: .84rem; padding: .4rem .75rem; border-radius: 6px; margin-bottom: .5rem; }
.co-msg--ok { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.co-msg--err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Phone row ── */
.co-phone-row { display: flex; gap: .5rem; }
.co-dial { min-width: 90px; max-width: 110px; padding: .65rem .5rem; border: 1.5px solid var(--co-border); border-radius: 8px; font-size: .85rem; background: #f8f9fb; }
.co-input--phone { flex: 1; }

/* ── Checkbox ── */
.co-check { display: flex; align-items: flex-start; gap: .5rem; cursor: pointer; font-size: .88rem; margin-bottom: .75rem; line-height: 1.4; }
.co-check input[type=checkbox] { accent-color: var(--co-accent); width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; }
.co-check--terms { margin-top: .75rem; }

/* ── OTP ── */
.co-otp-row { display: flex; justify-content: center; gap: .75rem; margin: 1rem 0; }
.co-otp-box { width: 56px; height: 64px; text-align: center; font-size: 1.6rem; font-weight: 700; border: 2px solid var(--co-border); border-radius: 12px; background: #f8f9fb; transition: border-color .2s, box-shadow .2s; }
.co-otp-box:focus { outline: none; border-color: var(--co-accent); box-shadow: 0 0 0 3px rgba(233,69,96,.12); }
.co-otp-box.err { border-color: var(--co-accent); }
.co-otp-resend { text-align: center; font-size: .82rem; color: var(--co-muted); margin: .5rem 0; }

/* ── Recipient ── */
.co-recipient { background: #f8f9fb; border-radius: 10px; padding: 1rem; margin-bottom: .75rem; border: 1px solid var(--co-border); }

/* ── Buttons ── */
.co-btn { width: 100%; padding: .85rem; background: var(--co-accent); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s, transform .1s; margin-top: .5rem; letter-spacing: .02em; }
.co-btn:hover:not(:disabled) { background: #c73652; transform: translateY(-1px); }
.co-btn:disabled { opacity: .6; cursor: not-allowed; }
.co-btn--sm { width: auto; padding: .6rem 1.2rem; font-size: .85rem; margin: 0; }
.co-btn--pay { background: linear-gradient(135deg, #1a1a2e, #0f3460); font-size: 1.05rem; letter-spacing: .04em; }
.co-btn--pay:hover:not(:disabled) { background: linear-gradient(135deg, #0f3460, #1a1a2e); }
.co-link-btn { background: none; border: none; color: var(--co-accent); font-size: .85rem; cursor: pointer; padding: .25rem 0; display: block; margin: .25rem auto; }
.co-link-btn:hover { text-decoration: underline; }

/* ── Shipping cards ── */
.co-ship-list { display: flex; flex-direction: column; gap: .6rem; margin-bottom: 1rem; }
.co-ship-card { display: flex; align-items: center; gap: .75rem; border: 2px solid var(--co-border); border-radius: 10px; padding: .85rem 1rem; cursor: pointer; transition: border-color .2s, background .2s; }
.co-ship-card.selected { border-color: var(--co-accent); background: #fef2f2; }
.co-ship-logo { width: 42px; height: 42px; object-fit: contain; border-radius: 6px; }
.co-ship-info { flex: 1; display: flex; flex-direction: column; }
.co-ship-name { font-weight: 600; font-size: .92rem; }
.co-ship-time { font-size: .78rem; color: var(--co-muted); margin-top: .1rem; }
.co-ship-price { font-weight: 700; color: var(--co-accent2); font-size: 1rem; }
.co-radio { accent-color: var(--co-accent); width: 18px; height: 18px; }
.co-loading { text-align: center; padding: 1.5rem; color: var(--co-muted); }
.co-empty { text-align: center; padding: 1.5rem; color: var(--co-muted); font-size: .9rem; }

/* ── Payment cards ── */
.co-pay-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .6rem; margin-bottom: 1rem; }
.co-pay-card { display: flex; flex-direction: column; align-items: center; gap: .4rem; border: 2px solid var(--co-border); border-radius: 10px; padding: .85rem .75rem; cursor: pointer; transition: border-color .2s, background .2s; text-align: center; }
.co-pay-card.selected { border-color: var(--co-accent); background: #fef2f2; }
.co-pay-logo { width: 52px; height: 34px; object-fit: contain; }
.co-pay-name { font-size: .82rem; font-weight: 600; color: var(--co-text); }
.co-pay-fee { font-size: .72rem; color: var(--co-muted); }

/* ── Card form ── */
.co-card-form { background: #f8f9fb; border-radius: 10px; padding: 1rem; border: 1px solid var(--co-border); margin-bottom: 1rem; }

/* ── Summary ── */
.co-summary { background: #f8f9fb; border-radius: 10px; padding: 1rem; border: 1px solid var(--co-border); margin: 1rem 0; }
.co-summary__row { display: flex; justify-content: space-between; padding: .4rem 0; font-size: .9rem; border-bottom: 1px solid var(--co-border); }
.co-summary__row:last-child { border-bottom: none; }
.co-summary__row--disc { color: var(--co-done); }
.co-summary__row--total { font-size: 1.05rem; font-weight: 700; color: var(--co-text); }

/* ── Transitions ── */
.co-expand-enter-active, .co-expand-leave-active { transition: max-height .3s ease, opacity .25s ease; max-height: 1200px; overflow: hidden; opacity: 1; }
.co-expand-enter-from, .co-expand-leave-to { max-height: 0; opacity: 0; }
.co-slide-enter-active, .co-slide-leave-active { transition: max-height .3s ease, opacity .2s; overflow: hidden; }
.co-slide-enter-from, .co-slide-leave-to { max-height: 0; opacity: 0; }

/* ── RTL ── */
.co-page.rtl { direction: rtl; }
.co-page.rtl .co-phone-row { flex-direction: row-reverse; }
.co-page.rtl .co-bar__thumbs { flex-direction: row-reverse; }
.co-page.rtl .co-bar__right { flex-direction: row-reverse; }
.co-page.rtl .co-summary__row { flex-direction: row-reverse; }
</style>
