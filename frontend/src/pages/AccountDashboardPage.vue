<template>
  <div class="account-page container">
    <aside class="account-sidebar">
      <div class="user-info">
        <div class="avatar">{{ userInitials }}</div>
        <h3>{{ auth.user?.name || 'User' }}</h3>
        <p>{{ auth.user?.email || '' }}</p>
      </div>
      <nav class="account-nav">
        <router-link to="/account" class="active">{{ $t('account.dashboard') }}</router-link>
        <router-link to="/account/orders">{{ $t('account.orders') }}</router-link>
        <router-link to="/account/profile">{{ $t('account.profile') }}</router-link>
        <button @click="logout" class="logout-btn">{{ $t('auth.logout') }}</button>
      </nav>
    </aside>

    <main class="account-content">
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
              <td><span :class="'status-badge ' + order.status.toLowerCase()">{{ order.status }}</span></td>
              <td>{{ order.total }} {{ order.currency }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const router = useRouter()
const { t } = useI18n()

const orders = ref<any[]>([])

const userInitials = computed(() => {
  const name = auth.user?.name || 'U'
  return name.substring(0, 2).toUpperCase()
})

async function logout() {
  await auth.logout()
  router.push('/login')
}

onMounted(async () => {
  // In a real scenario, fetch user orders
  // orders.value = await fetchUserOrders()
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
.account-content h2 {
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
