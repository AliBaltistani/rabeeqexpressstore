<template>
  <div class="account-content-inner">
      <div class="header-row">
        <h2>{{ $t('account.orderHistory') || 'Order History' }}</h2>
      </div>

      <!-- Status Filter Tabs -->
      <div class="order-status-tabs">
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          class="status-tab"
          :class="{ active: currentStatus === tab.value }"
          @click="filterByStatus(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="recent-orders">
        <div v-if="isLoading" class="loading-state">
          <p>{{ $t('common.loading') }}...</p>
        </div>
        
        <div v-else-if="orders.length === 0" class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
          <p>{{ currentStatus ? ($t('account.noOrdersInStatus') || 'No orders with this status.') : $t('account.noOrders') }}</p>
          <router-link to="/products" class="btn-shop">{{ $t('common.shopNow') }}</router-link>
        </div>

        <table v-else class="orders-table">
          <thead>
            <tr>
              <th>{{ $t('checkout.orderId') || 'Order #' }}</th>
              <th>{{ $t('checkout.date') || 'Date' }}</th>
              <th>{{ $t('checkout.status') || 'Status' }}</th>
              <th>{{ $t('checkout.total') || 'Total' }}</th>
              <th>{{ $t('checkout.action') || 'Action' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id">
              <td class="order-number">{{ order.orderNumber }}</td>
              <td>{{ formatDate(order.createdAt) }}</td>
              <td><span :class="'status-badge ' + order.status.toLowerCase()">{{ order.statusLabel || order.status }}</span></td>
              <td>{{ order.total?.formatted }}</td>
              <td>
                <router-link :to="`/account/orders/${order.orderNumber}`" class="view-link">
                  {{ $t('checkout.viewDetails') || 'View Details' }}
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination && pagination.lastPage > 1" class="pagination">
          <button 
            :disabled="pagination.page <= 1" 
            @click="changePage(pagination.page - 1)" 
            class="pagination-btn"
          >
            ←
          </button>
          <span class="pagination-info">{{ pagination.page }} / {{ pagination.lastPage }}</span>
          <button 
            :disabled="pagination.page >= pagination.lastPage" 
            @click="changePage(pagination.page + 1)" 
            class="pagination-btn"
          >
            →
          </button>
        </div>
      </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/authStore'
import { fetchOrders } from '@/api/services'
import type { Order, PaginationMeta } from '@/types'

const auth = useAuthStore()
const router = useRouter()
const { t, locale } = useI18n()

const orders = ref<Order[]>([])
const pagination = ref<PaginationMeta | undefined>()
const isLoading = ref(true)
const currentStatus = ref('')

const statusTabs = computed(() => [
  { value: '', label: t('common.all') || 'All' },
  { value: 'pending', label: t('checkout.pending') || 'Pending' },
  { value: 'processing', label: t('checkout.processing') || 'Processing' },
  { value: 'shipped', label: t('checkout.shipped') || 'Shipped' },
  { value: 'delivered', label: t('checkout.delivered') || 'Delivered' },
  { value: 'cancelled', label: t('checkout.cancelled') || 'Cancelled' },
])

function formatDate(dateStr: string | undefined): string {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

async function loadOrders(page = 1) {
  if (!auth.isAuthenticated) return
  isLoading.value = true
  try {
    const res = await fetchOrders(page, currentStatus.value || undefined)
    orders.value = res.data
    pagination.value = res.meta
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    isLoading.value = false
  }
}

function filterByStatus(status: string) {
  currentStatus.value = status
  loadOrders(1)
}

function changePage(page: number) {
  if (pagination.value && page >= 1 && page <= pagination.value.lastPage) {
    loadOrders(page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

onMounted(() => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  } else {
    loadOrders()
  }
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}

.header-row {
  margin-bottom: 1.5rem;
}
.header-row h2 {
  font-size: 1.5rem;
  color: var(--store-text-primary, #111827);
  margin: 0;
}

/* Status Filter Tabs */
.order-status-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 0.25rem;
}
.status-tab {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 9999px;
  background: #fff;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}
.status-tab:hover {
  border-color: #d1d5db;
  color: #374151;
}
.status-tab.active {
  background: var(--color-primary, #858585);
  border-color: var(--color-primary, #858585);
  color: #fff;
}

.recent-orders {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
html[dir="rtl"] .orders-table {
  text-align: right;
}
.orders-table th {
  padding: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  border-bottom: 2px solid #f3f4f6;
  white-space: nowrap;
}
.orders-table td {
  padding: 1rem;
  font-size: 0.9375rem;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}
.order-number {
  font-weight: 600;
  font-family: monospace;
  font-size: 0.875rem;
  color: #111827;
}
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}
.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.processing { background: #e0e7ff; color: #4338ca; }
.status-badge.shipped { background: #dbeafe; color: #1d4ed8; }
.status-badge.delivered { background: #d1fae5; color: #059669; }
.status-badge.cancelled { background: #fee2e2; color: #b91c1c; }
.status-badge.refunded { background: #f3f4f6; color: #6b7280; }

.view-link {
  color: var(--color-primary, #858585);
  font-weight: 500;
  text-decoration: none;
}
.view-link:hover {
  text-decoration: underline;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}
.empty-state svg {
  margin-bottom: 1rem;
}
.empty-state p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}
.btn-shop {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: opacity 0.2s;
}
.btn-shop:hover {
  opacity: 0.9;
}

.loading-state {
  text-align: center;
  padding: 4rem;
  color: #6b7280;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}
.pagination-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid #e5e7eb;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #374151;
  font-weight: 600;
  transition: all 0.2s;
}
.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #d1d5db;
}
.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.pagination-info {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}
</style>
