<template>
  <div class="account-content-inner">
    <div class="header-row">
      <h2>{{ $t('account.wallet') || 'My Wallet' }}</h2>
    </div>

    <div v-if="isLoading" class="loading-state">
      <p>{{ $t('common.loading') }}...</p>
    </div>

    <div v-else class="wallet-container">
      <div class="wallet-card">
        <div class="card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="card-details">
          <h3>Current Balance</h3>
          <p class="wallet-balance">{{ wallet?.formatted_balance || '0.00' }}</p>
        </div>
      </div>

      <div class="transactions-section">
        <h3>Transaction History</h3>
        <div v-if="!transactions || transactions.length === 0" class="empty-state">
          No transactions yet.
        </div>
        <table v-else class="transactions-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Amount</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in transactions" :key="t.id">
              <td>{{ new Date(t.created_at).toLocaleDateString() }}</td>
              <td>{{ t.description }}</td>
              <td :class="t.type === 'credit' ? 'text-green' : 'text-red'">
                {{ t.type === 'credit' ? '+' : '-' }}{{ t.formatted_amount || t.amount }}
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
import { fetchWallet, fetchWalletTransactions } from '@/api/services'

const wallet = ref<any>(null)
const transactions = ref<any[]>([])
const isLoading = ref(true)

async function loadData() {
  isLoading.value = true
  try {
    const res = await fetchWallet()
    wallet.value = res.data || res
    
    const txRes = await fetchWalletTransactions()
    transactions.value = txRes.data || txRes || []
  } catch (error) {
    console.error('Failed to load wallet', error)
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

.wallet-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.wallet-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 3rem;
  background: linear-gradient(135deg, #1f2937, #4b5563);
  color: white;
  border-radius: 16px;
  box-shadow: 0 10px 15px -3px rgba(31, 41, 55, 0.4);
}
.card-icon {
  background: rgba(255,255,255,0.15);
  padding: 1.5rem;
  border-radius: 50%;
}
.card-details h3 {
  margin: 0 0 0.5rem;
  font-size: 1.125rem;
  opacity: 0.9;
}
.wallet-balance {
  margin: 0;
  font-size: 3rem;
  font-weight: 800;
  line-height: 1;
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
.badge.credit { background: #d1fae5; color: #065f46; }
.badge.debit { background: #fee2e2; color: #991b1b; }

.empty-state, .loading-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}
</style>
