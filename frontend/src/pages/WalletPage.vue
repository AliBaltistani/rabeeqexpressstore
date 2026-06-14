<template>
  <div class="account-content-inner">
    <div class="header-row">
      <h2>{{ $t('wallet.title') }}</h2>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>{{ $t('common.loading') }}...</p>
    </div>

    <div v-else class="wallet-loyalty-container">
      <!-- Balance Cards -->
      <div class="cards-row">
        <!-- Wallet Card -->
        <div class="balance-card wallet-card">
          <div class="card-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="2" y="5" width="20" height="14" rx="3"/>
              <path d="M17 12h.01M2 10h20"/>
            </svg>
          </div>
          <div class="card-details">
            <span class="card-label">{{ $t('wallet.walletBalance') }}</span>
            <span class="card-value">{{ walletData?.currency || '' }} {{ Number(walletData?.balance || 0).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Loyalty Card -->
        <div class="balance-card loyalty-card">
          <div class="card-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
          </div>
          <div class="card-details">
            <span class="card-label">{{ $t('wallet.loyaltyPoints') }}</span>
            <span class="card-value">{{ loyaltyData?.points || 0 }} <small>{{ $t('wallet.pts') }}</small></span>
          </div>
        </div>
      </div>

      <!-- Redeem Section -->
      <div v-if="loyaltyData && loyaltyData.points >= (loyaltyData.minRedeem || 100)" class="redeem-section">
        <h3>{{ $t('wallet.redeemPointsToWallet') }}</h3>
        <p class="redeem-info">
          {{ $t('wallet.conversionRate', { rate: loyaltyData.redeemRate || 100, currency: walletData?.currency || 'SAR' }) }}
        </p>
        <div class="redeem-form">
          <div class="input-group">
            <input
              v-model.number="redeemPoints"
              type="number"
              :min="loyaltyData.minRedeem || 100"
              :max="loyaltyData.points"
              :placeholder="$t('wallet.minPoints', { min: loyaltyData.minRedeem || 100 })"
              class="redeem-input"
            />
            <span class="redeem-preview" v-if="redeemPoints > 0">
              = {{ walletData?.currency || 'SAR' }} {{ (redeemPoints / (loyaltyData.redeemRate || 100)).toFixed(2) }}
            </span>
          </div>
          <button
            class="btn-redeem"
            @click="handleRedeem"
            :disabled="isRedeeming || redeemPoints < (loyaltyData.minRedeem || 100) || redeemPoints > loyaltyData.points"
          >
            <template v-if="isRedeeming">{{ $t('wallet.converting') }}</template>
            <template v-else>{{ $t('wallet.convertToWallet') }}</template>
          </button>
        </div>
        <p v-if="redeemError" class="error-msg">{{ redeemError }}</p>
        <p v-if="redeemSuccess" class="success-msg">{{ redeemSuccess }}</p>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button
          :class="['tab-btn', activeTab === 'wallet' && 'active']"
          @click="activeTab = 'wallet'"
        >
          {{ $t('wallet.walletTransactions') }}
        </button>
        <button
          :class="['tab-btn', activeTab === 'loyalty' && 'active']"
          @click="activeTab = 'loyalty'"
        >
          {{ $t('wallet.loyaltyHistory') }}
        </button>
      </div>

      <!-- Wallet Transactions -->
      <div v-if="activeTab === 'wallet'" class="transactions-section">
        <div v-if="!walletTransactions.length" class="empty-state">
          {{ $t('wallet.noWalletTransactions') }}
        </div>
        <table v-else class="transactions-table">
          <thead>
            <tr>
              <th>{{ $t('wallet.date') }}</th>
              <th>{{ $t('wallet.description') }}</th>
              <th>{{ $t('wallet.amount') }}</th>
              <th>{{ $t('wallet.balance') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in walletTransactions" :key="t.id">
              <td>{{ formatDate(t.createdAt) }}</td>
              <td>{{ t.description || '—' }}</td>
              <td :class="t.type === 'credit' ? 'text-green' : 'text-red'">
                {{ t.type === 'credit' ? '+' : '-' }}{{ Number(t.amount).toFixed(2) }}
              </td>
              <td>{{ Number(t.balanceAfter).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Loyalty Transactions -->
      <div v-if="activeTab === 'loyalty'" class="transactions-section">
        <div v-if="!loyaltyTransactions.length" class="empty-state">
          {{ $t('wallet.noLoyaltyTransactions') }}
        </div>
        <table v-else class="transactions-table">
          <thead>
            <tr>
              <th>{{ $t('wallet.date') }}</th>
              <th>{{ $t('wallet.description') }}</th>
              <th>{{ $t('wallet.points') }}</th>
              <th>{{ $t('wallet.type') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in loyaltyTransactions" :key="t.id">
              <td>{{ formatDate(t.createdAt) }}</td>
              <td>{{ t.description || '—' }}</td>
              <td :class="t.type === 'earned' || t.type === 'adjusted' ? 'text-green' : 'text-red'">
                {{ t.type === 'earned' || t.type === 'adjusted' ? '+' : '-' }}{{ t.points }}
              </td>
              <td>
                <span class="badge" :class="'badge-' + t.type">{{ t.type }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchWallet, fetchWalletTransactions, fetchLoyaltyPoints, fetchLoyaltyTransactions, redeemLoyaltyToWallet } from '@/api/services'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const isLoading = ref(true)
const activeTab = ref<'wallet' | 'loyalty'>('wallet')

const walletData = ref<any>(null)
const loyaltyData = ref<any>(null)
const walletTransactions = ref<any[]>([])
const loyaltyTransactions = ref<any[]>([])

// Redeem
const redeemPoints = ref(0)
const isRedeeming = ref(false)
const redeemError = ref('')
const redeemSuccess = ref('')

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

async function loadData() {
  isLoading.value = true
  try {
    const [walletRes, loyaltyRes, walletTxRes, loyaltyTxRes] = await Promise.all([
      fetchWallet(),
      fetchLoyaltyPoints(),
      fetchWalletTransactions(),
      fetchLoyaltyTransactions(),
    ])
    walletData.value = walletRes
    loyaltyData.value = loyaltyRes
    walletTransactions.value = Array.isArray(walletTxRes) ? walletTxRes : []
    loyaltyTransactions.value = Array.isArray(loyaltyTxRes) ? loyaltyTxRes : []
  } catch (error) {
    console.error('Failed to load wallet/loyalty data', error)
  } finally {
    isLoading.value = false
  }
}

async function handleRedeem() {
  if (redeemPoints.value < (loyaltyData.value?.minRedeem || 100)) return
  isRedeeming.value = true
  redeemError.value = ''
  redeemSuccess.value = ''
  try {
    const res = await redeemLoyaltyToWallet(redeemPoints.value)
    redeemSuccess.value = `Converted ${res.pointsRedeemed} points to ${walletData.value?.currency || 'SAR'} ${res.walletCredited}`
    redeemPoints.value = 0
    // Refresh data
    await loadData()
  } catch (err: any) {
    redeemError.value = err?.response?.data?.message || err.message || 'Failed to redeem'
  } finally {
    isRedeeming.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.account-content-inner { flex: 1; }
.header-row { margin-bottom: 2rem; }
.header-row h2 { font-size: 1.5rem; color: #111827; margin: 0; }

.wallet-loyalty-container { display: flex; flex-direction: column; gap: 1.5rem; }

/* Balance Cards */
.cards-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

.balance-card {
  display: flex; align-items: center; gap: 1.25rem;
  padding: 2rem; border-radius: 16px; color: white;
  box-shadow: 0 8px 24px -4px rgba(0,0,0,0.2);
  position: relative; overflow: hidden;
}
.balance-card::after {
  content: ''; position: absolute; top: -40%; right: -20%;
  width: 200px; height: 200px; border-radius: 50%;
  background: rgba(255,255,255,0.08);
}

.wallet-card { background: linear-gradient(135deg, #1e293b, #334155); }
.loyalty-card { background: linear-gradient(135deg, #7c3aed, #a855f7); }

.card-icon {
  background: rgba(255,255,255,0.15); padding: 1rem;
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
}
.card-details { display: flex; flex-direction: column; gap: 0.25rem; }
.card-label { font-size: 0.875rem; opacity: 0.8; }
.card-value { font-size: 2rem; font-weight: 800; line-height: 1.2; }
.card-value small { font-size: 1rem; font-weight: 500; opacity: 0.7; }

/* Redeem Section */
.redeem-section {
  background: white; border-radius: 12px; padding: 1.5rem;
  border: 1px solid #e5e7eb;
}
.redeem-section h3 { margin: 0 0 0.5rem; color: #111827; font-size: 1.1rem; }
.redeem-info { margin: 0 0 1rem; color: #6b7280; font-size: 0.875rem; }
.redeem-form { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.input-group { display: flex; align-items: center; gap: 0.75rem; }
.redeem-input {
  width: 160px; padding: 0.625rem 1rem; border: 1px solid #d1d5db;
  border-radius: 8px; font-size: 0.95rem;
}
.redeem-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
.redeem-preview { color: #7c3aed; font-weight: 600; font-size: 0.95rem; }
.btn-redeem {
  padding: 0.625rem 1.5rem; background: #7c3aed; color: white;
  border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
  transition: background 0.2s;
}
.btn-redeem:hover:not(:disabled) { background: #6d28d9; }
.btn-redeem:disabled { opacity: 0.5; cursor: not-allowed; }

.error-msg { color: #ef4444; font-size: 0.875rem; margin: 0.75rem 0 0; }
.success-msg { color: #10b981; font-size: 0.875rem; margin: 0.75rem 0 0; }

/* Tabs */
.tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; }
.tab-btn {
  padding: 0.75rem 1.5rem; border: none; background: none;
  font-weight: 600; color: #6b7280; cursor: pointer;
  border-bottom: 2px solid transparent; margin-bottom: -2px;
  transition: all 0.2s;
}
.tab-btn.active { color: #111827; border-bottom-color: #7c3aed; }
.tab-btn:hover:not(.active) { color: #374151; }

/* Transactions */
.transactions-section {
  background: white; border-radius: 12px; padding: 1.5rem;
  border: 1px solid #e5e7eb;
}
.transactions-table { width: 100%; border-collapse: collapse; }
.transactions-table th {
  text-align: left; padding: 0.875rem 1rem;
  border-bottom: 2px solid #f3f4f6; color: #6b7280; font-size: 0.8rem;
  text-transform: uppercase; letter-spacing: 0.05em;
}
.transactions-table td { padding: 0.875rem 1rem; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.9rem; }

.text-green { color: #10b981; font-weight: 600; }
.text-red { color: #ef4444; font-weight: 600; }

.badge {
  padding: 0.2rem 0.6rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 600; text-transform: capitalize;
}
.badge-earned { background: #d1fae5; color: #065f46; }
.badge-redeemed { background: #fef3c7; color: #92400e; }
.badge-expired { background: #f3f4f6; color: #6b7280; }
.badge-adjusted { background: #dbeafe; color: #1e40af; }

.empty-state, .loading-state { text-align: center; padding: 3rem; color: #6b7280; }
.spinner { width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top-color: #7c3aed; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .cards-row { grid-template-columns: 1fr; }
  .card-value { font-size: 1.5rem; }
  .redeem-form { flex-direction: column; align-items: stretch; }
  .input-group { flex-direction: column; }
  .redeem-input { width: 100%; }
}
</style>
