<template>
  <div class="order-success-page container">
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>{{ $t('common.loading') }}</p>
    </div>
    <div v-else-if="order" class="success-content">
      <div class="success-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <h2>Order Placed Successfully!</h2>
      <p>Thank you for your purchase. Your order number is <strong>{{ order.orderNumber }}</strong></p>
      
      <div class="order-details">
        <div class="detail-row">
          <span>Status:</span>
          <strong>{{ order.status }}</strong>
        </div>
        <div class="detail-row">
          <span>Total Amount:</span>
          <strong>{{ order.total?.formatted || order.total }}</strong>
        </div>
        <div class="detail-row">
          <span>Payment Method:</span>
          <strong>{{ order.paymentMethod }}</strong>
        </div>
      </div>

      <router-link to="/" class="btn-home">Return to Home</router-link>
    </div>
    <div v-else class="error-state">
      <p>{{ $t('common.error') }}</p>
      <router-link to="/" class="btn-home">Return to Home</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { fetchOrderSuccess } from '@/api/services'

const route = useRoute()
const isLoading = ref(true)
const order = ref<any>(null)

onMounted(async () => {
  const orderNumber = route.params.orderNumber as string
  if (orderNumber) {
    try {
      const res = await fetchOrderSuccess(orderNumber)
      order.value = res.data || res
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  } else {
    isLoading.value = false
  }
})
</script>

<style scoped>
.order-success-page {
  padding: 4rem 1rem;
  text-align: center;
  min-height: 50vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.success-content {
  max-width: 500px;
  width: 100%;
  background: #fff;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.success-icon {
  margin-bottom: 1.5rem;
}
.success-content h2 {
  font-size: 1.5rem;
  color: var(--store-text-primary, #111827);
  margin-bottom: 0.5rem;
}
.success-content p {
  color: #6b7280;
  margin-bottom: 2rem;
}
.order-details {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  text-align: left;
}
html[dir="rtl"] .order-details {
  text-align: right;
}
.detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
}
.detail-row:last-child {
  margin-bottom: 0;
}
.detail-row span {
  color: #6b7280;
}
.detail-row strong {
  color: var(--store-text-primary, #111827);
}
.btn-home {
  display: inline-block;
  padding: 0.75rem 2rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: opacity 0.2s;
}
.btn-home:hover {
  opacity: 0.9;
}
.loading-state, .error-state {
  text-align: center;
}
.spinner {
  border: 3px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top: 3px solid var(--color-primary, #858585);
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
