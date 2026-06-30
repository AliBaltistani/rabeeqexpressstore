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
          <div v-if="cashbackMessage" class="checkout-header__cashback">{{ cashbackMessage }}</div>
          <button class="checkout-header__coupon-btn" @click="showCoupon = !showCoupon">{{ $t('checkout.useCoupon') }}</button>
        </div>
      </div>
      <div v-if="showCoupon" class="checkout-coupon container">
        <!-- Loyalty Coupons Quick-Apply -->
        <div v-if="loyaltyCoupons.length" class="checkout-coupon__loyalty">
          <span class="checkout-coupon__loyalty-label">{{ $t('checkout.yourCoupons') || 'Your coupons' }}:</span>
          <div class="checkout-coupon__loyalty-list">
            <button
              v-for="lc in loyaltyCoupons"
              :key="lc.id"
              class="checkout-coupon__loyalty-btn"
              :class="{ 'checkout-coupon__loyalty-btn--shipping': lc.type === 'free_shipping' }"
              @click="quickApplyCoupon(lc.code)"
              :disabled="couponLoading"
            >
              <span class="loyalty-coupon-icon">{{ lc.type === 'free_shipping' ? '🚚' : '🏷️' }}</span>
              <span class="loyalty-coupon-info">
                <span class="loyalty-coupon-name">{{ lc.rewardName || lc.name }}</span>
                <code class="loyalty-coupon-code">{{ lc.code }}</code>
              </span>
            </button>
          </div>
        </div>
        <div class="checkout-coupon__row">
          <input type="text" v-model="couponCode" :placeholder="$t('checkout.enterCouponCode')" class="checkout-coupon__input" />
          <button class="checkout-coupon__apply" @click="applyCoupon" :disabled="couponLoading">{{ $t('checkout.apply') }}</button>
        </div>
        <p v-if="couponMsg" class="checkout-coupon__msg" :class="{ error: couponError }">{{ couponMsg }}</p>
        <p v-if="freeShippingApplied" class="checkout-coupon__free-ship-msg">🚚 {{ $t('checkout.freeShippingApplied') || 'Free shipping coupon applied!' }}</p>
      </div>
      <div class="checkout-header__details-toggle container">
        <button class="checkout-details-btn" @click="showOrderDetails = !showOrderDetails">{{ $t('checkout.orderDetails') }}</button>
      </div>

    <!-- ORDER DETAILS DRAWER -->
    <Teleport to="body">
      <Transition name="drawer-fade">
        <div v-if="showOrderDetails" class="drawer-overlay" @click.self="showOrderDetails = false">
          <Transition name="drawer-slide">
            <div v-if="showOrderDetails" class="drawer-panel">
              <div class="drawer-header">
                <h3 class="drawer-title">{{ $t('checkout.orderDetails') }}</h3>
                <button class="drawer-close" @click="showOrderDetails = false">&times;</button>
              </div>
              <div class="drawer-body">
                <div v-if="cart.items.length === 0" class="drawer-empty">
                  <p>{{ $t('cart.emptyTitle') }}</p>
                </div>
                <div v-else class="drawer-items">
                  <div v-for="item in cart.items" :key="item.id" class="drawer-item">
                    <img :src="item.image || ''" :alt="item.productName" class="drawer-item__img" />
                    <div class="drawer-item__info">
                      <p class="drawer-item__name">{{ item.productName }}</p>
                      <!-- Attributes: interactive variant editor -->
                      <AttributeSelector
                        v-if="item.attributes && item.attributes.length"
                        :attributes="item.attributes"
                        :model-value="drawerSelections[item.id] || {}"
                        :show-update="true"
                        :disabled="updatingDrawerItems[item.id]"
                        :updating="updatingDrawerItems[item.id]"
                        @update:model-value="(v) => { drawerSelections[item.id] = v }"
                        @update="updateItemVariantInDrawer(item)"
                      />
                      <p class="drawer-item__price">{{ item.unitPrice?.formatted || '' }}</p>
                      <div class="drawer-item__qty">
                        <button class="qty-btn" @click="updateCartQty(item, -1)" :disabled="item.quantity <= 1">−</button>
                        <span class="qty-val">{{ item.quantity }}</span>
                        <button class="qty-btn" @click="updateCartQty(item, 1)">+</button>
                        <button class="qty-remove" @click="removeCartItem(item)">🗑</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="drawer-footer">
                <div class="drawer-total-row"><span>{{ $t('cart.total') }}</span><strong>{{ cart.total?.formatted || '' }}</strong></div>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
    </div>

    <!-- MAIN BODY -->
    <div class="container checkout-body">

      <!-- ═══ STEP 1: Login / Register / Guest ═══ -->
      <section class="checkout-section">
        <div class="section-header">
          <div class="section-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
          </div>
          <div class="section-title-wrap">
            <template v-if="currentStep > 1">
              <h2 class="section-title">{{ $t('checkout.welcome', { name: welcomeName }) }}</h2>
              <p class="section-subtitle">{{ welcomePhone }}</p>
            </template>
            <template v-else-if="authMode === 'guest'">
              <h2 class="section-title">{{ $t('checkout.welcomeGuest') }}</h2>
              <p class="section-subtitle">{{ $t('checkout.guestSubtitle') }}</p>
            </template>
            <template v-else>
              <h2 class="section-title">{{ $t('checkout.loginRegister') }}</h2>
              <p class="section-subtitle">{{ $t('checkout.loginSubtitle') }}</p>
            </template>
          </div>
          <button v-if="currentStep === 1 && authMode !== 'guest' && settings.storeSettings.features.guestCheckout" class="section-side-link" @click="authMode = 'guest'">{{ $t('checkout.purchaseAsGuest') }}</button>
          <button v-if="currentStep > 1" class="edit-btn" @click="currentStep = 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /><path d="M19.5 7.125L16.862 4.487" /></svg> {{ $t('checkout.edit') }}</button>
        </div>

        <div v-if="currentStep === 1" class="section-content">
          <!-- OTP Login flow (email / phone / both) -->
          <template v-if="authMode === 'login'">

            <!-- ─── Channel Tabs (only shown in 'both' mode) ─── -->
            <div v-if="otpMode === 'both' && otpStep === 'email'" class="checkout-otp-tabs">
              <button
                :class="['checkout-otp-tab', { active: otpChannel === 'email' }]"
                type="button"
                @click="switchOtpChannel('email')"
              >{{ $t('auth.email') }}</button>
              <button
                :class="['checkout-otp-tab', { active: otpChannel === 'phone' }]"
                type="button"
                @click="switchOtpChannel('phone')"
              >{{ $t('auth.phone') }}</button>
            </div>

            <!-- ─── Input Step: Email or Phone ─── -->
            <template v-if="otpStep === 'email'">
              <!-- Email input -->
              <div v-if="otpChannel === 'email'" class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.emailAddress') }}</label>
                <input type="email" v-model="otpEmail" class="checkout-input" :class="{ 'input-error': errors.otpEmail }" :placeholder="$t('loginModal.emailPlaceholder')" @keydown.enter.prevent="handleEmailEnter" />
                <span v-if="errors.otpEmail" class="field-error">{{ errors.otpEmail }}</span>
              </div>
              <!-- Phone input -->
              <div v-else class="checkout-field">
                <label class="checkout-label">{{ $t('auth.phone') }}</label>
                <PhoneInput
                  v-model="otpPhone"
                  v-model:countryCode="otpPhoneCode"
                  :error="!!errors.otpPhone"
                  placeholder="501234567"
                  @enter="handleEmailEnter"
                />
                <span v-if="errors.otpPhone" class="field-error">{{ errors.otpPhone }}</span>
              </div>
              <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
              <button class="checkout-btn checkout-btn--dark" :disabled="authLoading" @click="handleEmailEnter">{{ authLoading ? $t('common.loading') : $t('checkout.enter') }}</button>
            </template>

            <!-- ─── OTP Code Verification ─── -->
            <template v-else-if="otpStep === 'code'">
              <div class="otp-verify-block">
                <button class="otp-back-btn" @click="otpStep = 'email'">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </button>
                <div class="otp-verify-info">
                  <p class="otp-verify-text">{{ $t('checkout.verificationRequired') }}</p>
                  <p class="otp-verify-email">{{ otpChannel === 'phone' ? otpPhone : otpEmail }}</p>
                </div>
              </div>
              <div class="checkout-otp-row">
                <input v-for="(_, idx) in 4" :key="idx" :ref="(el) => { if (el) checkoutOtpRefs[idx] = el as HTMLInputElement }" type="text" inputmode="numeric" maxlength="1" class="checkout-otp-box" :class="{ 'input-error': errors.otpCode }" :value="otpCodeDigits[idx]" @input="handleOtpInput(idx, $event)" @keydown.backspace="handleOtpBackspace(idx, $event)" @paste="handleOtpPaste($event)" />
              </div>
              <span v-if="errors.otpCode" class="field-error" style="text-align:center;display:block;">{{ errors.otpCode }}</span>
              <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
              <button class="checkout-btn checkout-btn--dark" :disabled="authLoading || otpCodeDigits.join('').length < 4" @click="handleVerifyOtp">{{ authLoading ? $t('common.loading') : $t('loginModal.verify') }}</button>
              <p class="otp-resend-text">
                <template v-if="otpResendCooldown > 0">{{ $t('checkout.resendAfter') }} <strong>{{ String(Math.floor(otpResendCooldown/60)).padStart(2,'0') }} : {{ String(otpResendCooldown%60).padStart(2,'0') }}</strong></template>
                <button v-else class="section-side-link" @click="handleSendOtp" :disabled="authLoading">{{ $t('loginModal.resendCode') }}</button>
              </p>
            </template>

            <!-- ─── Register form (new email users) ─── -->
            <template v-else-if="otpStep === 'register'">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('auth.name') }}</label>
                <input type="text" v-model="registerForm.name" class="checkout-input" :class="{ 'input-error': errors.regName }" :placeholder="$t('checkout.firstName')" />
                <span v-if="errors.regName" class="field-error">{{ errors.regName }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.lastName') }}</label>
                <input type="text" v-model="registerForm.lastName" class="checkout-input" :class="{ 'input-error': errors.regLastName }" :placeholder="$t('checkout.lastName')" />
                <span v-if="errors.regLastName" class="field-error">{{ errors.regLastName }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.mobileNumber') || 'Mobile Number' }}</label>
                <PhoneInput
                  v-model="registerForm.phone"
                  v-model:countryCode="registerCountryCode"
                  placeholder="501234567"
                />
              </div>
              <label class="checkout-checkbox">
                <input type="checkbox" v-model="joinMailingList" />
                <span>{{ $t('checkout.joinMailingList') || 'I would like to join the mailing list to receive promotional and advertising campaigns' }}</span>
              </label>
              <p v-if="authError" class="auth-error-msg">{{ authError }}</p>
              <button class="checkout-btn checkout-btn--dark" :disabled="authLoading" @click="handleRegister">{{ authLoading ? $t('common.loading') : $t('auth.register') }}</button>
              <p class="checkout-alt-text">{{ $t('checkout.registerAgreement') || 'By completing registration, you agree to' }} <router-link to="/privacy-policy">{{ $t('checkout.privacyPolicy') || 'Privacy Policy' }}</router-link></p>
            </template>
          </template>

          <!-- Guest Form -->
          <form v-if="authMode === 'guest'" @submit.prevent="handleGuest" class="checkout-auth-form">
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.firstName') }} <span class="req">*</span></label>
                <input type="text" v-model="guestForm.firstName" class="checkout-input" :class="{ 'input-error': errors.gFirstName }" :placeholder="$t('checkout.firstName')" />
                <span v-if="errors.gFirstName" class="field-error">{{ errors.gFirstName }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.lastName') }} <span class="req">*</span></label>
                <input type="text" v-model="guestForm.lastName" class="checkout-input" :class="{ 'input-error': errors.gLastName }" :placeholder="$t('checkout.lastName')" />
                <span v-if="errors.gLastName" class="field-error">{{ errors.gLastName }}</span>
              </div>
            </div>
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.email') }} <span class="req">*</span></label>
                <input type="email" v-model="guestForm.email" class="checkout-input" :class="{ 'input-error': errors.gEmail }" placeholder="example@mail.com" />
                <span v-if="errors.gEmail" class="field-error">{{ errors.gEmail }}</span>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
                <PhoneInput
                  v-model="guestForm.phone"
                  v-model:countryCode="guestCountryCode"
                  :error="!!errors.gPhone"
                  placeholder="501234567"
                />
                <span v-if="errors.gPhone" class="field-error">{{ errors.gPhone }}</span>
              </div>
            </div>
            <button type="submit" class="checkout-btn checkout-btn--light">{{ $t('checkout.continueAsGuest') }}</button>
            <p class="checkout-alt-text"><span>⏰</span> {{ $t('checkout.alreadyHaveAccount') }} <a href="#" @click.prevent="authMode = 'login'">{{ $t('auth.login') }}</a></p>
          </form>
        </div>
      </section>

      <div class="section-divider"></div>

      <!-- ═══ STEP 2: Shipping Address ═══ -->
      <section class="checkout-section" :class="{ locked: currentStep < 2 }">
        <div class="section-header">
          <div class="section-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
          </div>
          <div class="section-title-wrap">
            <h2 class="section-title">{{ $t('checkout.shippingAddress') }}</h2>
            <p class="section-subtitle" v-if="currentStep > 2">{{ addressSummary }}</p>
            <p class="section-subtitle" v-else>{{ $t('checkout.ensureAddress') }}</p>
          </div>
          <button v-if="currentStep > 2" class="edit-btn" @click="currentStep = 2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg> {{ $t('checkout.edit') }}</button>
        </div>
        <div v-if="currentStep === 2" class="section-content">
          <!-- MAP MODE (auto-geocodes address, country, city) -->
          <div v-if="addressMode === 'map'" class="address-map-mode">
            <div class="map-container">
              <div class="map-search-overlay">
                <input type="text" ref="mapSearchInput" class="map-search-input" :placeholder="$t('checkout.searchAddress') || 'Search for an address...'" />
                <p class="map-search-hint">{{ $t('checkout.shortAddressHint') || 'Short address must be 4 letters followed by 4 digits (e.g. ABCD1234)' }}</p>
              </div>
              <div ref="mapContainer" class="google-map"></div>
              <button class="current-location-btn" @click="getCurrentLocation" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16"><path d="M12 2v2m0 16v2M2 12h2m16 0h2m-5 0a5 5 0 11-10 0 5 5 0 0110 0z"/></svg>
                {{ $t('checkout.currentLocation') || 'Current location' }}
              </button>
            </div>
            <!-- Name + Phone in map mode -->
            <div class="checkout-field">
              <label class="checkout-label checkout-label--colored">{{ $t('checkout.name') }} <span class="req">*</span></label>
              <input type="text" v-model="addressFullName" class="checkout-input" :class="{ 'input-error': errors.addrName }" :placeholder="$t('checkout.firstName') + ' ' + $t('checkout.lastName')" />
              <span v-if="errors.addrName" class="field-error">{{ errors.addrName }}</span>
            </div>
            <div class="checkout-field">
              <label class="checkout-label checkout-label--colored">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
              <PhoneInput v-model="addrPhoneNum" v-model:countryCode="addrPhoneCode" :error="!!errors.addrPhone" placeholder="501234567" />
              <span v-if="errors.addrPhone" class="field-error">{{ errors.addrPhone }}</span>
            </div>
            <p v-if="addressError" class="auth-error-msg">{{ addressError }}</p>
            <button class="checkout-btn checkout-btn--dark" :disabled="shippingLoading" @click="submitAddress">{{ shippingLoading ? $t('common.loading') : $t('checkout.save') }}</button>
            <button class="toggle-address-mode" @click="addressMode = 'manual'" type="button">{{ $t('checkout.enterManually') || 'Enter The Address Manually' }}</button>
          </div>
          <!-- MANUAL MODE — simplified: Name, Phone, Address -->
          <div v-else class="address-manual-mode">
            <form @submit.prevent="submitAddress">
              <!-- Full Name -->
              <div class="checkout-field">
                <label class="checkout-label checkout-label--colored">{{ $t('checkout.name') }} <span class="req">*</span></label>
                <input type="text" v-model="addressFullName" class="checkout-input" :class="{ 'input-error': errors.addrName }" :placeholder="$t('checkout.firstName') + ' ' + $t('checkout.lastName')" />
                <span v-if="errors.addrName" class="field-error">{{ errors.addrName }}</span>
              </div>
              <!-- Phone Number -->
              <div class="checkout-field">
                <label class="checkout-label checkout-label--colored">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
                <PhoneInput
                  v-model="addrPhoneNum"
                  v-model:countryCode="addrPhoneCode"
                  :error="!!errors.addrPhone"
                  placeholder="501234567"
                />
                <span v-if="errors.addrPhone" class="field-error">{{ errors.addrPhone }}</span>
              </div>
              <!-- Address -->
              <div class="checkout-field">
                <label class="checkout-label checkout-label--colored">{{ $t('checkout.address') }} <span class="req">*</span></label>
                <input type="text" v-model="addressForm.street" class="checkout-input" :class="{ 'input-error': errors.addrStreet }" :placeholder="$t('checkout.addressPlaceholder')" />
                <span v-if="errors.addrStreet" class="field-error">{{ errors.addrStreet }}</span>
              </div>
              <p v-if="addressError" class="auth-error-msg">{{ addressError }}</p>
              <button type="submit" class="checkout-btn checkout-btn--dark" :disabled="shippingLoading">{{ shippingLoading ? $t('common.loading') : $t('checkout.save') }}</button>
            </form>
            <button class="toggle-address-mode" @click="addressMode = 'map'" type="button">{{ $t('checkout.locateOnMap') || 'Locate Your Address From The Map' }}</button>
          </div>
        </div>
      </section>

      <div class="section-divider"></div>

      <!-- ═══ STEP 3: Shipping Company ═══ -->
      <section class="checkout-section" :class="{ locked: currentStep < 3 }">
        <div class="section-header">
          <div class="section-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-3.375m-9.375 0V6.375c0-.621.504-1.125 1.125-1.125h7.5c.621 0 1.125.504 1.125 1.125v8.625m-9.75 0h9.75"/></svg>
          </div>
          <div class="section-title-wrap">
            <h2 class="section-title">{{ $t('checkout.shippingCompany') }}</h2>
            <p class="section-subtitle" v-if="currentStep > 3">{{ selectedShipping?.name }}<template v-if="selectedShipping?.estimated_delivery || selectedShipping?.time">, {{ selectedShipping?.estimated_delivery || selectedShipping?.time }}</template></p>
            <p class="section-subtitle" v-else>{{ $t('checkout.selectShipping') }}</p>
          </div>
          <button v-if="currentStep > 3" class="edit-btn" @click="currentStep = 3"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg> {{ $t('checkout.edit') }}</button>
        </div>
        <div v-if="currentStep === 3" class="section-content">
          <div v-if="shippingLoading" class="empty-state"><p>{{ $t('common.loading') }}...</p></div>
          <div v-else-if="shippingRatesFetched && shippingOptions.length === 0" class="empty-state">
            <p>{{ $t('checkout.noShippingRates') }}</p>
            <button class="checkout-btn checkout-btn--dark" @click="currentStep = 5; loadPaymentMethods()">{{ $t('search.next') || 'Next' }} →</button>
          </div>
          <div v-else class="shipping-options">
            <label v-for="opt in shippingOptions" :key="opt.id" class="shipping-card" :class="{ selected: selectedShippingId === opt.id }">
              <input type="radio" name="shipping" :value="opt.id" v-model="selectedShippingId" class="checkout-radio" />
              <img v-if="opt.logo || opt.image" :src="opt.logo || opt.image" class="shipping-logo" :alt="opt.name" />
              <div class="shipping-info">
                <span class="shipping-name">{{ opt.name }}</span>
                <span class="shipping-time" v-if="opt.estimated_delivery || opt.time">{{ opt.estimated_delivery || opt.time }}</span>
              </div>
              <span class="shipping-price">{{ typeof opt.price === 'object' ? (opt.price as any).formatted : opt.price }}</span>
            </label>
          </div>
          <span v-if="errors.shipping" class="field-error">{{ errors.shipping }}</span>
          <button v-if="shippingOptions.length > 0" class="checkout-btn checkout-btn--dark" @click="submitShipping">{{ $t('checkout.confirmShipping') }}</button>
        </div>
      </section>

<!-- Step 4 removed — phone captured in Step 2 -->

      <div class="section-divider"></div>

      <!-- ═══ STEP 5: Payment ═══ -->
      <section class="checkout-section" :class="{ locked: currentStep < 5 }">
        <div class="section-header">
          <div class="section-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
          </div>
          <div class="section-title-wrap">
            <h2 class="section-title">{{ $t('checkout.payment') }}</h2>
            <p class="section-subtitle">{{ selectedPaymentName }}</p>
          </div>
        </div>
        <div v-if="currentStep === 5" class="section-content">
          <div v-if="paymentMethodsLoading" class="payment-shimmer-grid">
            <div class="payment-shimmer-card" v-for="i in 3" :key="i"><div class="shimmer-bar"></div></div>
          </div>
          <div v-else class="payment-methods-grid">
            <label v-for="pm in activePaymentMethods" :key="pm.id" class="payment-card" :class="{ selected: selectedPayment === pm.id }">
              <input type="radio" name="payment" :value="pm.id" v-model="selectedPayment" class="checkout-radio" />
              <img v-if="pm.icon || pm.logo" :src="pm.icon || pm.logo" class="payment-logo" :alt="pm.name" />
              <span class="payment-name-text">{{ pm.name }}</span>
            </label>
          </div>
          <span v-if="errors.payment" class="field-error">{{ errors.payment }}</span>

          <!-- Stripe Payment Element (handles Cards, Apple Pay, Google Pay, Link) -->
          <div v-if="selectedPayment === 'stripe'" class="card-details-form">
            <div v-if="stripeElementMounting" class="stripe-element-loading">
              <div class="stripe-element-spinner"></div>
              <span>{{ $t('common.loading') }}...</span>
            </div>
            <div id="stripe-payment-element" class="stripe-payment-mount" :class="{ 'stripe-hidden': stripeElementMounting }"></div>
            <p v-if="stripeError" class="field-error" style="margin-top:0.5rem;">{{ stripeError }}</p>
          </div>

          <!-- Bank Transfer / COD Info -->
          <div v-if="selectedPayment === 'bank_transfer'" class="card-details-form"><p class="card-note">{{ $t('checkout.bankTransferNote') }}</p></div>
          <div v-if="selectedPayment === 'cod'" class="card-details-form"><p class="card-note">{{ $t('checkout.codNote') || 'Pay cash when your order is delivered.' }}</p></div>

          <!-- Terms -->
          <label class="checkout-checkbox checkout-checkbox--terms">
            <input type="checkbox" v-model="agreeTerms" />
            <span>{{ $t('checkout.agreeTerms') }}</span>
          </label>
          <span v-if="errors.terms" class="field-error">{{ errors.terms }}</span>

          <button class="checkout-btn checkout-btn--dark" @click="confirmPayment" :disabled="orderLoading">{{ orderLoading ? $t('common.loading') : $t('checkout.confirmPayment') }}</button>
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
import { fetchDynamicShippingMethods, placeOrder, fetchPaymentMethods, createStripePaymentIntent, confirmStripePayment, cancelStripeOrder, fetchActiveCountries, updateProfile, fetchMyCoupons, fetchAddresses, addAddress } from '@/api/services'
import { useI18n } from 'vue-i18n'
import PhoneInput from '@/components/common/PhoneInput.vue'
import { useCountries } from '@/composables/useCountries'
import AttributeSelector from '@/components/product/AttributeSelector.vue'

const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const settings = useSettingsStore()
const { t } = useI18n()
const { countries: countriesList } = useCountries()   // shared — loaded once, used for country lookup

// ── Drawer variant editor state ────────────────────────────────────────────
const drawerSelections = reactive<Record<number, Record<number, number>>>({})
const updatingDrawerItems = reactive<Record<number, boolean>>({})

function initDrawerSelections(items: typeof cart.items) {
  for (const item of items) {
    if (!item.attributes?.length) continue
    drawerSelections[item.id] = {}
    for (const group of item.attributes) {
      const match = group.values?.find(
        (v: any) => item.selectedAttributeValues?.includes(v.id)
      )
      drawerSelections[item.id][group.id] = Number(match?.id ?? group.values?.[0]?.id ?? 0)
    }
  }
}

async function updateItemVariantInDrawer(item: any) {
  if (updatingDrawerItems[item.id]) return
  const groupSelections = drawerSelections[item.id]
  if (!groupSelections) return
  const attrValues = Object.values(groupSelections).filter(Boolean).map(v => Number(v))
  updatingDrawerItems[item.id] = true
  try {
    await cart.updateItemAttributes(item.id, item.productId, item.quantity, attrValues)
    initDrawerSelections(cart.items)
  } catch (e) {
    console.error('Failed to update drawer variant:', e)
  } finally {
    updatingDrawerItems[item.id] = false
  }
}

// ── Core State ──
const currentStep = ref(1)
const showCoupon = ref(false)
const showOrderDetails = ref(false)
const couponCode = ref('')
const couponLoading = ref(false)
const couponMsg = ref('')
const couponError = ref(false)
const loyaltyCoupons = ref<any[]>([])
const freeShippingApplied = ref(false)
const authMode = ref<'login' | 'guest'>('login')
const authLoading = ref(false)
const authError = ref('')
const errors = reactive<Record<string, string>>({})
const addressMode = ref<'map' | 'manual'>('map')

// ── Auth Forms ──
const otpEmail = ref('')
const otpPhone = ref('')
const otpPhoneCode = ref('+966')
const otpChannel = ref<'email' | 'phone'>('email') // active OTP channel
const otpMode = computed(() => settings.storeSettings.features.otpMode)
const otpStep = ref<'email' | 'code' | 'register'>('email')
const otpCodeDigits = ref(['', '', '', ''])
const checkoutOtpRefs = ref<HTMLInputElement[]>([])
const otpResendCooldown = ref(0)
let otpCooldownTimer: ReturnType<typeof setInterval> | null = null
const registerForm = ref({ name: '', lastName: '', phone: '', email: '', password: '', password_confirmation: '' })
const registerCountryCode = ref('+966')
const joinMailingList = ref(true)
const loginForm = ref({ email: '', password: '' })
const guestForm = ref({ firstName: '', lastName: '', email: '', phone: '' })
const guestCountryCode = ref('+966')

// ── Address ──
const addressForm = ref({ firstName: '', lastName: '', phone: '', country: '', state: '', city: '', district: '', street: '', postalCode: '', buildingNo: '', buildingDesc: '' })
const addressFullName   = ref('')        // simple full-name field (split to first/last on submit)
const addrPhoneNum      = ref('')        // digit-only part of the address phone
const addrPhoneCode     = ref('+966')    // country dialling code (also drives shipping country)
const addressError = ref('')
const shippingLoading = ref(false)
const shippingRatesFetched = ref(false)
const deliverToOther = ref(false)
const recipientForm = ref({ name: '', phone: '', email: '' })
const recipientCountryCode = ref('+966')
const smsUpdates = ref(false)
const addrCountryCode = ref('+966')
const additionalPhone = ref('')


// ── Shipping ──
const shippingOptions = ref<any[]>([])
const selectedShippingId = ref<number | null>(null)
const selectedShipping = computed(() => shippingOptions.value.find((s: any) => s.id === selectedShippingId.value))

// ── Payment ──
const selectedPayment = ref('')
const selectedPaymentName = computed(() => { const pm = dynamicPaymentMethods.value.find((p: any) => p.id === selectedPayment.value); return pm?.name || '' })
const activePaymentMethods = computed(() => dynamicPaymentMethods.value.filter((pm: any) => pm.enabled))
const dynamicPaymentMethods = ref<any[]>([])
const paymentMethodsLoading = ref(false)
const agreeTerms = ref(false)
const orderLoading = ref(false)
const orderError = ref('')
let stripeInstance: any = null
let stripeElements: any = null        // Stripe Elements instance (intent-first)
let stripePaymentElement: any = null  // The mounted Payment Element
const stripeError = ref('')
const stripeElementMounting = ref(false)
const stripePublishableKey = ref('')
let stripeClientSecret = ''           // Cached PI clientSecret for confirmPayment reuse

// ── Google Maps ──
const mapContainer = ref<HTMLElement | null>(null)
const mapSearchInput = ref<HTMLInputElement | null>(null)
let googleMap: any = null
let googleMarker: any = null
let autocomplete: any = null

// ── Country helpers (kept for backward compat with phone composition) ──
const selectedGuestCountry = computed(() => countriesList.value.find((c: any) => c.phone_code === guestCountryCode.value))
const selectedAddrCountry = computed(() => countriesList.value.find((c: any) => c.phone_code === addrCountryCode.value))
const selectedRecipientCountry = computed(() => countriesList.value.find((c: any) => c.phone_code === recipientCountryCode.value))

// ── Computed ──
const cashbackMessage = computed(() => { return '' })
const welcomeName = computed(() => { if (auth.isAuthenticated && auth.user) { return auth.user.name } if (authMode.value === 'guest') { return `${guestForm.value.firstName} ${guestForm.value.lastName}` } return '' })
const welcomePhone = computed(() => { if (auth.isAuthenticated && auth.user) return auth.user.phone || ''; return guestForm.value.phone ? `${guestCountryCode.value}${guestForm.value.phone}` : '' })
const addressSummary = computed(() => {
  const name = addressFullName.value || [addressForm.value.firstName, addressForm.value.lastName].filter(Boolean).join(' ')
  const phone = addrPhoneCode.value + addrPhoneNum.value
  const addr = addressForm.value.street
  return [name, phone, addr].filter(Boolean).join(' — ') || ''
})

// ── Helpers ──
function clearErrors() { Object.keys(errors).forEach(k => delete errors[k]) }
function isEmail(v: string) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) }
// Country picker now uses native <select> dropdowns — no modal needed

// ── Drawer cart helpers ──
async function updateCartQty(item: any, delta: number) {
  const newQty = item.quantity + delta
  if (newQty < 1) return
  await cart.updateQuantity(item.id, newQty)
}
async function removeCartItem(item: any) {
  await cart.removeItem(item.id)
}

// ── Step 1: OTP helpers — channel-aware ──
function switchOtpChannel(ch: 'email' | 'phone') {
  otpChannel.value = ch
  authError.value = ''
  clearErrors()
}

async function handleEmailEnter() {
  clearErrors(); authError.value = ''
  let result: any

  try {
    if (otpChannel.value === 'phone' || otpMode.value === 'phone') {
      // Phone OTP flow
      if (!otpPhone.value.trim()) { errors.otpPhone = t('checkout.required'); return }
      authLoading.value = true
      const fullPhone = normalizePhoneNumber(otpPhoneCode.value, otpPhone.value)
      if (!fullPhone) {
        errors.otpPhone = 'Invalid phone number format'
        authLoading.value = false
        return
      }
      result = await auth.sendPhoneOtp(fullPhone)
    } else {
      // Email OTP flow
      if (!otpEmail.value.trim()) { errors.otpEmail = t('checkout.required'); return }
      if (!isEmail(otpEmail.value)) { errors.otpEmail = t('checkout.invalidEmail'); return }
      authLoading.value = true
      registerForm.value.email = otpEmail.value
      result = await auth.sendOtp(otpEmail.value)
    }

    authLoading.value = false
    if (result.success) {
      otpStep.value = 'code'
      otpCodeDigits.value = ['', '', '', '']
      startOtpCooldown((result as any).cooldown || 60)
      nextTick(() => checkoutOtpRefs.value[0]?.focus())
    } else {
      const errKey = otpChannel.value === 'phone' ? 'otpPhone' : 'otpEmail'
      authError.value = (result as any).message || t('common.error')
    }
  } catch (error: any) {
    authLoading.value = false
    authError.value = error.message || t('common.error')
  }
}

async function handleSendOtp() {
  clearErrors(); authError.value = ''
  authLoading.value = true
  let result: any

  try {
    if (otpChannel.value === 'phone' || otpMode.value === 'phone') {
      const fullPhone = normalizePhoneNumber(otpPhoneCode.value, otpPhone.value)
      if (!fullPhone) {
        authError.value = 'Invalid phone number format'
        authLoading.value = false
        return
      }
      result = await auth.sendPhoneOtp(fullPhone)
    } else {
      result = await auth.sendOtp(otpEmail.value)
    }
    authLoading.value = false
    if (result.success) {
      otpCodeDigits.value = ['', '', '', '']
      startOtpCooldown((result as any).cooldown || 60)
      nextTick(() => checkoutOtpRefs.value[0]?.focus())
    } else { authError.value = (result as any).message || t('common.error') }
  } catch (error: any) {
    authLoading.value = false
    authError.value = error.message || t('common.error')
  }
}

function normalizePhoneNumber(countryCode: string, phone: string): string | null {
  // Remove leading zeros and whitespace
  const cleanPhone = phone.trim().replace(/^0+/, '')

  // Ensure country code has + prefix
  const code = countryCode.startsWith('+') ? countryCode : '+' + countryCode

  // Combine
  const fullPhone = code + cleanPhone

  // Validate E.164 format: +[1-9]d{1,14}
  if (!/^\+[1-9]\d{1,14}$/.test(fullPhone)) {
    return null
  }

  return fullPhone
}

async function handleVerifyOtp() {
  clearErrors(); authError.value = ''
  const code = otpCodeDigits.value.join('')
  if (code.length < 4) { errors.otpCode = t('loginModal.invalidOtp'); return }
  authLoading.value = true
  let result: any

  try {
    if (otpChannel.value === 'phone' || otpMode.value === 'phone') {
      const fullPhone = normalizePhoneNumber(otpPhoneCode.value, otpPhone.value)
      if (!fullPhone) {
        errors.otpCode = 'Invalid phone number format'
        authLoading.value = false
        return
      }
      result = await auth.verifyPhoneOtp(fullPhone, code)
    } else {
      result = await auth.verifyOtp(otpEmail.value, code)
    }
    authLoading.value = false
    if (result.success) {
      if ((result as any).isNewUser) { otpStep.value = 'register'; return }
      // Refresh cart after auth (backend migrates guest cart items)
      await cart.loadCart()
      prefillAddressFromUser()
      currentStep.value = 2
    } else {
      authError.value = (result as any).message || t('loginModal.invalidOtp')
      otpCodeDigits.value = ['', '', '', '']
      nextTick(() => checkoutOtpRefs.value[0]?.focus())
    }
  } catch (error: any) {
    authLoading.value = false
    authError.value = error.message || t('common.error')
  }
}

function handleOtpInput(idx: number, event: Event) {
  const target = event.target as HTMLInputElement; const val = target.value.replace(/\D/g, '')
  otpCodeDigits.value[idx] = val.slice(-1)
  if (val && idx < 3) nextTick(() => checkoutOtpRefs.value[idx + 1]?.focus())
  if (otpCodeDigits.value.join('').length === 4) handleVerifyOtp()
}
function handleOtpBackspace(idx: number, event: KeyboardEvent) {
  if (!otpCodeDigits.value[idx] && idx > 0) { event.preventDefault(); otpCodeDigits.value[idx - 1] = ''; nextTick(() => checkoutOtpRefs.value[idx - 1]?.focus()) }
}
function handleOtpPaste(event: ClipboardEvent) {
  event.preventDefault(); const paste = event.clipboardData?.getData('text')?.replace(/\D/g, '') || ''
  for (let i = 0; i < 4; i++) otpCodeDigits.value[i] = paste[i] || ''
  if (paste.length >= 4) nextTick(() => handleVerifyOtp())
}
function startOtpCooldown(seconds: number) {
  otpResendCooldown.value = seconds
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
  otpCooldownTimer = setInterval(() => { otpResendCooldown.value--; if (otpResendCooldown.value <= 0 && otpCooldownTimer) { clearInterval(otpCooldownTimer); otpCooldownTimer = null } }, 1000)
}

async function handleRegister() {
  clearErrors(); authError.value = ''
  if (!registerForm.value.name.trim()) errors.regName = t('checkout.required')
  if (!registerForm.value.lastName.trim()) errors.regLastName = t('checkout.required')
  if (Object.keys(errors).length) return
  authLoading.value = true
  try {
    // User was already created by verifyOtp — just update their profile name/phone
    const fullName = `${registerForm.value.name} ${registerForm.value.lastName}`.trim()
    const phone = registerForm.value.phone ? `${registerCountryCode.value}${registerForm.value.phone}` : ''
    await updateProfile({ name: fullName, phone: phone || undefined })
    // Refresh user data in auth store
    await auth.fetchUser()
    // Refresh cart after registration (backend migrates guest cart items)
    await cart.loadCart()
    prefillAddressFromUser()
    currentStep.value = 2
  } catch (e: any) { authError.value = e.response?.data?.message || t('common.error') }
  finally { authLoading.value = false }
}

function handleGuest() {
  clearErrors()
  if (!guestForm.value.firstName.trim()) errors.gFirstName = t('checkout.required')
  if (!guestForm.value.lastName.trim()) errors.gLastName = t('checkout.required')
  if (!guestForm.value.email.trim()) errors.gEmail = t('checkout.required')
  else if (!isEmail(guestForm.value.email)) errors.gEmail = t('checkout.invalidEmail')
  if (!guestForm.value.phone.trim()) errors.gPhone = t('checkout.required')
  if (Object.keys(errors).length) return

  // Prefill the legacy addressForm (used by order payload builder)
  addressForm.value.firstName = guestForm.value.firstName
  addressForm.value.lastName  = guestForm.value.lastName
  addressForm.value.phone     = guestCountryCode.value + guestForm.value.phone
  additionalPhone.value       = addressForm.value.phone

  // Prefill the new simplified Step 2 form refs
  addressFullName.value = `${guestForm.value.firstName} ${guestForm.value.lastName}`.trim()
  addrPhoneNum.value    = guestForm.value.phone
  addrPhoneCode.value   = guestCountryCode.value

  currentStep.value = 2
}


function prefillAddressFromUser() {
  if (!auth.user) return
  const name = auth.user.name || ''
  const parts = name.split(' ')
  addressForm.value.firstName = parts[0] || ''
  addressForm.value.lastName  = parts.slice(1).join(' ') || ''
  addressFullName.value       = name

  // Parse phone using countries list (longest-first) to avoid greedy-regex bug
  const rawPhone = auth.user.phone || ''
  if (rawPhone && countriesList.value.length) {
    const sorted = [...countriesList.value].sort((a: any, b: any) => b.phone_code.length - a.phone_code.length)
    let matched = false
    for (const c of sorted) {
      if (rawPhone.startsWith(c.phone_code)) {
        addrPhoneCode.value = c.phone_code
        addrPhoneNum.value  = rawPhone.slice(c.phone_code.length)
        matched = true
        break
      }
    }
    if (!matched) addrPhoneNum.value = rawPhone
  } else {
    addrPhoneNum.value = rawPhone
  }
  addressForm.value.phone = rawPhone
  additionalPhone.value   = rawPhone

  // Load saved default address to pre-fill street + city (vice-versa from profile)
  fetchAddresses().then((addresses: any[]) => {
    const def = addresses.find((a: any) => a.isDefault) || addresses[0] || null
    if (def) {
      if (def.addressLine1 && def.addressLine1 !== '-') {
        addressForm.value.street = def.addressLine1
      }
      if (def.city && def.city !== '-') {
        addressForm.value.city = def.city
      }
      if (def.country && def.country !== '-') {
        addressForm.value.country = def.country
      }
      // Override phone from saved address if present and profile has no phone
      if (!rawPhone && def.phone) {
        const sorted = [...countriesList.value].sort((a: any, b: any) => b.phone_code.length - a.phone_code.length)
        for (const c of sorted) {
          if (def.phone.startsWith(c.phone_code)) {
            addrPhoneCode.value = c.phone_code
            addrPhoneNum.value  = def.phone.slice(c.phone_code.length)
            break
          }
        }
      }
    }
  }).catch(() => { /* non-blocking */ })
}

// ── Step 2: Address ──
async function submitAddress() {
  clearErrors(); addressError.value = ''

  // ── Validate simplified fields ──
  if (!addressFullName.value.trim()) { errors.addrName = t('checkout.required') }
  if (!addrPhoneNum.value.trim())    { errors.addrPhone = t('checkout.required') }
  if (addressMode.value === 'manual' && !addressForm.value.street.trim()) {
    errors.addrStreet = t('checkout.required')
  }
  if (Object.keys(errors).length) return

  // ── Resolve name parts ──
  const nameParts = addressFullName.value.trim().split(/\s+/)
  addressForm.value.firstName = nameParts[0] || addressFullName.value.trim()
  addressForm.value.lastName  = nameParts.slice(1).join(' ') || '-'

  // ── Resolve phone (full E.164) ──
  const fullPhone = (addrPhoneCode.value + addrPhoneNum.value.replace(/^0+/, '')).trim()
  addressForm.value.phone = fullPhone
  additionalPhone.value   = fullPhone   // used in order payload — no separate Step 4 needed

  // ── Derive country from selected dialling code ──
  const matchedCountry = countriesList.value.find((c: any) => c.phone_code === addrPhoneCode.value)
  const derivedCountry = matchedCountry?.name ?? addressForm.value.country ?? ''
  addressForm.value.country = derivedCountry

  if (addressMode.value === 'map') {
    // Map mode: ensure geocoding filled in address
    if (!addressForm.value.street && !addressForm.value.city) {
      addressError.value = t('checkout.selectLocationOnMap') || 'Please pin your location on the map.'
      return
    }
  }

  // ── Fetch shipping rates ──
  shippingLoading.value = true; shippingRatesFetched.value = false
  try {
    const country = addressForm.value.country || ''
    const city    = addressForm.value.city    || ''
    const rates   = await fetchDynamicShippingMethods(country, city)
    shippingRatesFetched.value = true
    if (rates?.length) { shippingOptions.value = rates; selectedShippingId.value = rates[0].id } else { shippingOptions.value = [] }
    currentStep.value = 3
    // ── Silently persist shipping address for authenticated users ──
    // This auto-populates their profile Default Address after login/register
    if (auth.isAuthenticated) {
      try {
        const existingAddresses = await fetchAddresses()
        const hasDefault = existingAddresses.some((a: any) => a.isDefault)
        await addAddress({
          firstName: addressForm.value.firstName || addressFullName.value.split(' ')[0] || 'Customer',
          lastName: addressForm.value.lastName || addressFullName.value.split(' ').slice(1).join(' ') || '-',
          phone: addressForm.value.phone || (addrPhoneCode.value + addrPhoneNum.value),
          addressLine1: addressForm.value.street || '-',
          city: addressForm.value.city || '-',
          country: addressForm.value.country || (countriesList.value.find((c: any) => c.phone_code === addrPhoneCode.value) as any)?.name || '-',
          state: addressForm.value.state || undefined,
          postalCode: addressForm.value.postalCode || undefined,
          isDefault: !hasDefault,
        } as any)
      } catch { /* Fire-and-forget — don't block checkout on address save failure */ }
    }
  } catch (err: any) {
    shippingRatesFetched.value = true
    console.error('[Checkout] Shipping rates error:', err?.response?.data || err)
    addressError.value = err?.response?.data?.message || t('checkout.shippingRatesError') || 'Could not fetch shipping rates'
  } finally {
    shippingLoading.value = false
  }
}

// ── Google Maps ──
async function initGoogleMaps() {
  const apiKey = settings.storeSettings.googleMapsApiKey
  if (!apiKey) { addressMode.value = 'manual'; return }

  // Load script if not already loaded
  if (!(window as any).google?.maps) {
    // Prevent duplicate script injection
    if (document.querySelector('script[src*="maps.googleapis.com"]')) {
      // Script already injected but not ready — wait a bit
      await new Promise(r => setTimeout(r, 500))
      if (!(window as any).google?.maps) { addressMode.value = 'manual'; return }
    } else {
      try {
        // Set up auth failure handler BEFORE loading the script
        let authFailed = false
        ;(window as any).gm_authFailure = () => { authFailed = true }

        await new Promise<void>((resolve, reject) => {
          const s = document.createElement('script')
          s.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&loading=async&callback=Function.prototype`
          s.async = true
          s.defer = true
          s.onload = () => resolve()
          s.onerror = () => reject()
          document.head.appendChild(s)
        })

        // Give gm_authFailure a moment to fire
        await new Promise(r => setTimeout(r, 300))
        if (authFailed) {
          console.warn('[Checkout] Google Maps API key is invalid or restricted. Falling back to manual address.')
          addressMode.value = 'manual'
          return
        }
      } catch {
        addressMode.value = 'manual'
        return
      }
    }
  }

  await nextTick()
  if (!mapContainer.value) return

  try {
    const center = { lat: 24.7136, lng: 46.6753 } // Riyadh, Saudi Arabia default
    googleMap = new (window as any).google.maps.Map(mapContainer.value, { center, zoom: 12, disableDefaultUI: true, zoomControl: true })
    googleMarker = new (window as any).google.maps.Marker({ position: center, map: googleMap, draggable: true })
    googleMarker.addListener('dragend', () => { const pos = googleMarker.getPosition(); reverseGeocode(pos.lat(), pos.lng()) })
    if (mapSearchInput.value) {
      autocomplete = new (window as any).google.maps.places.Autocomplete(mapSearchInput.value, { types: ['geocode'] })
      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (place.geometry) {
          const loc = place.geometry.location
          googleMap.setCenter(loc); googleMarker.setPosition(loc)
          parseAddressComponents(place.address_components || [])
          addressForm.value.street = place.formatted_address || ''
        }
      })
    }
  } catch (err) {
    console.warn('[Checkout] Failed to initialize Google Maps:', err)
    addressMode.value = 'manual'
  }
}

function reverseGeocode(lat: number, lng: number) {
  const geocoder = new (window as any).google.maps.Geocoder()
  geocoder.geocode({ location: { lat, lng } }, (results: any[], status: string) => {
    if (status === 'OK' && results[0]) { parseAddressComponents(results[0].address_components); addressForm.value.street = results[0].formatted_address || '' }
  })
}

function parseAddressComponents(components: any[]) {
  for (const c of components) {
    if (c.types.includes('country')) addressForm.value.country = c.long_name
    if (c.types.includes('administrative_area_level_1')) addressForm.value.state = c.long_name
    if (c.types.includes('locality')) addressForm.value.city = c.long_name
    if (c.types.includes('sublocality_level_1') || c.types.includes('sublocality')) addressForm.value.district = c.long_name
    if (c.types.includes('postal_code')) addressForm.value.postalCode = c.long_name
  }
}

function getCurrentLocation() {
  if (!navigator.geolocation) return
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude: lat, longitude: lng } = pos.coords
    if (googleMap && googleMarker) { const loc = { lat, lng }; googleMap.setCenter(loc); googleMarker.setPosition(loc); reverseGeocode(lat, lng) }
  })
}

// ── Step 3: Shipping ──
function submitShipping() {
  clearErrors()
  if (!selectedShippingId.value) { errors.shipping = t('checkout.required'); return }
  // Step 4 (additional info phone) is removed — phone captured in Step 2
  currentStep.value = 5
  loadPaymentMethods()
}

// ── Step 4: Additional Info ──
function submitAdditionalInfo() {
  clearErrors()
  if (!additionalPhone.value.trim()) { errors.additionalPhone = t('checkout.required'); return }
  currentStep.value = 5
  loadPaymentMethods()
}

// ── Step 5: Payment ──
async function loadPaymentMethods() {
  paymentMethodsLoading.value = true
  try {
    const response = await fetchPaymentMethods()
    const gateways = response.gateways || response || []
    dynamicPaymentMethods.value = gateways
    if (response.stripePublishableKey) stripePublishableKey.value = response.stripePublishableKey
    if (response.wallet?.enabled && response.wallet.balance > 0 && auth.isAuthenticated) {
      dynamicPaymentMethods.value = [{ id: 'wallet', name: `Wallet (${response.wallet.currency} ${Number(response.wallet.balance).toFixed(2)})`, icon: '💳', enabled: true }, ...gateways]
    }
    const firstEnabled = dynamicPaymentMethods.value.find((g: any) => g.enabled)
    if (firstEnabled) selectedPayment.value = firstEnabled.id
  } catch { const fb = settings.storeSettings.paymentMethods || []; dynamicPaymentMethods.value = fb.map((pm: any) => ({ ...pm, enabled: true })); if (fb.length) selectedPayment.value = fb[0].id }
  finally { paymentMethodsLoading.value = false }
}

// Pre-mount the Payment Element as soon as the user selects Stripe.
// This shows the Stripe UI immediately (with wallets) and caches the clientSecret.
watch(selectedPayment, async (val) => {
  if (val === 'stripe') {
    await nextTick()
    await initStripePaymentElement()
  } else {
    // Destroy element if user switches away from Stripe
    if (stripePaymentElement) { try { stripePaymentElement.destroy() } catch {} stripePaymentElement = null }
    stripeClientSecret = ''
  }
})

async function initStripePaymentElement() {
  if (stripePaymentElement) return // Already mounted — nothing to do
  stripeError.value = ''
  stripeElementMounting.value = true

  const stripeKey = stripePublishableKey.value || (settings.storeSettings as any)?.stripePublishableKey || import.meta.env.VITE_STRIPE_KEY || ''
  if (!stripeKey) {
    stripeElementMounting.value = false
    stripeError.value = 'Stripe is not configured. Please contact the store.'
    return
  }

  try {
    // 1. Load Stripe.js once
    if (!(window as any).Stripe) {
      await new Promise<void>((resolve, reject) => {
        const s = document.createElement('script')
        s.src = 'https://js.stripe.com/v3/'
        s.onload = () => resolve()
        s.onerror = () => reject(new Error('Stripe.js failed to load'))
        document.head.appendChild(s)
      })
    }
    if (!stripeInstance) stripeInstance = (window as any).Stripe(stripeKey)

    // 2. Create PaymentIntent → get clientSecret
    const selectedOpt = shippingOptions.value.find((s: any) => s.id === selectedShippingId.value)
    const isNewMethod = selectedOpt?.slug || selectedOpt?.carrier_type
    const intentData = await createStripePaymentIntent({
      shippingMethodId: isNewMethod ? (selectedShippingId.value ?? undefined) : undefined,
      couponCode:       couponCode.value || cart.couponCode || undefined,
      currency:         settings.currentCurrencyCode,
    })
    stripeClientSecret = intentData.clientSecret
    // Store the list of enabled types so the Payment Element order matches the PaymentIntent exactly.
    // apple_pay and google_pay are sub-channels of 'card' — insert them right after card so Stripe
    // surfaces them as wallet tabs when the customer's device/browser supports them.
    const backendTypes: string[] = intentData.enabledPaymentMethods ?? ['card', 'link']
    const methodOrder: string[] = []
    for (const t of backendTypes) {
      methodOrder.push(t)
      if (t === 'card') {
        // Wallet types live inside the card payment_method_type; surfaced to separate tabs automatically
        methodOrder.push('apple_pay', 'google_pay')
      }
    }

    // 3. Build Elements instance (intent-first — unlocks wallets)
    stripeElements = stripeInstance.elements({
      clientSecret: stripeClientSecret,
      appearance: {
        theme: 'stripe',
        variables: { colorPrimary: '#4b7bec', fontFamily: 'inherit', borderRadius: '8px' },
      },
    })

    // 4. Create and mount the Payment Element
    //
    // paymentMethodOrder: forces Link to render as a regular tab instead of a dominant
    // "authenticated wallet" overlay. When a user is logged in to Link, Stripe promotes
    // it to a full-screen chip — listing it last prevents that behaviour.
    //
    // wallets: explicitly opts in to Apple Pay and Google Pay. Without this hint,
    // Stripe may suppress wallet stubs in certain test/staging environments.
    //
    // layout.defaultCollapsed: false — ensures every payment-method tab is rendered
    // open and visible on mount (no overflow "More" collapse).
    //
    // NOTE: Apple Pay and Google Pay are DEVICE + BROWSER conditional:
    //   • Google Pay — Chrome + Google account with a saved card
    //   • Apple Pay  — Safari on Apple device + card in Apple Wallet + HTTPS
    //   • Both wallets require HTTPS. On http://localhost they will never appear.
    //   • Both must be enabled in Stripe Dashboard → Settings → Payment Methods.
    //   • Apple Pay requires your domain registered under Dashboard → Apple Pay Domains.
    // methodOrder is built from enabledPaymentMethods returned by the backend (which calls
    // Stripe's PaymentMethodConfigurations API). It matches payment_method_types in the
    // PaymentIntent exactly — Stripe requires this alignment or it hides unmatched methods.
    console.info('[Stripe] Element paymentMethodOrder:', methodOrder)
    stripePaymentElement = stripeElements.create('payment', {
      layout: { type: 'tabs', defaultCollapsed: false },
      paymentMethodOrder: methodOrder,
      wallets: {
        applePay: 'auto',
        googlePay: 'auto',
      },
    })
    const mountEl = document.getElementById('stripe-payment-element')
    if (mountEl) {
      stripePaymentElement.mount('#stripe-payment-element')

      // Debug: log which payment methods Stripe actually resolved once the element is ready.
      // Open browser DevTools Console to inspect this on each checkout load.
      stripePaymentElement.on('ready', (e: any) => {
        const methods = (e as any)?.availablePaymentMethods || {}
        console.info('[Stripe] Payment Element ready. Resolved methods:', methods)
        if (!methods.applePay && !methods.googlePay) {
          console.warn(
            '[Stripe] Wallets not visible. Possible causes:\n' +
            '  1. Page is on HTTP — both wallets require HTTPS.\n' +
            '  2. No card saved in the device wallet (Google / Apple).\n' +
            '  3. Apple Pay / Google Pay not enabled in Stripe Dashboard → Settings → Payment Methods.\n' +
            '  4. Apple Pay domain not registered in Stripe Dashboard → Apple Pay Domains.\n' +
            '  5. Browser/device is incompatible (Google Pay needs Chrome; Apple Pay needs Safari on Apple).'
          )
        }
      })

      stripePaymentElement.on('loaderror', (e: any) => { stripeError.value = e.error?.message || 'Failed to load payment form.' })
    }
  } catch (err: any) {
    const msg = err?.response?.data?.message || err?.message || 'Could not initialise payment.'
    stripeError.value = `Payment initialisation failed: ${msg}`
  } finally {
    stripeElementMounting.value = false
  }
}

async function confirmPayment() {
  clearErrors(); orderError.value = ''; stripeError.value = ''
  if (!selectedPayment.value) { errors.payment = t('checkout.selectPayment'); return }
  if (!agreeTerms.value) { errors.terms = t('checkout.agreeTermsRequired'); return }
  // ── Build base order payload ──
  const isGuestMode = authMode.value === 'guest' && !auth.isAuthenticated
  const selectedOpt = shippingOptions.value.find((s: any) => s.id === selectedShippingId.value)
  const isNewMethod = selectedOpt?.slug || selectedOpt?.carrier_type
  const payload: any = {
    shippingAddress: { firstName: addressForm.value.firstName, lastName: addressForm.value.lastName, phone: additionalPhone.value || addressForm.value.phone, addressLine1: addressForm.value.street, city: addressForm.value.city, country: addressForm.value.country, state: addressForm.value.state, postalCode: addressForm.value.postalCode },
    paymentMethod: selectedPayment.value,
    ...(isNewMethod ? { shippingMethodId: selectedShippingId.value } : { shippingRateId: selectedShippingId.value }),
    couponCode: couponCode.value || cart.couponCode || undefined,
    notes: '',
    currency: settings.currentCurrencyCode,
  }
  const userNameParts = auth.user?.name?.split(' ') || []
  if (!payload.shippingAddress.firstName) payload.shippingAddress.firstName = guestForm.value.firstName || userNameParts[0] || 'Customer'
  if (!payload.shippingAddress.lastName)  payload.shippingAddress.lastName  = guestForm.value.lastName  || userNameParts.slice(1).join(' ') || '-'
  if (!payload.shippingAddress.phone)     payload.shippingAddress.phone     = guestForm.value.phone || auth.user?.phone || ''
  if (!payload.shippingAddress.addressLine1) payload.shippingAddress.addressLine1 = '-'
  if (!payload.shippingAddress.city)    payload.shippingAddress.city    = '-'
  if (!payload.shippingAddress.country) payload.shippingAddress.country = '-'
  if (isGuestMode) { payload.guestEmail = guestForm.value.email; payload.guestName = `${payload.shippingAddress.firstName} ${payload.shippingAddress.lastName}`; payload.guestPhone = guestForm.value.phone }

  orderLoading.value = true
  try {
    // ════════════════════════════════════════════════════════
    //  STRIPE — Payment-First Flow
    //  1 → Element pre-mounted on method select (watch)
    //  2 → elements.submit() validates payment fields
    //  3 → stripe.confirmPayment() charges card/wallet in-page
    //  4 → Attach PI id → place-order (backend re-verifies)
    // ════════════════════════════════════════════════════════
    if (selectedPayment.value === 'stripe') {

      // Guard: element must be mounted (initStripePaymentElement runs on method selection)
      if (!stripeElements || !stripePaymentElement || !stripeClientSecret) {
        await initStripePaymentElement()
        if (!stripeElements || !stripePaymentElement) {
          orderError.value = 'Payment form is not ready. Please wait a moment and try again.'
          orderLoading.value = false
          return
        }
      }

      // 1️⃣ Validate payment fields inside Stripe's element
      const { error: submitError } = await stripeElements.submit()
      if (submitError) {
        stripeError.value = submitError.message || 'Please check your payment details.'
        orderLoading.value = false
        return
      }

      // 2️⃣ Confirm — redirect:'if_required' keeps cards/wallets fully in-page
      const { error: confirmError, paymentIntent } = await stripeInstance.confirmPayment({
        elements:      stripeElements,
        clientSecret:  stripeClientSecret,
        confirmParams: { return_url: window.location.origin + '/checkout/success' },
        redirect:      'if_required',
      })

      if (confirmError) {
        const hint = confirmError.decline_code || confirmError.code || null
        stripeError.value = `${confirmError.message || 'Payment failed.'}${hint ? ` (${hint})` : ''}`
        // Reset element so user gets a fresh PaymentIntent on retry
        stripePaymentElement = null; stripeElements = null; stripeClientSecret = ''
        await nextTick(); await initStripePaymentElement()
        orderLoading.value = false
        return
      }

      if (!paymentIntent || paymentIntent.status !== 'succeeded') {
        orderError.value = `Payment not completed (status: ${paymentIntent?.status ?? 'unknown'}). Please try again.`
        stripePaymentElement = null; stripeElements = null; stripeClientSecret = ''
        await nextTick(); await initStripePaymentElement()
        orderLoading.value = false
        return
      }

      // 3️⃣ Attach verified PI id — backend re-verifies with Stripe before creating order
      payload.paymentIntentId = paymentIntent.id
    }

    // ── Place the order (for Stripe: backend verifies PI → creates order as paid) ──
    const response = await placeOrder(payload)

    // ── Success ──
    await cart.loadCart()
    router.push('/checkout/success/' + response.orderNumber)

  } catch (e: any) {
    // Restore cart snapshot so the user can retry without losing items
    cart.syncFromApi({ items: JSON.parse(JSON.stringify(cart.items)), subtotal: { ...cart.subtotal }, discount: { ...cart.discount }, total: { ...cart.total }, couponCode: cart.couponCode } as any)
    const resp = e.response?.data
    if (resp?.errors) {
      const allErrors = Object.values(resp.errors).flat().join('. ')
      orderError.value = allErrors || resp.message || t('common.error')
    } else {
      orderError.value = resp?.message || e.message || t('common.error')
    }
    // If this was a Stripe order and the PI was already consumed, reset element for a clean retry
    if (selectedPayment.value === 'stripe') {
      stripePaymentElement = null; stripeElements = null; stripeClientSecret = ''
      nextTick().then(() => initStripePaymentElement())
    }
  }
  finally { orderLoading.value = false }

}

// ── Coupon ──
async function applyCoupon() {
  if (!couponCode.value.trim()) return
  couponLoading.value = true; couponMsg.value = ''; couponError.value = false; freeShippingApplied.value = false
  const result = await cart.applyCoupon(couponCode.value.trim())
  couponLoading.value = false
  if (result.success) {
    couponMsg.value = t('checkout.couponApplied')
    // Check for free shipping flag from backend
    if (result.freeShipping) {
      freeShippingApplied.value = true
    }
    showCoupon.value = false
  } else {
    couponError.value = true
    couponMsg.value = result.message || t('common.error')
  }
}

function quickApplyCoupon(code: string) {
  couponCode.value = code
  applyCoupon()
}

// ── Lifecycle ──
onMounted(async () => {
  // Initialize OTP channel from admin setting
  if (otpMode.value === 'phone') otpChannel.value = 'phone'

  if (auth.isAuthenticated) {
    authMode.value = 'login'; currentStep.value = 2; prefillAddressFromUser()
    // Load user's loyalty coupons for quick-apply
    try {
      const couponsRes = await fetchMyCoupons()
      loyaltyCoupons.value = (couponsRes?.active || []).slice(0, 5)
    } catch { /* ignore */ }
  }
  // countriesList is loaded via useCountries() composable (no separate fetch needed)
  if (currentStep.value === 2) initGoogleMaps()
  // Init drawer variant selections from current cart items
  initDrawerSelections(cart.items)
})

// Re-init drawer selections whenever cart changes
watch(() => cart.items, (items) => initDrawerSelections(items), { deep: true, immediate: true })

watch(currentStep, (val) => { if (val === 2) nextTick(() => initGoogleMaps()) })

onUnmounted(() => {
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
  if (stripePaymentElement) {
    try { stripePaymentElement.destroy() } catch {}
    stripePaymentElement = null
  }
})
</script>

<style scoped>
.checkout-page { background: #f5f5f5; min-height: 100vh; padding-bottom: 3rem; }
.container { max-width: 900px; margin: 0 auto; padding: 0 1rem; }

/* Header */
.checkout-header { background: #fff; border-bottom: 1px solid #eee; padding: 1.25rem 0 0; }
.checkout-header__inner { display: flex; justify-content: space-between; align-items: flex-start; }
.checkout-header__left { display: flex; align-items: center; gap: 1rem; }
.checkout-header__logo { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; }
.checkout-header__logo-img { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; }
.checkout-header__logo-label { font-size: 0.625rem; color: #374151; }
.checkout-header__thumbs { display: flex; gap: 0.25rem; }
.checkout-header__thumb { width: 32px; height: 32px; object-fit: contain; border-radius: 4px; }
.checkout-header__right { text-align: right; }
.checkout-header__title { font-size: 1.125rem; font-weight: 700; color: #111; }
.checkout-header__total { font-size: 1.5rem; font-weight: 700; color: #111; }
.checkout-header__cashback { font-size: 0.75rem; color: #059669; font-weight: 600; margin-top: 0.125rem; }
.checkout-header__coupon-btn { background: none; border: none; color: #c0392b; font-size: 0.8125rem; font-weight: 500; cursor: pointer; text-decoration: underline; margin-top: 0.25rem; }
.checkout-coupon { padding: 1rem 0; }
.checkout-coupon__row { display: flex; gap: 0.5rem; }
.checkout-coupon__input { flex: 1; padding: 0.625rem 0.875rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.875rem; outline: none; text-transform: uppercase; font-family: monospace; }
.checkout-coupon__input:focus { border-color: #111; }
.checkout-coupon__apply { padding: 0.625rem 1.5rem; background: #333; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.checkout-coupon__apply:disabled { opacity: 0.6; }
.checkout-coupon__msg { font-size: 0.8125rem; margin: 0.5rem 0 0; color: #059669; }
.checkout-coupon__msg.error { color: #dc2626; }

/* Loyalty coupons quick-apply */
.checkout-coupon__loyalty { margin-bottom: 0.75rem; }
.checkout-coupon__loyalty-label { display: block; font-size: 0.8rem; font-weight: 600; color: #6b7280; margin-bottom: 0.5rem; }
.checkout-coupon__loyalty-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.checkout-coupon__loyalty-btn {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.75rem; background: #fef3c7; border: 1px solid #fde68a;
  border-radius: 8px; cursor: pointer; transition: all 0.2s; text-align: left;
}
.checkout-coupon__loyalty-btn:hover:not(:disabled) { background: #fde68a; border-color: #f59e0b; transform: translateY(-1px); }
.checkout-coupon__loyalty-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.checkout-coupon__loyalty-btn--shipping { background: #ede9fe; border-color: #ddd6fe; }
.checkout-coupon__loyalty-btn--shipping:hover:not(:disabled) { background: #ddd6fe; border-color: #a78bfa; }
.loyalty-coupon-icon { font-size: 1.25rem; flex-shrink: 0; }
.loyalty-coupon-info { display: flex; flex-direction: column; gap: 0.1rem; }
.loyalty-coupon-name { font-size: 0.75rem; font-weight: 600; color: #111827; white-space: nowrap; }
.loyalty-coupon-code { font-family: monospace; font-size: 0.7rem; color: #b45309; background: none; padding: 0; letter-spacing: 0.5px; }
.checkout-coupon__free-ship-msg {
  font-size: 0.8125rem; margin: 0.5rem 0 0;
  color: #7c3aed; font-weight: 600;
  display: flex; align-items: center; gap: 0.35rem;
}
.checkout-header__details-toggle { display: flex; justify-content: center; padding: 1rem 0; }
.checkout-details-btn { padding: 0.375rem 1.5rem; border: 1px solid #ddd; border-radius: 100px; background: #fff; font-size: 0.8125rem; color: #111; cursor: pointer; }
.checkout-details-btn:hover { border-color: #999; }

/* ─ Sections ─ */
.checkout-body { padding-top: 0; }
.checkout-section { background: #fff; border-radius: 16px; padding: 1.5rem; transition: opacity 0.3s; }
.checkout-section.locked { opacity: 0.4; pointer-events: none; }
.section-divider { height: 1px; background: #eee; }
.section-header { display: flex; align-items: flex-start; gap: 0.75rem; }
.section-icon { width: 28px; height: 28px; flex-shrink: 0; color: #374151; margin-top: 2px; }
.section-icon svg { width: 100%; height: 100%; }
.section-title-wrap { flex: 1; }
.section-title { font-size: 1.125rem; font-weight: 700; color: #111; margin: 0; line-height: 1.3; }
.section-subtitle { font-size: 0.8125rem; color: #888; margin: 0.125rem 0 0; }
.section-side-link { background: none; border: none; font-size: 0.875rem; font-weight: 500; color: #111; cursor: pointer; text-decoration: underline; white-space: nowrap; }
.edit-btn { display: flex; align-items: center; gap: 0.25rem; padding: 0.375rem 0.75rem; border: 1px solid #ddd; border-radius: 8px; background: #fff; font-size: 0.8125rem; color: #111; cursor: pointer; white-space: nowrap; }
.edit-btn:hover { border-color: #999; }
.section-content { margin-top: 1.25rem; }

/* ─ Forms ─ */
.checkout-auth-form { display: flex; flex-direction: column; gap: 1rem; }
.checkout-form-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 600px) { .checkout-form-grid { grid-template-columns: 1fr 1fr; } }
.checkout-field { display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 0.5rem; }
.checkout-label { font-size: 0.875rem; font-weight: 600; color: #111; }
.checkout-label--colored { color: #6b4c9a; }
.req { color: #dc2626; }
.checkout-input { padding: 0.625rem 0.875rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.875rem; outline: none; color: #111; background: #fff; width: 100%; box-sizing: border-box; }
.checkout-input:focus { border-color: #111; }
.checkout-input.input-error { border-color: #dc2626; }
.checkout-select { appearance: auto; cursor: pointer; }
.field-error { font-size: 0.75rem; color: #dc2626; }
.auth-error-msg { color: #dc2626; font-size: 0.875rem; margin: 0.25rem 0; padding: 0.5rem 1rem; background: #fef2f2; border-radius: 8px; }
.checkout-alt-text { display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-size: 0.8125rem; color: #666; margin-top: 0.5rem; }
.checkout-alt-text a { color: #111; text-decoration: underline; }

/* Phone Input */
.phone-input-row { display: flex; gap: 0; }
.country-code-select { display: flex; align-items: center; gap: 0.25rem; padding: 0 0.5rem; border: 1px solid #ddd; border-right: none; border-radius: 8px 0 0 8px; background: #f9f9f9; cursor: pointer; min-width: 70px; }
html[dir="rtl"] .country-code-select { border-right: 1px solid #ddd; border-left: none; border-radius: 0 8px 8px 0; }
.country-code-arrow { font-size: 0.75rem; color: #888; transform: rotate(90deg); }
.country-flag-img { width: 20px; height: 14px; object-fit: cover; border-radius: 2px; }
.country-code-val { font-size: 0.8125rem; color: #111; font-weight: 500; }
.phone-input { border-radius: 0 8px 8px 0 !important; flex: 1; }
html[dir="rtl"] .phone-input { border-radius: 8px 0 0 8px !important; }

/* OTP */
.otp-verify-block { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
.otp-back-btn { width: 40px; height: 40px; border-radius: 50%; border: 1px solid #eee; background: #f9f9f9; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
.otp-verify-info { text-align: center; flex: 1; }
.otp-verify-text { font-size: 0.8125rem; color: #888; margin: 0; }
.otp-verify-email { font-size: 0.9375rem; font-weight: 700; color: #111; margin: 0.25rem 0 0; }
.checkout-otp-row { display: flex; gap: 0.75rem; justify-content: center; margin: 1rem 0; direction: ltr; }
.checkout-otp-box { width: 70px; height: 56px; text-align: center; font-size: 1.25rem; font-weight: 700; font-family: monospace; border: 1.5px solid #ddd; border-radius: 8px; outline: none; background: #fff; color: #111; }
.checkout-otp-box:focus { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
.checkout-otp-box.input-error { border-color: #dc2626; }
.otp-resend-text { text-align: center; font-size: 0.8125rem; color: #888; margin-top: 0.75rem; }

/* OTP Channel Tabs (email / phone) */
.checkout-otp-tabs { display: flex; border: 1.5px solid #ddd; border-radius: 8px; overflow: hidden; margin-bottom: 1rem; }
.checkout-otp-tab { flex: 1; padding: 0.625rem; background: #f5f5f5; border: none; font-size: 0.875rem; font-weight: 500; cursor: pointer; color: #555; transition: all 0.15s; }
.checkout-otp-tab.active { background: #888; color: #fff; font-weight: 700; }
.checkout-otp-tab:hover:not(.active) { background: #ebebeb; }

/* Buttons */
.checkout-btn { display: block; width: 100%; padding: 0.875rem 1rem; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 700; cursor: pointer; margin-top: 1rem; text-align: center; }
.checkout-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.checkout-btn--dark { background: #888; color: #fff; }
.checkout-btn--dark:hover:not(:disabled) { background: #666; }
.checkout-btn--light { background: #e5e5e5; color: #555; }
.checkout-btn--light:hover:not(:disabled) { background: #d4d4d4; }

/* Checkboxes */
.checkout-checkbox { display: flex; align-items: flex-start; gap: 0.5rem; margin: 0.75rem 0; cursor: pointer; font-size: 0.8125rem; color: #111; }
.checkout-checkbox input[type="checkbox"] { width: 18px; height: 18px; accent-color: #4b7bec; flex-shrink: 0; margin-top: 1px; }
.checkout-checkbox--terms { font-size: 0.75rem; color: #666; line-height: 1.5; }
.info-icon { font-size: 0.75rem; color: #999; }

/* Address */
.address-map-mode, .address-manual-mode { display: flex; flex-direction: column; gap: 0.5rem; }
.map-container { position: relative; border-radius: 12px; overflow: hidden; margin-bottom: 0.5rem; }
.map-search-overlay { position: absolute; top: 0; left: 0; right: 0; z-index: 10; padding: 0.75rem; background: rgba(255,255,255,0.95); }
.map-search-input { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.875rem; outline: none; box-sizing: border-box; }
.map-search-hint { font-size: 0.6875rem; color: #888; margin: 0.25rem 0 0; }
.google-map { width: 100%; height: 300px; background: #e5e7eb; }
.current-location-btn { position: absolute; bottom: 12px; left: 12px; z-index: 10; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; background: #fff; border: 1px solid #ddd; border-radius: 4px; font-size: 0.75rem; color: #111; cursor: pointer; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
.toggle-address-mode { background: none; border: none; color: #6b4c9a; font-size: 0.8125rem; font-weight: 500; cursor: pointer; text-decoration: underline; text-align: center; margin-top: 0.5rem; display: block; width: 100%; }
.recipient-block { padding: 1rem; border: 1px solid #eee; border-radius: 12px; margin: 0.5rem 0; background: #fafafa; }

/* Shipping Cards */
.shipping-options { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 0.5rem; }
.shipping-card { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border: 1.5px solid #eee; border-radius: 12px; cursor: pointer; transition: border-color 0.2s; }
.shipping-card.selected { border-color: #4b7bec; background: #f8faff; }
.checkout-radio { width: 18px; height: 18px; accent-color: #4b7bec; flex-shrink: 0; }
.shipping-logo { width: 48px; height: 36px; object-fit: contain; flex-shrink: 0; }
.shipping-info { flex: 1; }
.shipping-name { display: block; font-size: 0.9375rem; font-weight: 700; color: #111; }
.shipping-time { font-size: 0.75rem; color: #888; }
.shipping-price { font-size: 1rem; font-weight: 700; color: #111; white-space: nowrap; }
.empty-state { text-align: center; padding: 2rem; color: #888; }

/* Payment */
.payment-methods-grid { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; }
.payment-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border: 1.5px solid #eee; border-radius: 12px; cursor: pointer; transition: border-color 0.2s; min-width: 120px; }
.payment-card.selected { border-color: #4b7bec; background: #f8faff; }
.payment-card.disabled { opacity: 0.5; cursor: not-allowed; }
.payment-logo { height: 28px; max-width: 80px; object-fit: contain; }
.payment-name-text { font-size: 0.8125rem; font-weight: 600; color: #111; }
.payment-badge { font-size: 0.625rem; font-weight: 600; background: #fef3c7; color: #d97706; padding: 0.125rem 0.5rem; border-radius: 100px; text-transform: uppercase; }
.card-details-form { border: 1px solid #eee; border-radius: 12px; padding: 1.25rem; margin-bottom: 0.75rem; }
.card-note { margin: 0; font-size: 0.875rem; color: #666; }
/* Stripe Payment Element */
.stripe-payment-mount { min-height: 200px; }
.stripe-hidden { visibility: hidden; height: 0; overflow: hidden; }
.stripe-element-loading {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.75rem; padding: 2rem; color: #888; font-size: 0.875rem;
}
.stripe-element-spinner {
  width: 32px; height: 32px; border: 3px solid #e5e7eb;
  border-top-color: #4b7bec; border-radius: 50%;
  animation: stripe-spin 0.8s linear infinite;
}
@keyframes stripe-spin { to { transform: rotate(360deg); } }

/* ─ Drawer ─ */
.drawer-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 9999; display: flex; justify-content: flex-end; }
.drawer-panel { width: 400px; max-width: 90vw; height: 100vh; background: #fff; display: flex; flex-direction: column; box-shadow: -4px 0 24px rgba(0,0,0,0.12); }
.drawer-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #eee; }
.drawer-title { font-size: 1.125rem; font-weight: 700; color: #111; margin: 0; }
.drawer-close { width: 36px; height: 36px; border: none; background: #f5f5f5; border-radius: 50%; font-size: 1.25rem; color: #666; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.drawer-close:hover { background: #eee; }
.drawer-body { flex: 1; overflow-y: auto; padding: 1rem 1.5rem; }
.drawer-empty { text-align: center; padding: 3rem 1rem; color: #888; }
.drawer-items { display: flex; flex-direction: column; gap: 1rem; }
.drawer-item { display: flex; gap: 0.75rem; padding: 0.75rem; border: 1px solid #eee; border-radius: 12px; }
.drawer-item__img { width: 72px; height: 72px; object-fit: contain; border-radius: 8px; background: #f9f9f9; flex-shrink: 0; }
.drawer-item__info { flex: 1; display: flex; flex-direction: column; gap: 0.25rem; }
.drawer-item__name { font-size: 0.875rem; font-weight: 600; color: #111; margin: 0; line-height: 1.3; }
.drawer-item__attrs { display: flex; flex-wrap: wrap; gap: 0.375rem; margin: 0; }
.drawer-item__attr { font-size: 0.6875rem; color: #666; background: #f3f3f3; padding: 0.125rem 0.5rem; border-radius: 4px; }
.drawer-item__price { font-size: 0.875rem; font-weight: 700; color: #111; margin: 0; }
.drawer-item__qty { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem; }
.qty-btn { width: 28px; height: 28px; border: 1px solid #ddd; border-radius: 6px; background: #fff; font-size: 1rem; color: #111; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover:not(:disabled) { border-color: #999; background: #f5f5f5; }
.qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.qty-val { font-size: 0.875rem; font-weight: 700; min-width: 20px; text-align: center; }
.qty-remove { width: 28px; height: 28px; border: none; background: #fee2e2; border-radius: 6px; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-left: auto; }
.qty-remove:hover { background: #fecaca; }
.drawer-footer { padding: 1rem 1.5rem; border-top: 1px solid #eee; }
.drawer-total-row { display: flex; justify-content: space-between; align-items: center; font-size: 1.0625rem; }
.drawer-total-row strong { font-size: 1.25rem; color: #111; }

/* Drawer transitions */
.drawer-fade-enter-active, .drawer-fade-leave-active { transition: opacity 0.3s ease; }
.drawer-fade-enter-from, .drawer-fade-leave-to { opacity: 0; }
.drawer-slide-enter-active, .drawer-slide-leave-active { transition: transform 0.3s ease; }
.drawer-slide-enter-from, .drawer-slide-leave-to { transform: translateX(100%); }
html[dir="rtl"] .drawer-slide-enter-from, html[dir="rtl"] .drawer-slide-leave-to { transform: translateX(-100%); }

/* Country code dropdown */
.country-code-dropdown {
  appearance: auto;
  width: 90px;
  padding: 0.625rem 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px 0 0 10px;
  background: #f9fafb;
  font-size: 0.9rem;
  font-weight: 600;
  color: #111;
  cursor: pointer;
  border-right: none;
  flex-shrink: 0;
}
.country-code-dropdown:focus { outline: none; border-color: #111; }
html[dir="rtl"] .country-code-dropdown { border-radius: 0 10px 10px 0; border-right: 1px solid #e5e7eb; border-left: none; }

/* Payment shimmer */
.payment-shimmer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; }
.payment-shimmer-card { height: 80px; border-radius: 12px; background: #f3f4f6; overflow: hidden; position: relative; }
.shimmer-bar { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%); background-size: 200% 100%; animation: shimmer 1.5s ease-in-out infinite; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* ═══════════════════════════════════════
   MOBILE RESPONSIVE OVERRIDES
   max-width: 600px — small phones/tablets
═══════════════════════════════════════ */
@media (max-width: 600px) {

  /* ─ Container ─ */
  .container { padding: 0 0.75rem; }

  /* ─ Checkout Header ─ */
  .checkout-header { padding: 0.875rem 0 0; }
  .checkout-header__inner {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .checkout-header__left {
    flex: 1 1 auto;
    min-width: 0;
    gap: 0.5rem;
  }
  .checkout-header__thumbs {
    flex-wrap: wrap;
    max-width: calc(100vw - 180px);
    overflow: hidden;
  }
  .checkout-header__thumb {
    width: 28px;
    height: 28px;
  }
  .checkout-header__logo-img {
    width: 44px;
    height: 44px;
  }
  .checkout-header__right {
    text-align: right;
    flex-shrink: 0;
  }
  .checkout-header__title { font-size: 0.875rem; }
  .checkout-header__total { font-size: 1.25rem; }

  /* ─ Coupon Row ─ */
  .checkout-coupon__row {
    flex-direction: row;
    gap: 0.375rem;
  }
  .checkout-coupon__apply {
    padding: 0.625rem 0.875rem;
    font-size: 0.8125rem;
    flex-shrink: 0;
  }
  .checkout-coupon__loyalty-list { gap: 0.375rem; }
  .checkout-coupon__loyalty-btn {
    padding: 0.375rem 0.625rem;
    gap: 0.375rem;
    font-size: 0.75rem;
  }
  .loyalty-coupon-icon { font-size: 1rem; }
  .loyalty-coupon-name { font-size: 0.6875rem; white-space: normal; }

  /* ─ Detail toggle ─ */
  .checkout-header__details-toggle { padding: 0.75rem 0; }

  /* ─ Sections ─ */
  .checkout-section {
    border-radius: 10px;
    padding: 1rem 0.875rem;
  }

  /* ─ Section Header ─ */
  .section-header {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .section-title-wrap { min-width: 0; }
  .section-title { font-size: 1rem; }
  .section-subtitle { font-size: 0.75rem; }
  .section-icon { width: 22px; height: 22px; }
  .edit-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    margin-left: auto;
  }
  .section-side-link { font-size: 0.8125rem; }

  /* ─ OTP ─ */
  .checkout-otp-row { gap: 0.5rem; margin: 0.75rem 0; }
  .checkout-otp-box {
    width: calc((100% - 3 * 0.5rem) / 4);
    max-width: 62px;
    height: 50px;
    font-size: 1.125rem;
  }
  .otp-verify-block { gap: 0.75rem; }
  .otp-verify-email { font-size: 0.875rem; }
  .otp-resend-text { font-size: 0.75rem; }

  /* ─ Buttons ─ */
  .checkout-btn {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }

  /* ─ Form fields ─ */
  .checkout-field { margin-bottom: 0.375rem; }
  .checkout-input {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
  }
  .checkout-label { font-size: 0.8125rem; }

  /* ─ Address / Map ─ */
  .google-map { height: 220px; }
  .map-search-overlay { padding: 0.5rem; }
  .map-search-input {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
  }
  .current-location-btn {
    bottom: 8px;
    left: 8px;
    padding: 0.3rem 0.6rem;
    font-size: 0.6875rem;
  }
  .recipient-block { padding: 0.75rem; }

  /* ─ Shipping Cards ─ */
  .shipping-card {
    padding: 0.75rem 0.875rem;
    gap: 0.5rem;
  }
  .shipping-logo {
    width: 36px;
    height: 28px;
  }
  .shipping-name { font-size: 0.875rem; }
  .shipping-price { font-size: 0.875rem; }

  /* ─ Payment Methods ─ */
  .payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 0.625rem;
  }
  .payment-card {
    min-width: unset;
    padding: 0.625rem 0.75rem;
    gap: 0.375rem;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  .payment-logo {
    height: 22px;
    max-width: 64px;
  }
  .payment-name-text { font-size: 0.75rem; }
  .payment-shimmer-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }

  /* ─ Card details / Stripe ─ */
  .card-details-form { padding: 0.875rem; }

  /* ─ Drawer ─ */
  .drawer-panel {
    width: 100vw;
    max-width: 100vw;
  }
  .drawer-header { padding: 1rem 1rem; }
  .drawer-body { padding: 0.875rem 1rem; }
  .drawer-footer { padding: 0.875rem 1rem; }
  .drawer-item { gap: 0.625rem; padding: 0.625rem; }
  .drawer-item__img { width: 56px; height: 56px; }
  .drawer-item__name { font-size: 0.8125rem; }
  .drawer-item__price { font-size: 0.8125rem; }

  /* ─ Section content spacing ─ */
  .section-content { margin-top: 1rem; }

  /* ─ Checkboxes ─ */
  .checkout-checkbox { font-size: 0.75rem; }

  /* ─ Checkout alt text ─ */
  .checkout-alt-text { font-size: 0.75rem; flex-wrap: wrap; justify-content: center; }
}

/* ═══════════════════════════════════════
   EXTRA SMALL — very narrow phones (< 380px)
═══════════════════════════════════════ */
@media (max-width: 380px) {
  .checkout-header__inner { gap: 0.25rem; }
  .checkout-header__logo-img { width: 38px; height: 38px; }
  .checkout-header__total { font-size: 1.125rem; }
  .checkout-header__title { font-size: 0.8125rem; }
  .checkout-otp-box {
    height: 44px;
    font-size: 1rem;
  }
  .checkout-section { padding: 0.875rem 0.75rem; }
  .payment-methods-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
