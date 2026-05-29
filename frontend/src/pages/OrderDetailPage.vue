<template>
  <div class="account-content-inner">
      <div class="header-row">
        <div style="display: flex; gap: 1rem; align-items: center;">
          <h2>{{ $t('checkout.orderId') || 'Order' }} #{{ route.params.orderNumber }}</h2>
          <button v-if="order" @click="handleDownloadInvoice" class="btn-invoice">📝 Generate Invoice</button>
        </div>
        <router-link to="/account/orders" class="back-link">← {{ $t('account.orders') }}</router-link>
      </div>

      <div v-if="isLoading" class="loading-state"><p>{{ $t('common.loading') }}...</p></div>
      <div v-else-if="!order" class="empty-state">
        <p>Order not found.</p>
        <router-link to="/account/orders" class="btn-shop">Go Back</router-link>
      </div>

      <div v-else class="order-detail-content">
        <!-- Order Tracking Stepper -->
        <div class="tracking-stepper">
          <div v-for="(step, idx) in trackingSteps" :key="step.key" class="stepper-step" :class="{ active: step.active, completed: step.completed, current: step.current }">
            <div class="stepper-icon">
              <span v-if="step.completed">✓</span>
              <span v-else>{{ idx + 1 }}</span>
            </div>
            <div class="stepper-label">{{ step.label }}</div>
            <div v-if="idx < trackingSteps.length - 1" class="stepper-line" :class="{ filled: step.completed }"></div>
          </div>
        </div>

        <!-- Status Banner -->
        <div class="order-status-banner" :class="order.status.toLowerCase()">
          <div class="status-info">
            <span class="label">{{ $t('checkout.status') || 'Status' }}:</span>
            <span class="value">{{ order.statusLabel || order.status }}</span>
          </div>
          <div class="date-info">
            <span class="label">{{ $t('checkout.date') || 'Date' }}:</span>
            <span class="value">{{ formatDate(order.createdAt) }}</span>
          </div>
        </div>

        <!-- Tracking Info -->
        <div v-if="order.tracking || order.trackingNumber" class="info-card tracking-card">
          <h3>{{ $t('checkout.shippingCompany') || 'Shipping Tracking' }}</h3>
          <p v-if="order.tracking?.carrier"><strong>Carrier:</strong> {{ order.tracking.carrier }}</p>
          <p><strong>Tracking Number:</strong> {{ order.tracking?.trackingNumber || order.trackingNumber }}</p>
          <p v-if="order.tracking?.trackingUrl">
            <a :href="order.tracking.trackingUrl" target="_blank" class="tracking-link">Track Shipment ↗</a>
          </p>
          <p v-if="order.tracking?.estimatedDelivery"><strong>Estimated Delivery:</strong> {{ formatDate(order.tracking.estimatedDelivery) }}</p>
        </div>

        <!-- Addresses & Payment -->
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
          </div>
          <div class="info-card">
            <h3>{{ $t('checkout.paymentMethod') || 'Payment' }}</h3>
            <p><strong>Method:</strong> <span class="capitalize">{{ order.paymentMethod }}</span></p>
            <p><strong>Status:</strong> <span class="capitalize">{{ order.paymentStatus }}</span></p>
            <p v-if="order.paymentGateway"><strong>Gateway:</strong> <span class="capitalize">{{ order.paymentGateway }}</span></p>
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
                  <span>{{ $t('checkout.qty') || 'Qty' }}: {{ item.quantity }}</span>
                  <span>{{ item.unitPrice?.formatted }}</span>
                </div>
              </div>
              <div class="item-total">{{ item.total?.formatted }}</div>
            </div>
          </div>
        </div>

        <!-- Order Totals -->
        <div class="order-summary-box">
          <div class="summary-row"><span>{{ $t('checkout.subtotal') || 'Subtotal' }}</span><span>{{ order.subtotal?.formatted }}</span></div>
          <div class="summary-row" v-if="order.discountAmount?.raw > 0">
            <span>{{ $t('checkout.discount') || 'Discount' }} <span v-if="order.couponCode">({{ order.couponCode }})</span></span>
            <span class="text-green">-{{ order.discountAmount?.formatted }}</span>
          </div>
          <div class="summary-row"><span>{{ $t('checkout.shipping') || 'Shipping' }}</span><span>{{ order.shippingAmount?.formatted }}</span></div>
          <div class="summary-row" v-if="order.taxAmount?.raw > 0"><span>{{ $t('checkout.tax') || 'Tax' }}</span><span>{{ order.taxAmount?.formatted }}</span></div>
          <div class="summary-row total"><span>{{ $t('checkout.total') || 'Total' }}</span><span>{{ order.total?.formatted }}</span></div>
        </div>

        <!-- Status History Timeline -->
        <div v-if="order.statusHistory?.length" class="info-card">
          <h3>Order History</h3>
          <div class="status-timeline">
            <div v-for="(h, i) in order.statusHistory" :key="i" class="timeline-entry" :class="{ latest: i === order.statusHistory.length - 1 }">
              <div class="timeline-dot" :style="{ background: statusColor(h.statusTo || h.status) }"></div>
              <div class="timeline-content">
                <span class="timeline-status" :style="{ color: statusColor(h.statusTo || h.status) }">{{ h.statusTo || h.status }}</span>
                <span v-if="h.comment" class="timeline-comment">{{ h.comment }}</span>
                <span class="timeline-date">{{ formatDateTime(h.createdAt) }}</span>
              </div>
            </div>
          </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import { fetchOrderByNumber, downloadInvoice } from '@/api/services'
import type { Order } from '@/types'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const { t, locale } = useI18n()

const order = ref<Order | null>(null)
const isLoading = ref(true)

const statusOrder = ['pending', 'processing', 'shipped', 'delivered']

const trackingSteps = computed(() => {
  if (!order.value) return []
  const current = order.value.status.toLowerCase()
  const currentIdx = statusOrder.indexOf(current)
  return [
    { key: 'pending', label: t('checkout.pending') || 'Order Placed', completed: currentIdx > 0, current: currentIdx === 0, active: currentIdx >= 0 },
    { key: 'processing', label: t('checkout.processing') || 'Processing', completed: currentIdx > 1, current: currentIdx === 1, active: currentIdx >= 1 },
    { key: 'shipped', label: t('checkout.shipped') || 'Shipped', completed: currentIdx > 2, current: currentIdx === 2, active: currentIdx >= 2 },
    { key: 'delivered', label: t('checkout.delivered') || 'Delivered', completed: currentIdx >= 3, current: currentIdx === 3, active: currentIdx >= 3 },
  ]
})

function statusColor(status?: string): string {
  const colors: Record<string, string> = { pending: '#f59e0b', processing: '#6366f1', shipped: '#3b82f6', delivered: '#10b981', cancelled: '#ef4444', refunded: '#6b7280' }
  return colors[(status || '').toLowerCase()] || '#9ca3af'
}

function formatDate(d: string | undefined): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function formatDateTime(d: string | undefined): string {
  if (!d) return '—'
  return new Date(d).toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function loadOrder() {
  const orderNum = route.params.orderNumber as string
  if (!orderNum) return
  isLoading.value = true
  try { order.value = await fetchOrderByNumber(orderNum) }
  catch { console.error('Failed to load order details') }
  finally { isLoading.value = false }
}

async function handleDownloadInvoice() {
  if (!order.value) return
  try { await downloadInvoice(order.value.orderNumber) }
  catch { console.error('Failed to download invoice') }
}

onMounted(() => {
  if (!auth.isAuthenticated) { router.push('/login') } else { loadOrder() }
})
</script>

<style scoped>
.account-content-inner { flex: 1; }
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.header-row h2 { font-size: 1.5rem; color: var(--store-text-primary, #111827); margin: 0; }
.back-link { color: #6b7280; text-decoration: none; font-weight: 500; }
.back-link:hover { color: var(--color-primary, #858585); }
.btn-invoice { padding: 0.5rem 1rem; background: #3b82f6; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 0.875rem; cursor: pointer; }
.btn-invoice:hover { background: #2563eb; }
.loading-state { text-align: center; padding: 4rem; color: #6b7280; }
.empty-state { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 12px; }
.btn-shop { display: inline-block; padding: 0.75rem 1.5rem; background: var(--color-primary); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 1rem; }
.order-detail-content { display: flex; flex-direction: column; gap: 1.5rem; }
.capitalize { text-transform: capitalize; }

/* Tracking Stepper */
.tracking-stepper { display: flex; align-items: flex-start; justify-content: space-between; background: #fff; border-radius: 12px; padding: 2rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative; }
.stepper-step { display: flex; flex-direction: column; align-items: center; position: relative; flex: 1; z-index: 1; }
.stepper-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 700; background: #e5e7eb; color: #9ca3af; transition: all 0.3s; }
.stepper-step.completed .stepper-icon { background: #10b981; color: #fff; }
.stepper-step.current .stepper-icon { background: var(--color-primary, #6366f1); color: #fff; box-shadow: 0 0 0 4px rgba(99,102,241,0.15); animation: stepPulse 2s infinite; }
.stepper-label { margin-top: 0.5rem; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-align: center; }
.stepper-step.active .stepper-label { color: #374151; }
.stepper-line { position: absolute; top: 18px; left: 50%; width: 100%; height: 3px; background: #e5e7eb; z-index: -1; }
.stepper-line.filled { background: #10b981; }
@keyframes stepPulse { 0%,100% { box-shadow: 0 0 0 4px rgba(99,102,241,0.15); } 50% { box-shadow: 0 0 0 8px rgba(99,102,241,0.06); } }

/* Status Banner */
.order-status-banner { display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem; background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 4px solid #d1d5db; }
@media (min-width: 640px) { .order-status-banner { flex-direction: row; justify-content: space-between; align-items: center; } }
.order-status-banner.pending { border-left-color: #f59e0b; }
.order-status-banner.processing { border-left-color: #6366f1; }
.order-status-banner.shipped { border-left-color: #3b82f6; }
.order-status-banner.delivered { border-left-color: #10b981; }
.order-status-banner.cancelled { border-left-color: #ef4444; }
.status-info, .date-info { display: flex; flex-direction: column; }
.label { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem; }
.value { font-size: 1.125rem; font-weight: 600; color: #111827; text-transform: capitalize; }

/* Cards & Grid */
.info-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 768px) { .info-grid { grid-template-columns: 1fr 1fr; } }
.info-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.info-card h3 { margin: 0 0 1rem; font-size: 1.125rem; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 0.75rem; }
.info-card p { margin: 0 0 0.5rem; color: #4b5563; font-size: 0.9375rem; line-height: 1.5; }
.tracking-link { color: #3b82f6; text-decoration: none; font-weight: 500; }
.tracking-link:hover { text-decoration: underline; }

/* Items */
.order-items-section { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.order-items-section h3 { margin: 0 0 1rem; font-size: 1.125rem; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 0.75rem; }
.order-items-list { display: flex; flex-direction: column; gap: 1rem; }
.order-item { display: flex; gap: 1rem; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6; }
.order-item:last-child { border-bottom: none; padding-bottom: 0; }
.item-img { width: 64px; height: 64px; object-fit: cover; border-radius: 8px; background: #f9fafb; }
.item-details { flex: 1; }
.item-details h4 { margin: 0 0 0.25rem; font-size: 1rem; color: #111827; }
.item-variant { font-size: 0.875rem; color: #6b7280; margin: 0 0 0.5rem; }
.item-meta { display: flex; gap: 1rem; font-size: 0.875rem; color: #4b5563; }
.item-total { font-weight: 600; color: #111827; }

/* Summary */
.order-summary-box { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.75rem; }
@media (min-width: 768px) { .order-summary-box { margin-left: auto; width: 350px; } html[dir="rtl"] .order-summary-box { margin-left: 0; margin-right: auto; } }
.summary-row { display: flex; justify-content: space-between; color: #4b5563; font-size: 0.9375rem; }
.summary-row.total { margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 1.125rem; font-weight: 700; color: #111827; }
.text-green { color: #059669; }

/* Status Timeline */
.status-timeline { position: relative; padding-left: 1.5rem; }
.timeline-entry { position: relative; padding-bottom: 1.25rem; padding-left: 1rem; }
.timeline-entry:last-child { padding-bottom: 0; }
.timeline-entry::before { content: ''; position: absolute; left: -1.5rem; top: 1.25rem; bottom: 0; width: 2px; background: #e5e7eb; }
.timeline-entry:last-child::before { display: none; }
.timeline-dot { position: absolute; left: -1.85rem; top: 0.25rem; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #e5e7eb; }
.timeline-entry.latest .timeline-dot { box-shadow: 0 0 0 3px rgba(99,102,241,0.2); }
.timeline-content { display: flex; flex-direction: column; gap: 0.125rem; }
.timeline-status { font-weight: 600; font-size: 0.875rem; text-transform: capitalize; }
.timeline-comment { font-size: 0.8125rem; color: #6b7280; }
.timeline-date { font-size: 0.75rem; color: #9ca3af; }
</style>
