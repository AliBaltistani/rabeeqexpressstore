<template>
  <div class="account-content-inner">
    <div class="header-row">
      <h2>{{ $t('account.loyaltyPoints') || 'Loyalty Points' }}</h2>
    </div>

    <div v-if="isLoading" class="loading-state">
      <p>{{ $t('common.loading') }}...</p>
    </div>

    <div v-else class="loyalty-container">
      <div class="points-card">
        <div class="card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="m12 8 2.5 5.5 5.5 1-4.5 3.5 1 5.5-4.5-3-4.5 3 1-5.5L4 14.5l5.5-1z"></path></svg>
        </div>
        <div class="card-details">
          <h3>Available Points</h3>
          <p class="points-balance">{{ loyalty?.balance || 0 }}</p>
          <span class="tier" v-if="loyalty?.tier">{{ loyalty.tier.name }} Tier</span>
        </div>
      </div>

      <div class="transactions-section">
        <h3>Recent History</h3>
        <div v-if="!transactions || transactions.length === 0" class="empty-state">
          No transactions yet.
        </div>
        <table v-else class="transactions-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Points</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in transactions" :key="t.id">
              <td>{{ new Date(t.created_at).toLocaleDateString() }}</td>
              <td>{{ t.description }}</td>
              <td :class="t.type === 'earned' ? 'text-green' : 'text-red'">
                {{ t.type === 'earned' ? '+' : '-' }}{{ t.points }}
              </td>
              <td><span class="badge" :class="t.type">{{ t.type }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchLoyaltyPoints, fetchLoyaltyTransactions } from '@/api/services'

const loyalty = ref<any>(null)
const transactions = ref<any[]>([])
const isLoading = ref(true)

async function loadData() {
  isLoading.value = true
  try {
    const res: any = await fetchLoyaltyPoints()
    loyalty.value = res.data || res
    
    const txRes: any = await fetchLoyaltyTransactions()
    transactions.value = txRes.data || txRes || []
  } catch (error) {
    console.error('Failed to load loyalty points', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}
.header-row {
  margin-bottom: 2rem;
}
.header-row h2 {
  font-size: 1.5rem;
  color: #111827;
  margin: 0;
}

.loyalty-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.points-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 3rem;
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white;
  border-radius: 16px;
  box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
}
.card-icon {
  background: rgba(255,255,255,0.2);
  padding: 1.5rem;
  border-radius: 50%;
}
.card-details h3 {
  margin: 0 0 0.5rem;
  font-size: 1.125rem;
  opacity: 0.9;
}
.points-balance {
  margin: 0 0 0.5rem;
  font-size: 3rem;
  font-weight: 800;
  line-height: 1;
}
.tier {
  display: inline-block;
  background: rgba(255,255,255,0.2);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.transactions-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
}
.transactions-section h3 {
  margin: 0 0 1.5rem;
  color: #111827;
}

.transactions-table {
  width: 100%;
  border-collapse: collapse;
}
.transactions-table th {
  text-align: left;
  padding: 1rem;
  border-bottom: 2px solid #f3f4f6;
  color: #6b7280;
  font-size: 0.875rem;
}
.transactions-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  color: #374151;
}

.text-green { color: #10b981; font-weight: 600; }
.text-red { color: #ef4444; font-weight: 600; }

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}
.badge.earned { background: #d1fae5; color: #065f46; }
.badge.redeemed { background: #fee2e2; color: #991b1b; }

.empty-state, .loading-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}
</style>
