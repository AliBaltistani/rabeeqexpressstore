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
                      <p v-if="item.attributes && item.attributes.length" class="drawer-item__attrs">
                        <span v-for="attr in item.attributes" :key="attr.id" class="drawer-item__attr">{{ attr.name }}: {{ attr.values.map(v => v.value).join(', ') }}</span>
                      </p>
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
          <!-- MAP MODE -->
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
            <div class="checkout-field">
              <label class="checkout-label checkout-label--colored">{{ $t('checkout.buildingDesc') }}</label>
              <input type="text" v-model="addressForm.buildingDesc" class="checkout-input" :placeholder="$t('checkout.buildingDesc')" />
            </div>
            <label class="checkout-checkbox">
              <input type="checkbox" v-model="deliverToOther" />
              <span>{{ $t('checkout.deliverToOther') }} <span class="info-icon">ⓘ</span></span>
            </label>
            <div v-if="deliverToOther" class="recipient-block">
              <div class="checkout-field">
                <label class="checkout-label checkout-label--colored">{{ $t('checkout.recipientName') }} <span class="req">*</span></label>
                <input type="text" v-model="recipientForm.name" class="checkout-input" />
              </div>
              <div class="checkout-form-grid">
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
                  <PhoneInput
                    v-model="recipientForm.phone"
                    v-model:countryCode="recipientCountryCode"
                    :error="!!errors.recipientPhone"
                    placeholder="501234567"
                  />
                  <span v-if="errors.recipientPhone" class="field-error">{{ errors.recipientPhone }}</span>
                </div>
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.recipientEmail') }} ({{ $t('common.optional') }})</label>
                  <input type="email" v-model="recipientForm.email" class="checkout-input" />
                </div>
              </div>
              <label class="checkout-checkbox">
                <input type="checkbox" v-model="smsUpdates" />
                <span>{{ $t('checkout.smsUpdates') }}</span>
              </label>
            </div>
            <p v-if="addressError" class="auth-error-msg">{{ addressError }}</p>
            <button class="checkout-btn checkout-btn--dark" :disabled="shippingLoading" @click="submitAddress">{{ shippingLoading ? $t('common.loading') : $t('checkout.save') }}</button>
            <button class="toggle-address-mode" @click="addressMode = 'manual'" type="button">{{ $t('checkout.enterManually') || 'Enter The Address Manually' }}</button>
          </div>
          <!-- MANUAL MODE -->
          <div v-else class="address-manual-mode">
            <form @submit.prevent="submitAddress">
              <div class="checkout-form-grid">
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.country') }} <span class="req">*</span></label>
                  <select v-model="addressForm.country" class="checkout-input checkout-select" :class="{ 'input-error': errors.addrCountry }">
                    <option value="">{{ $t('checkout.country') }}...</option>
                    <option v-for="c in countries" :key="c.code" :value="c.name">{{ c.name }}</option>
                  </select>
                  <span v-if="errors.addrCountry" class="field-error">{{ errors.addrCountry }}</span>
                </div>
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.region') }} <span class="req">*</span></label>
                  <input type="text" v-model="addressForm.state" class="checkout-input" :class="{ 'input-error': errors.addrRegion }" :placeholder="$t('checkout.region') + '...'" />
                  <span v-if="errors.addrRegion" class="field-error">{{ errors.addrRegion }}</span>
                </div>
              </div>
              <div class="checkout-form-grid">
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.city') }} <span class="req">*</span></label>
                  <input type="text" v-model="addressForm.city" class="checkout-input" :class="{ 'input-error': errors.addrCity }" />
                  <span v-if="errors.addrCity" class="field-error">{{ errors.addrCity }}</span>
                </div>
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.district') }} <span class="req">*</span></label>
                  <input type="text" v-model="addressForm.district" class="checkout-input" />
                </div>
              </div>
              <div class="checkout-form-grid">
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.street') }} <span class="req">*</span></label>
                  <input type="text" v-model="addressForm.street" class="checkout-input" :class="{ 'input-error': errors.addrStreet }" />
                  <span v-if="errors.addrStreet" class="field-error">{{ errors.addrStreet }}</span>
                </div>
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.postalCode') }} <span class="req">*</span></label>
                  <input type="text" v-model="addressForm.postalCode" class="checkout-input" :class="{ 'input-error': errors.addrPostal }" />
                  <span v-if="errors.addrPostal" class="field-error">{{ errors.addrPostal }}</span>
                </div>
              </div>
              <div class="checkout-form-grid">
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.buildingNo') }}</label>
                  <input type="text" v-model="addressForm.buildingNo" class="checkout-input" />
                </div>
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.buildingDesc') }}</label>
                  <input type="text" v-model="addressForm.buildingDesc" class="checkout-input" :placeholder="$t('checkout.buildingDesc')" />
                </div>
              </div>
              <label class="checkout-checkbox">
                <input type="checkbox" v-model="deliverToOther" />
                <span>{{ $t('checkout.deliverToOther') }} <span class="info-icon">ⓘ</span></span>
              </label>
              <div v-if="deliverToOther" class="recipient-block">
                <div class="checkout-field">
                  <label class="checkout-label checkout-label--colored">{{ $t('checkout.recipientName') }} <span class="req">*</span></label>
                  <input type="text" v-model="recipientForm.name" class="checkout-input" />
                </div>
                <div class="checkout-form-grid">
                  <div class="checkout-field">
                    <label class="checkout-label checkout-label--colored">{{ $t('checkout.phoneNumber') }} <span class="req">*</span></label>
                    <PhoneInput
                      v-model="recipientForm.phone"
                      v-model:countryCode="recipientCountryCode"
                      :error="!!errors.recipientPhone"
                      placeholder="501234567"
                    />
                  </div>
                  <div class="checkout-field">
                    <label class="checkout-label checkout-label--colored">{{ $t('checkout.recipientEmail') }} ({{ $t('common.optional') }})</label>
                    <input type="email" v-model="recipientForm.email" class="checkout-input" />
                  </div>
                </div>
                <label class="checkout-checkbox"><input type="checkbox" v-model="smsUpdates" /><span>{{ $t('checkout.smsUpdates') }}</span></label>
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
            <button class="checkout-btn checkout-btn--dark" @click="currentStep = 4">{{ $t('search.next') || 'Next' }} →</button>
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

      <div class="section-divider"></div>

      <!-- ═══ STEP 4: Additional Information ═══ -->
      <section class="checkout-section" :class="{ locked: currentStep < 4 }">
        <div class="section-header">
          <div class="section-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
          </div>
          <div class="section-title-wrap">
            <h2 class="section-title">{{ $t('checkout.additionalInfo') }}</h2>
          </div>
          <button v-if="currentStep > 4" class="edit-btn" @click="currentStep = 4"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg> {{ $t('checkout.edit') }}</button>
        </div>
        <div v-if="currentStep === 4" class="section-content">
          <div class="checkout-field">
            <label class="checkout-label checkout-label--colored">{{ $t('checkout.mobileNumber') || 'Mobile Number' }} <span class="req">*</span></label>
            <PhoneInput
              v-model="additionalPhone"
              v-model:countryCode="addrCountryCode"
              :error="!!errors.additionalPhone"
              placeholder="501234567"
            />
            <span v-if="errors.additionalPhone" class="field-error">{{ errors.additionalPhone }}</span>
          </div>
          <button class="checkout-btn checkout-btn--dark" @click="submitAdditionalInfo">{{ $t('checkout.confirmInfo') }}</button>
        </div>
      </section>

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

          <!-- Stripe Card Element -->
          <div v-if="selectedPayment === 'stripe'" class="card-details-form">
            <div class="checkout-form-grid">
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.cardDetails') }} <span class="req">*</span></label>
                <div id="stripe-card-element" class="stripe-mount"></div>
                <p v-if="stripeError" class="field-error" style="margin-top:0.5rem;">{{ stripeError }}</p>
              </div>
              <div class="checkout-field">
                <label class="checkout-label">{{ $t('checkout.cardHolderName') }} <span class="req">*</span></label>
                <input type="text" v-model="cardHolderName" class="checkout-input" :placeholder="$t('checkout.cardHolderName')" />
              </div>
            </div>
            <label class="checkout-checkbox"><input type="checkbox" v-model="saveCard" /><span>{{ $t('checkout.saveCard') }}</span></label>
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
import { fetchDynamicShippingMethods, placeOrder, fetchPaymentMethods, confirmStripePayment, fetchActiveCountries, updateProfile, fetchMyCoupons } from '@/api/services'
import { useI18n } from 'vue-i18n'
import PhoneInput from '@/components/common/PhoneInput.vue'

const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const settings = useSettingsStore()
const { t } = useI18n()

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
const addressError = ref('')
const shippingLoading = ref(false)
const shippingRatesFetched = ref(false)
const deliverToOther = ref(false)
const recipientForm = ref({ name: '', phone: '', email: '' })
const recipientCountryCode = ref('+966')
const smsUpdates = ref(false)
const addrCountryCode = ref('+966')
const additionalPhone = ref('')
const countries = ref<any[]>([])

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
const cardHolderName = ref('')
const saveCard = ref(true)
let stripeInstance: any = null
let stripeCard: any = null
const stripeError = ref('')
const stripeReady = ref(false)
const stripePublishableKey = ref('')

// ── Google Maps ──
const mapContainer = ref<HTMLElement | null>(null)
const mapSearchInput = ref<HTMLInputElement | null>(null)
let googleMap: any = null
let googleMarker: any = null
let autocomplete: any = null

// ── Country helpers (kept for backward compat with phone composition) ──
const selectedGuestCountry = computed(() => countries.value.find((c: any) => c.phone_code === guestCountryCode.value))
const selectedAddrCountry = computed(() => countries.value.find((c: any) => c.phone_code === addrCountryCode.value))
const selectedRecipientCountry = computed(() => countries.value.find((c: any) => c.phone_code === recipientCountryCode.value))

// ── Computed ──
const cashbackMessage = computed(() => { return '' })
const welcomeName = computed(() => { if (auth.isAuthenticated && auth.user) { return auth.user.name } if (authMode.value === 'guest') { return `${guestForm.value.firstName} ${guestForm.value.lastName}` } return '' })
const welcomePhone = computed(() => { if (auth.isAuthenticated && auth.user) return auth.user.phone || ''; return guestForm.value.phone ? `${guestCountryCode.value}${guestForm.value.phone}` : '' })
const addressSummary = computed(() => { const a = addressForm.value; return [a.country, a.city, a.street].filter(Boolean).join(' - ') || '' })

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

  if (otpChannel.value === 'phone' || otpMode.value === 'phone') {
    // Phone OTP flow
    if (!otpPhone.value.trim()) { errors.otpPhone = t('checkout.required'); return }
    authLoading.value = true
    const fullPhone = otpPhoneCode.value + otpPhone.value.replace(/^0+/, '')
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
}

async function handleSendOtp() {
  clearErrors(); authError.value = ''
  authLoading.value = true
  let result: any
  if (otpChannel.value === 'phone' || otpMode.value === 'phone') {
    result = await auth.sendPhoneOtp(otpPhoneCode.value + otpPhone.value.replace(/^0+/, ''))
  } else {
    result = await auth.sendOtp(otpEmail.value)
  }
  authLoading.value = false
  if (result.success) {
    otpCodeDigits.value = ['', '', '', '']
    startOtpCooldown((result as any).cooldown || 60)
    nextTick(() => checkoutOtpRefs.value[0]?.focus())
  } else { authError.value = (result as any).message || t('common.error') }
}

async function handleVerifyOtp() {
  clearErrors(); authError.value = ''
  const code = otpCodeDigits.value.join('')
  if (code.length < 4) { errors.otpCode = t('loginModal.invalidOtp'); return }
  authLoading.value = true
  let result: any
  if (otpChannel.value === 'phone' || otpMode.value === 'phone') {
    result = await auth.verifyPhoneOtp(otpPhoneCode.value + otpPhone.value.replace(/^0+/, ''), code)
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
  addressForm.value.firstName = guestForm.value.firstName
  addressForm.value.lastName = guestForm.value.lastName
  addressForm.value.phone = guestForm.value.phone
  additionalPhone.value = guestForm.value.phone
  currentStep.value = 2
}

function prefillAddressFromUser() {
  if (auth.user) {
    const parts = auth.user.name?.split(' ') || []
    addressForm.value.firstName = parts[0] || ''
    addressForm.value.lastName = parts.slice(1).join(' ') || ''
    addressForm.value.phone = auth.user.phone || ''
    additionalPhone.value = auth.user.phone || ''
  }
}

// ── Step 2: Address ──
async function submitAddress() {
  clearErrors(); addressError.value = ''
  if (addressMode.value === 'manual') {
    if (!addressForm.value.country.trim()) errors.addrCountry = t('checkout.required')
    if (!addressForm.value.city.trim()) errors.addrCity = t('checkout.required')
    if (!addressForm.value.street.trim()) errors.addrStreet = t('checkout.required')
    if (Object.keys(errors).length) return
  } else {
    // Map mode — ensure geocoding populated the address
    if (!addressForm.value.country.trim() || !addressForm.value.city.trim()) {
      addressError.value = t('checkout.selectLocationOnMap') || 'Please select a location on the map or enter the address manually.'
      return
    }
  }
  shippingLoading.value = true; shippingRatesFetched.value = false
  try {
    const rates = await fetchDynamicShippingMethods(addressForm.value.country, addressForm.value.city)
    shippingRatesFetched.value = true
    if (rates?.length) { shippingOptions.value = rates; selectedShippingId.value = rates[0].id } else { shippingOptions.value = [] }
    currentStep.value = 3
  } catch (err: any) {
    shippingRatesFetched.value = true
    console.error('[Checkout] Shipping rates error:', err?.response?.data || err)
    addressError.value = err?.response?.data?.message || t('checkout.shippingRatesError') || 'Could not fetch shipping rates'
  }
  finally { shippingLoading.value = false }
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
  currentStep.value = 4
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

watch(selectedPayment, async (val) => { if (val === 'stripe') { await nextTick(); await initStripeElements() } })

async function initStripeElements() {
  stripeError.value = ''
  const stripeKey = stripePublishableKey.value || (settings.storeSettings as any)?.stripePublishableKey || import.meta.env.VITE_STRIPE_KEY || ''
  if (!stripeKey) { stripeReady.value = true; return }
  try {
    if (!(window as any).Stripe) { await new Promise<void>((resolve, reject) => { const s = document.createElement('script'); s.src = 'https://js.stripe.com/v3/'; s.onload = () => resolve(); s.onerror = () => reject(); document.head.appendChild(s) }) }
    stripeInstance = (window as any).Stripe(stripeKey)
    const elements = stripeInstance.elements()
    stripeCard = elements.create('card', { style: { base: { fontSize: '16px', color: '#32325d', fontFamily: 'inherit', '::placeholder': { color: '#aab7c4' } }, invalid: { color: '#fa755a' } } })
    const mount = document.getElementById('stripe-card-element')
    if (mount) { stripeCard.mount('#stripe-card-element'); stripeCard.on('change', (e: any) => { stripeError.value = e.error?.message || '' }); stripeReady.value = true }
  } catch (e: any) { stripeError.value = 'Failed to initialize payment form' }
}

async function confirmPayment() {
  clearErrors(); orderError.value = ''
  if (!selectedPayment.value) { errors.payment = t('checkout.selectPayment'); return }
  if (!agreeTerms.value) { errors.terms = t('checkout.agreeTermsRequired'); return }
  orderLoading.value = true
  try {
    const isGuestMode = authMode.value === 'guest' && !auth.isAuthenticated
    const selectedOpt = shippingOptions.value.find((s: any) => s.id === selectedShippingId.value)
    const isNewMethod = selectedOpt?.slug || selectedOpt?.carrier_type
    const payload: any = {
      shippingAddress: { firstName: addressForm.value.firstName, lastName: addressForm.value.lastName, phone: additionalPhone.value || addressForm.value.phone, addressLine1: addressForm.value.street, city: addressForm.value.city, country: addressForm.value.country, state: addressForm.value.state, postalCode: addressForm.value.postalCode },
      paymentMethod: selectedPayment.value,
      ...(isNewMethod ? { shippingMethodId: selectedShippingId.value } : { shippingRateId: selectedShippingId.value }),
      couponCode: couponCode.value || cart.couponCode || undefined, notes: '', currency: settings.currentCurrencyCode,
    }
    // Resolve name: prefer addressForm, fall back to auth user, then guest form
    const userNameParts = auth.user?.name?.split(' ') || []
    if (!payload.shippingAddress.firstName) payload.shippingAddress.firstName = guestForm.value.firstName || userNameParts[0] || 'Customer'
    if (!payload.shippingAddress.lastName) payload.shippingAddress.lastName = guestForm.value.lastName || userNameParts.slice(1).join(' ') || '-'
    if (!payload.shippingAddress.phone) payload.shippingAddress.phone = guestForm.value.phone || auth.user?.phone || ''
    if (!payload.shippingAddress.addressLine1) payload.shippingAddress.addressLine1 = '-'
    if (!payload.shippingAddress.city) payload.shippingAddress.city = '-'
    if (!payload.shippingAddress.country) payload.shippingAddress.country = '-'
    if (isGuestMode) { payload.guestEmail = guestForm.value.email; payload.guestName = `${payload.shippingAddress.firstName} ${payload.shippingAddress.lastName}`; payload.guestPhone = guestForm.value.phone }
    const response = await placeOrder(payload)
    if (selectedPayment.value === 'stripe' && response.clientSecret && stripeInstance && stripeCard) {
      const { error, paymentIntent } = await stripeInstance.confirmCardPayment(response.clientSecret, { payment_method: { card: stripeCard } })
      if (error) { orderError.value = error.message || 'Payment failed'; orderLoading.value = false; return }
      if (paymentIntent?.status === 'succeeded') await confirmStripePayment(response.orderNumber, response.paymentIntentId)
    }
    cart.clearCart()
    router.push('/checkout/success/' + response.orderNumber)
  } catch (e: any) {
    const resp = e.response?.data
    if (resp?.errors) {
      const allErrors = Object.values(resp.errors).flat().join('. ')
      orderError.value = allErrors || resp.message || t('common.error')
    } else {
      orderError.value = resp?.message || e.message || t('common.error')
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
  try { countries.value = await fetchActiveCountries() } catch { /* use defaults */ }
  if (currentStep.value === 2) initGoogleMaps()
})

watch(currentStep, (val) => { if (val === 2) nextTick(() => initGoogleMaps()) })

onUnmounted(() => {
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
  if (stripeCard) { try { stripeCard.destroy() } catch {} }
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
.stripe-mount { padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; min-height: 44px; }
.stripe-mount:focus-within { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }

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
</style>
