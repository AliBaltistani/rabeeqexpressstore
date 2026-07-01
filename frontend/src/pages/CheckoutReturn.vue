<template>
  <div class="checkout-return-page">
    <div class="checkout-return-card">
      <!-- Success -->
      <template v-if="isSuccess">
        <div class="return-icon return-icon--success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h1 class="return-title">{{ $t('checkout.paymentSuccess') || 'Payment Confirmed!' }}</h1>
        <p class="return-subtitle">
          {{ $t('checkout.bnplSuccess') || 'Your BNPL payment was approved. Your order is being processed.' }}
        </p>
        <p v-if="orderNumber" class="return-order">
          {{ $t('orders.orderNumber') || 'Order' }}: <strong>{{ orderNumber }}</strong>
        </p>
        <div class="return-actions">
          <router-link :to="orderNumber ? `/orders/${orderNumber}` : '/orders'" class="checkout-btn checkout-btn--dark">
            {{ $t('orders.viewOrder') || 'View Order' }}
          </router-link>
          <router-link to="/" class="checkout-btn checkout-btn--light">
            {{ $t('common.continueShopping') || 'Continue Shopping' }}
          </router-link>
        </div>
      </template>

      <!-- Cancelled -->
      <template v-else-if="isCancelled">
        <div class="return-icon return-icon--warning">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
            <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <h1 class="return-title">{{ $t('checkout.paymentCancelled') || 'Payment Cancelled' }}</h1>
        <p class="return-subtitle">
          {{ $t('checkout.bnplCancelled') || 'You cancelled the payment. Your order has been reserved but not paid.' }}
        </p>
        <div class="return-actions">
          <router-link to="/checkout" class="checkout-btn checkout-btn--dark">
            {{ $t('checkout.tryAgain') || 'Try Again' }}
          </router-link>
          <router-link to="/" class="checkout-btn checkout-btn--light">
            {{ $t('common.continueShopping') || 'Continue Shopping' }}
          </router-link>
        </div>
      </template>

      <!-- Failure -->
      <template v-else>
        <div class="return-icon return-icon--error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
            <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h1 class="return-title">{{ $t('checkout.paymentFailed') || 'Payment Failed' }}</h1>
        <p class="return-subtitle">
          {{ $t('checkout.bnplFailed') || 'Your payment was not approved. Please try a different payment method.' }}
        </p>
        <div class="return-actions">
          <router-link to="/checkout" class="checkout-btn checkout-btn--dark">
            {{ $t('checkout.tryAgain') || 'Try Again' }}
          </router-link>
          <router-link to="/" class="checkout-btn checkout-btn--light">
            {{ $t('common.continueShopping') || 'Continue Shopping' }}
          </router-link>
        </div>
      </template>

      <!-- Gateway badge -->
      <p v-if="gateway" class="return-gateway">
        {{ $t('checkout.poweredBy') || 'Powered by' }}
        <strong>{{ gateway === 'tamara' ? 'Tamara' : 'Tabby' }}</strong>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const status      = computed(() => (route.query.status as string) || 'failure')
const orderNumber = computed(() => (route.query.order as string) || '')
const gateway     = computed(() => (route.query.gateway as string) || '')

const isSuccess   = computed(() => status.value === 'success')
const isCancelled = computed(() => status.value === 'cancel' || status.value === 'cancelled')
</script>

<style scoped>
.checkout-return-page {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: var(--color-background, #f9fafb);
}

.checkout-return-card {
  background: #fff;
  border-radius: 1rem;
  padding: 3rem 2.5rem;
  max-width: 480px;
  width: 100%;
  text-align: center;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}

.return-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  margin: 0 auto 1.5rem;
}

.return-icon--success { background: #dcfce7; color: #16a34a; }
.return-icon--warning { background: #fef9c3; color: #ca8a04; }
.return-icon--error   { background: #fee2e2; color: #dc2626; }

.return-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
  color: var(--color-text, #111);
}

.return-subtitle {
  color: var(--color-text-muted, #6b7280);
  margin: 0 0 1rem;
  line-height: 1.6;
}

.return-order {
  font-size: 0.95rem;
  color: var(--color-text-muted, #6b7280);
  margin: 0 0 1.5rem;
}

.return-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.return-gateway {
  font-size: 0.8rem;
  color: #9ca3af;
  margin: 0;
}

/* Reuse checkout button styles */
.checkout-btn {
  display: block;
  width: 100%;
  padding: 0.85rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 600;
  text-align: center;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: opacity 0.2s;
}
.checkout-btn:hover { opacity: 0.88; }
.checkout-btn--dark  { background: #111; color: #fff; }
.checkout-btn--light { background: #f3f4f6; color: #111; }
</style>
