<template>
  <div class="account-page container">
    <aside class="account-sidebar">
      <div class="user-info">
        <div class="avatar">{{ userInitials }}</div>
        <h3>{{ auth.user?.name || 'Guest User' }}</h3>
        <p v-if="auth.isAuthenticated">{{ auth.user?.email || '' }}</p>
      </div>
      <nav class="account-nav">
        <router-link v-if="auth.isAuthenticated" to="/account">{{ $t('account.dashboard') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/orders" class="active">{{ $t('account.orders') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/profile">{{ $t('account.profile') }}</router-link>
        <router-link to="/account/wishlist">{{ $t('common.wishlist') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/addresses">{{ $t('account.addresses') || 'Addresses' }}</router-link>
        <button v-if="auth.isAuthenticated" @click="logout" class="logout-btn">{{ $t('auth.logout') }}</button>
      </nav>
    </aside>

    <main class="account-content">
      <div class="header-row">
        <h2>{{ $t('account.orderHistory') || 'Order History' }}</h2>
      </div>

      <div class="recent-orders">
        <div v-if="isLoading" class="loading-state">
          <p>{{ $t('common.loading') }}...</p>
        </div>
        
        <div v-else-if="orders.length === 0" class="empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
          <p>{{ $t('account.noOrders') }}</p>
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
              <td>{{ order.orderNumber }}</td>
              <td>{{ new Date(order.createdAt || '').toLocaleDateString() }}</td>
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
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { fetchOrders } from '@/api/services'
import type { Order, PaginationMeta } from '@/types'

const auth = useAuthStore()
const router = useRouter()

const orders = ref<Order[]>([])
const pagination = ref<PaginationMeta | undefined>()
const isLoading = ref(true)

const userInitials = computed(() => {
  const name = auth.user?.name || 'G'
  return name.substring(0, 2).toUpperCase()
})

async function logout() {
  await auth.logout()
  router.push('/login')
}

async function loadOrders(page = 1) {
  if (!auth.isAuthenticated) return
  isLoading.value = true
  try {
    const res = await fetchOrders(page)
    orders.value = res.data
    pagination.value = res.meta
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    isLoading.value = false
  }
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
.account-page {
  padding: 4rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
@media (min-width: 768px) {
  .account-page {
    flex-direction: row;
  }
}
.account-sidebar {
  width: 100%;
  background: #fff;
  border-radius: 12px;
  padding: 2rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
@media (min-width: 768px) {
  .account-sidebar {
    width: 280px;
    flex-shrink: 0;
  }
}
.user-info {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #f3f4f6;
}
.avatar {
  width: 64px;
  height: 64px;
  background: var(--color-primary, #858585);
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin: 0 auto 1rem;
}
.user-info h3 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
  color: var(--store-text-primary, #111827);
}
.user-info p {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}
.account-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.account-nav a, .logout-btn {
  display: block;
  padding: 0.875rem 1rem;
  border-radius: 8px;
  color: #4b5563;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9375rem;
  transition: all 0.2s;
  text-align: left;
  border: none;
  background: transparent;
  cursor: pointer;
}
html[dir="rtl"] .account-nav a, html[dir="rtl"] .logout-btn {
  text-align: right;
}
.account-nav a:hover, .account-nav a.active {
  background: #f9fafb;
  color: var(--color-primary, #858585);
}
.logout-btn {
  color: #ef4444;
}
.logout-btn:hover {
  background: #fef2f2;
}

.account-content {
  flex: 1;
}

.header-row {
  margin-bottom: 2rem;
}
.header-row h2 {
  font-size: 1.5rem;
  color: var(--store-text-primary, #111827);
  margin: 0;
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
