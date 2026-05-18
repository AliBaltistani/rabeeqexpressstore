<template>
  <div class="account-content-inner">
      <h2>{{ $t('account.welcome', { name: auth.user?.name || 'User' }) }}</h2>
      
      <div class="dashboard-cards">
        <div class="dash-card">
          <h4>{{ $t('account.totalOrders') }}</h4>
          <p class="dash-num">{{ orders.length }}</p>
          <router-link to="/account/orders" class="dash-link">{{ $t('account.viewOrders') }}</router-link>
        </div>
      </div>

      <div class="recent-orders">
        <h3>{{ $t('account.recentOrders') }}</h3>
        <div v-if="orders.length === 0" class="empty-state">
          <p>{{ $t('account.noOrders') }}</p>
          <router-link to="/products" class="btn-shop">{{ $t('common.shopNow') }}</router-link>
        </div>
        <table v-else class="orders-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Date</th>
              <th>Status</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders.slice(0, 5)" :key="order.id">
              <td>{{ order.orderNumber }}</td>
              <td>{{ new Date(order.createdAt).toLocaleDateString() }}</td>
              <td><span :class="'status-badge ' + order.status.toLowerCase()">{{ order.statusLabel || order.status }}</span></td>
              <td>{{ order.total?.formatted }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useI18n } from 'vue-i18n'
import { fetchOrders } from '@/api/services'

const auth = useAuthStore()
const router = useRouter()
const { t } = useI18n()

const orders = ref<any[]>([])

onMounted(async () => {
  try {
    const res = await fetchOrders()
    orders.value = res.data || []
  } catch (error) {
    console.error('Failed to load dashboard orders:', error)
  }
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}
.account-content-inner h2 {
  font-size: 1.5rem;
  margin-bottom: 2rem;
  color: var(--store-text-primary, #111827);
}
.dashboard-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}
.dash-card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.dash-card h4 {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}
.dash-num {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 1rem;
  color: var(--store-text-primary, #111827);
}
.dash-link {
  font-size: 0.875rem;
  color: var(--color-primary, #858585);
  font-weight: 600;
  text-decoration: underline;
}

.recent-orders h3 {
  font-size: 1.25rem;
  margin-bottom: 1rem;
  color: var(--store-text-primary, #111827);
}
.empty-state {
  background: #fff;
  padding: 3rem;
  border-radius: 12px;
  text-align: center;
}
.empty-state p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}
.btn-shop {
  display: inline-block;
  padding: 0.75rem 2rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
}
.orders-table {
  width: 100%;
  background: #fff;
  border-radius: 12px;
  border-collapse: collapse;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.orders-table th, .orders-table td {
  padding: 1rem 1.5rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.875rem;
}
html[dir="rtl"] .orders-table th, html[dir="rtl"] .orders-table td {
  text-align: right;
}
.orders-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #4b5563;
}
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #f3f4f6;
  color: #4b5563;
}
.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.completed { background: #d1fae5; color: #059669; }
</style>
