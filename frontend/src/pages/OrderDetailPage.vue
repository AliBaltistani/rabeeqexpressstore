<template>
  <div class="account-content-inner">
      <div class="header-row">
        <div style="display: flex; gap: 1rem; align-items: center;">
          <h2>{{ $t('checkout.orderId') || 'Order' }} #{{ route.params.orderNumber }}</h2>
          <button v-if="order" @click="handleDownloadInvoice" class="btn-invoice">📝 Generate Invoice</button>
        </div>
        <router-link to="/account/orders" class="back-link">← {{ $t('account.orders') }}</router-link>
      </div>

      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>
      
      <div v-else-if="!order" class="empty-state">
        <p>Order not found.</p>
        <router-link to="/account/orders" class="btn-shop">Go Back</router-link>
      </div>

      <div v-else class="order-detail-content">
        <!-- Order Header Status -->
        <div class="order-status-banner" :class="order.status.toLowerCase()">
          <div class="status-info">
            <span class="label">{{ $t('checkout.status') || 'Status' }}:</span>
            <span class="value">{{ order.statusLabel || order.status }}</span>
          </div>
          <div class="date-info">
            <span class="label">{{ $t('checkout.date') || 'Date' }}:</span>
            <span class="value">{{ new Date(order.createdAt || '').toLocaleString() }}</span>
          </div>
        </div>

        <!-- Tracking Info -->
        <div v-if="order.tracking" class="info-card tracking-card">
          <h3>{{ $t('checkout.shippingCompany') || 'Shipping Tracking' }}</h3>
          <p><strong>Carrier:</strong> {{ order.tracking.carrier }}</p>
          <p><strong>Tracking Number:</strong> {{ order.tracking.trackingNumber }}</p>
          <p v-if="order.tracking.trackingUrl">
            <a :href="order.tracking.trackingUrl" target="_blank" class="tracking-link">Track Shipment ↗</a>
          </p>
          <p v-if="order.tracking.estimatedDelivery"><strong>Estimated Delivery:</strong> {{ new Date(order.tracking.estimatedDelivery).toLocaleDateString() }}</p>
        </div>

        <!-- Addresses & Payment Info -->
        <div class="info-grid">
          <div class="info-card">
            <h3>{{ $t('checkout.shippingAddress') || 'Shipping Address' }}</h3>
            <div v-if="order.shippingAddress">
              <p>{{ order.shippingAddress.firstName }} {{ order.shippingAddress.lastName }}</p>
              <p>{{ order.shippingAddress.phone }}</p>
              <p>{{ order.shippingAddress.addressLine1 }}</p>
              <p v-if="order.shippingAddress.addressLine2">{{ order.shippingAddress.addressLine2 }}</p>
              <p>{{ order.shippingAddress.city }}, {{ order.shippingAddress.state }} {{ order.shippingAddress.postalCode }}</p>
              <p>{{ order.shippingAddress.country }}</p>
            </div>
            <p v-else>Not provided</p>
          </div>

          <div class="info-card">
            <h3>{{ $t('checkout.paymentMethod') || 'Payment Method' }}</h3>
            <p><strong>Method:</strong> <span style="text-transform: capitalize;">{{ order.paymentMethod }}</span></p>
            <p><strong>Status:</strong> <span style="text-transform: capitalize;">{{ order.paymentStatus }}</span></p>
          </div>
        </div>

        <!-- Order Items -->
        <div class="order-items-section">
          <h3>{{ $t('checkout.orderSummary') || 'Order Items' }}</h3>
          <div class="order-items-list">
            <div v-for="item in order.items" :key="item.id" class="order-item">
              <img :src="item.productImage || 'https://placehold.co/100x100?text=No+Image'" :alt="item.productName" class="item-img" />
              <div class="item-details">
                <h4>{{ item.productName }}</h4>
                <p v-if="item.variantName" class="item-variant">{{ item.variantName }}</p>
                <div class="item-meta">
                  <span class="item-qty">{{ $t('checkout.qty') || 'Qty' }}: {{ item.quantity }}</span>
                  <span class="item-price">{{ item.unitPrice?.formatted }}</span>
                </div>
              </div>
              <div class="item-total">
                {{ item.total?.formatted }}
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-box">
          <div class="summary-row">
            <span>{{ $t('checkout.subtotal') || 'Subtotal' }}</span>
            <span>{{ order.subtotal?.formatted }}</span>
          </div>
          <div class="summary-row" v-if="order.discountAmount?.raw > 0">
            <span>{{ $t('checkout.discount') || 'Discount' }} <span v-if="order.couponCode">({{ order.couponCode }})</span></span>
            <span class="text-green">-{{ order.discountAmount?.formatted }}</span>
          </div>
          <div class="summary-row">
            <span>{{ $t('checkout.shipping') || 'Shipping' }}</span>
            <span>{{ order.shippingAmount?.formatted }}</span>
          </div>
          <div class="summary-row" v-if="order.taxAmount?.raw > 0">
            <span>{{ $t('checkout.tax') || 'Tax' }}</span>
            <span>{{ order.taxAmount?.formatted }}</span>
          </div>
          <div class="summary-row total">
            <span>{{ $t('checkout.total') || 'Total' }}</span>
            <span>{{ order.total?.formatted }}</span>
          </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { fetchOrderByNumber, downloadInvoice } from '@/api/services'
import type { Order } from '@/types'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const order = ref<Order | null>(null)
const isLoading = ref(true)

const isDownloadingInvoice = ref(false)

async function loadOrder() {
  const orderNum = route.params.orderNumber as string
  if (!orderNum) return

  isLoading.value = true
  try {
    order.value = await fetchOrderByNumber(orderNum)
  } catch (error) {
    console.error('Failed to load order details:', error)
  } finally {
    isLoading.value = false
  }
}

async function handleDownloadInvoice() {
  if (!order.value) return;
  try {
    await downloadInvoice(order.value.orderNumber);
  } catch (error) {
    console.error('Failed to download invoice', error);
  }
}

onMounted(() => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  } else {
    loadOrder()
  }
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.header-row h2 {
  font-size: 1.5rem;
  color: var(--store-text-primary, #111827);
  margin: 0;
}
.back-link {
  color: #6b7280;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9375rem;
}
.back-link:hover {
  color: var(--color-primary, #858585);
}

.btn-invoice {
  padding: 0.5rem 1rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.btn-invoice:hover {
  background-color: #2563eb;
}

.loading-state {
  text-align: center;
  padding: 4rem;
  color: #6b7280;
}
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.btn-shop {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  margin-top: 1rem;
}

.order-detail-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.order-status-banner {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border-left: 4px solid #d1d5db;
}
@media (min-width: 640px) {
  .order-status-banner {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}
.order-status-banner.pending { border-left-color: #f59e0b; }
.order-status-banner.processing { border-left-color: #6366f1; }
.order-status-banner.shipped { border-left-color: #3b82f6; }
.order-status-banner.delivered { border-left-color: #10b981; }
.order-status-banner.cancelled { border-left-color: #ef4444; }

.status-info, .date-info {
  display: flex;
  flex-direction: column;
}
.label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}
.value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  text-transform: capitalize;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}
@media (min-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.info-card {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.info-card h3 {
  margin: 0 0 1rem;
  font-size: 1.125rem;
  color: #111827;
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 0.75rem;
}
.info-card p {
  margin: 0 0 0.5rem;
  color: #4b5563;
  font-size: 0.9375rem;
  line-height: 1.5;
}
.tracking-link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
}
.tracking-link:hover {
  text-decoration: underline;
}

.order-items-section {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.order-items-section h3 {
  margin: 0 0 1rem;
  font-size: 1.125rem;
  color: #111827;
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 0.75rem;
}

.order-items-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.order-item {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}
.order-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.item-img {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 8px;
  background: #f9fafb;
}
.item-details {
  flex: 1;
}
.item-details h4 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
  color: #111827;
}
.item-variant {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 0.5rem;
}
.item-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #4b5563;
}
.item-total {
  font-weight: 600;
  color: #111827;
}

.order-summary-box {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
@media (min-width: 768px) {
  .order-summary-box {
    margin-left: auto;
    width: 350px;
  }
  html[dir="rtl"] .order-summary-box {
    margin-left: 0;
    margin-right: auto;
  }
}
.summary-row {
  display: flex;
  justify-content: space-between;
  color: #4b5563;
  font-size: 0.9375rem;
}
.summary-row.total {
  margin-top: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  font-size: 1.125rem;
  font-weight: 700;
  color: #111827;
}
.text-green {
  color: #059669;
}
</style>
