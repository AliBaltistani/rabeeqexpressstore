<template>
  <div class="account-content-inner">
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>{{ $t('common.loading') }}</p>
    </div>

    <div v-else class="loyalty-page">
      <!-- ═══ Hero Banner ═══ -->
      <div class="loyalty-hero">
        <div class="hero-bg-circles">
          <div class="circle c1"></div>
          <div class="circle c2"></div>
          <div class="circle c3"></div>
        </div>
        <div class="hero-content">
          <div class="hero-left">
            <div class="hero-star">
              <svg width="56" height="56" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="hero-text">
              <h2>{{ $t('wallet.loyaltySystem') || 'Loyalty System' }}</h2>
              <p>{{ $t('wallet.loyaltySystemDesc') || 'You can now redeem your points and convert them to your store wallet - learn more about the ways to earn!' }}</p>
              <div class="hero-points">
                <span class="hero-points-label">{{ $t('wallet.youHave') || 'You have' }}</span>
                <span class="hero-points-value">{{ loyaltyData?.points || 0 }}</span>
                <span class="hero-points-label">{{ $t('wallet.point') || 'Point' }}</span>
              </div>
            </div>
          </div>
          <button class="btn-exchange" @click="$router.push('/account/wallet')">
            {{ $t('wallet.exchangePoints') || 'Exchange Points' }}
          </button>
        </div>
      </div>

      <!-- ═══ Ways to Get Points ═══ -->
      <section class="section-block">
        <h3 class="section-title">{{ $t('wallet.waysToGetPoints') || 'Ways to get points' }}</h3>
        <div class="ways-grid">
          <div class="way-card">
            <div class="way-icon">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="way-info">
              <span class="way-points">{{ loyaltyData?.profileCompletionPoints || 17 }} {{ $t('wallet.point') || 'Point' }}</span>
              <span class="way-desc">{{ $t('wallet.completeProfile') || 'Complete your personal information' }}</span>
            </div>
            <button class="btn-way" @click="$router.push('/account')" :class="{ 'btn-way--done': loyaltyData?.profileCompleted }" :disabled="loyaltyData?.profileCompleted">
              {{ loyaltyData?.profileCompleted ? '✓' : ($t('wallet.completeInformation') || 'Complete Information') }}
            </button>
          </div>
          <div class="way-card">
            <div class="way-icon">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <div class="way-info">
              <span class="way-points">{{ loyaltyData?.earnRate || 1 }} {{ $t('wallet.point') || 'Point' }}</span>
              <span class="way-desc">{{ $t('wallet.shopNow') || 'Buying from the store' }}</span>
            </div>
            <button class="btn-way" @click="$router.push('/categories')">
              {{ $t('wallet.shopNow') || 'Buying from the store' }}
            </button>
          </div>
          <div class="way-card">
            <div class="way-icon">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <div class="way-info">
              <span class="way-points">{{ loyaltyData?.sharePoints || 50 }} {{ $t('wallet.point') || 'Point' }}</span>
              <span class="way-desc">{{ $t('wallet.shareStoreLink') || 'Share store link' }}</span>
            </div>
            <div class="share-input-group">
              <input type="text" :value="loyaltyData?.storeUrl || 'https://eseven-store.com'" readonly class="share-url-input" ref="shareUrlInput" />
              <button class="btn-copy" @click="copyStoreUrl">
                <svg v-if="!copied" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ Discounts (Dynamic) ═══ -->
      <section v-if="discountRewards.length" class="section-block">
        <h3 class="section-title section-title--gold">{{ $t('wallet.discounts') || 'Discounts' }}</h3>
        <div class="rewards-grid">
          <div v-for="reward in discountRewards" :key="reward.id" class="reward-card">
            <div class="reward-image reward-image--discount">
              <img v-if="reward.image" :src="reward.image" :alt="reward.name" class="reward-img" />
              <span v-else class="reward-badge">{{ reward.discountValue ? Math.round(reward.discountValue) + '%' : '🏷️' }}</span>
            </div>
            <div class="reward-body">
              <h4>{{ reward.name }}</h4>
              <p class="reward-desc">{{ reward.description }}</p>
              <span class="reward-cost">{{ reward.pointsCost }} {{ $t('wallet.point') || 'Point' }}</span>
              <button
                class="btn-redeem"
                :disabled="(loyaltyData?.points || 0) < reward.pointsCost || redeemingId === reward.id"
                @click="openRedeemConfirm(reward)"
              >
                <template v-if="redeemingId === reward.id">
                  <span class="btn-spinner"></span>
                </template>
                <template v-else>
                  {{ (loyaltyData?.points || 0) < reward.pointsCost ? ($t('wallet.notEnoughPoints') || 'Not enough points') : ($t('wallet.redeem') || 'Redeem') }}
                </template>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ Free Shipping (Dynamic) ═══ -->
      <section v-if="shippingRewards.length" class="section-block">
        <h3 class="section-title section-title--gold">{{ $t('wallet.freeShipping') || 'Free shipping' }}</h3>
        <div class="rewards-grid">
          <div v-for="reward in shippingRewards" :key="reward.id" class="reward-card">
            <div class="reward-image reward-image--shipping">
              <img v-if="reward.image" :src="reward.image" :alt="reward.name" class="reward-img" />
              <span v-else class="reward-badge">🚚</span>
            </div>
            <div class="reward-body">
              <h4>{{ reward.name }}</h4>
              <p class="reward-desc">{{ reward.description }}</p>
              <span class="reward-cost">{{ reward.pointsCost }} {{ $t('wallet.point') || 'Point' }}</span>
              <button
                class="btn-redeem btn-redeem--shipping"
                :disabled="(loyaltyData?.points || 0) < reward.pointsCost || redeemingId === reward.id"
                @click="openRedeemConfirm(reward)"
              >
                <template v-if="redeemingId === reward.id">
                  <span class="btn-spinner"></span>
                </template>
                <template v-else>
                  {{ (loyaltyData?.points || 0) < reward.pointsCost ? ($t('wallet.notEnoughPoints') || 'Not enough points') : ($t('wallet.redeem') || 'Redeem') }}
                </template>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ My Coupons ═══ -->
      <section v-if="myCoupons.length" class="section-block">
        <h3 class="section-title section-title--green">{{ $t('wallet.myCoupons') || 'My Coupons' }}</h3>
        <div class="coupons-list">
          <div v-for="coupon in myCoupons" :key="coupon.id" class="coupon-item" :class="{ 'coupon-item--used': coupon.status === 'used', 'coupon-item--expired': coupon.status === 'expired' }">
            <div class="coupon-left">
              <span class="coupon-type-icon">{{ coupon.type === 'free_shipping' ? '🚚' : '🏷️' }}</span>
              <div class="coupon-info">
                <div class="coupon-name">{{ coupon.rewardName || coupon.name }}</div>
                <div class="coupon-meta">
                  <span v-if="coupon.type !== 'free_shipping'" class="coupon-value">
                    {{ coupon.type === 'percentage' ? coupon.value + '% OFF' : coupon.value + ' OFF' }}
                  </span>
                  <span v-else class="coupon-value coupon-value--shipping">{{ $t('wallet.freeShipping') || 'Free Shipping' }}</span>
                  <span class="coupon-expiry" v-if="coupon.expiresAt">
                    {{ coupon.status === 'expired' ? ($t('wallet.expired') || 'Expired') : ($t('wallet.expiresOn') || 'Expires') + ' ' + formatDate(coupon.expiresAt) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="coupon-right">
              <span class="coupon-status-badge" :class="`coupon-status--${coupon.status}`">
                {{ coupon.status === 'active' ? ($t('wallet.active') || 'Active') : coupon.status === 'used' ? ($t('wallet.used') || 'Used') : ($t('wallet.expired') || 'Expired') }}
              </span>
              <div v-if="coupon.status === 'active'" class="coupon-code-group">
                <code class="coupon-code">{{ coupon.code }}</code>
                <button class="btn-copy-sm" @click="copyCouponCode(coupon.code)" :title="$t('wallet.copiedToClipboard') || 'Copy'">
                  <svg v-if="copiedCode !== coupon.code" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══ Redeem Confirm Modal ═══ -->
      <Teleport to="body">
        <Transition name="modal-fade">
          <div v-if="showConfirmModal" class="modal-overlay" @click.self="showConfirmModal = false">
            <div class="modal-box">
              <h3 class="modal-title">{{ $t('wallet.confirmRedeem') || 'Confirm Redemption' }}</h3>
              <p class="modal-desc">
                {{ $t('wallet.redeemConfirmMsg') || 'Are you sure you want to redeem this reward?' }}
              </p>
              <div v-if="confirmReward" class="modal-reward-summary">
                <div class="modal-reward-name">{{ confirmReward.name }}</div>
                <div class="modal-reward-cost">-{{ confirmReward.pointsCost }} {{ $t('wallet.point') || 'Points' }}</div>
              </div>
              <p v-if="redeemError" class="modal-error">{{ redeemError }}</p>
              <!-- Success state -->
              <div v-if="redeemSuccess" class="modal-success">
                <div class="success-icon">✓</div>
                <p class="success-msg">{{ $t('wallet.redeemSuccess') || 'Reward redeemed successfully!' }}</p>
                <div class="success-coupon">
                  <span class="success-label">{{ $t('wallet.yourCouponCode') || 'Your coupon code' }}:</span>
                  <code class="success-code">{{ redeemSuccess.couponCode }}</code>
                  <button class="btn-copy-sm" @click="copyCouponCode(redeemSuccess.couponCode)">
                    <svg v-if="copiedCode !== redeemSuccess.couponCode" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                </div>
                <button class="btn-modal btn-modal--primary" @click="closeAndRefresh">{{ $t('common.ok') || 'OK' }}</button>
              </div>
              <div v-else class="modal-actions">
                <button class="btn-modal btn-modal--cancel" @click="showConfirmModal = false">{{ $t('common.cancel') || 'Cancel' }}</button>
                <button class="btn-modal btn-modal--primary" :disabled="redeemingId !== null" @click="executeRedeem">
                  <span v-if="redeemingId !== null" class="btn-spinner"></span>
                  <span v-else>{{ $t('wallet.redeem') || 'Redeem' }}</span>
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchLoyaltyPoints, fetchLoyaltyRewards, redeemLoyaltyReward, fetchMyCoupons } from '@/api/services'

const isLoading = ref(true)
const loyaltyData = ref<any>(null)
const discountRewards = ref<any[]>([])
const shippingRewards = ref<any[]>([])
const myCoupons = ref<any[]>([])
const copied = ref(false)
const copiedCode = ref('')
const shareUrlInput = ref<HTMLInputElement | null>(null)

// Redeem state
const redeemingId = ref<number | null>(null)
const showConfirmModal = ref(false)
const confirmReward = ref<any>(null)
const redeemError = ref('')
const redeemSuccess = ref<any>(null)

async function loadData() {
  isLoading.value = true
  try {
    const [loyaltyRes, rewardsRes, couponsRes] = await Promise.all([
      fetchLoyaltyPoints(),
      fetchLoyaltyRewards(),
      fetchMyCoupons().catch(() => ({ coupons: [] })),
    ])
    loyaltyData.value = loyaltyRes
    discountRewards.value = rewardsRes?.discounts || []
    shippingRewards.value = rewardsRes?.freeShipping || []
    myCoupons.value = couponsRes?.coupons || []
  } catch (error) {
    console.error('Failed to load loyalty data', error)
  } finally {
    isLoading.value = false
  }
}

function openRedeemConfirm(reward: any) {
  confirmReward.value = reward
  redeemError.value = ''
  redeemSuccess.value = null
  showConfirmModal.value = true
}

async function executeRedeem() {
  if (!confirmReward.value) return
  redeemingId.value = confirmReward.value.id
  redeemError.value = ''
  try {
    const result = await redeemLoyaltyReward(confirmReward.value.id)
    redeemSuccess.value = result
    // Update points balance locally
    if (loyaltyData.value) {
      loyaltyData.value.points = result.loyaltyBalance
    }
  } catch (e: any) {
    redeemError.value = e?.response?.data?.message || e?.message || 'Failed to redeem reward'
  } finally {
    redeemingId.value = null
  }
}

async function closeAndRefresh() {
  showConfirmModal.value = false
  redeemSuccess.value = null
  confirmReward.value = null
  await loadData()
}

function copyStoreUrl() {
  const url = loyaltyData.value?.storeUrl || 'https://eseven-store.com'
  navigator.clipboard.writeText(url).then(() => {
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }).catch(() => {
    if (shareUrlInput.value) {
      shareUrlInput.value.select()
      document.execCommand('copy')
      copied.value = true
      setTimeout(() => { copied.value = false }, 2000)
    }
  })
}

function copyCouponCode(code: string) {
  navigator.clipboard.writeText(code).then(() => {
    copiedCode.value = code
    setTimeout(() => { copiedCode.value = '' }, 2000)
  })
}

function formatDate(isoStr: string) {
  try {
    return new Date(isoStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
  } catch { return isoStr }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.account-content-inner { flex: 1; }
.loyalty-page { display: flex; flex-direction: column; gap: 2.5rem; }

/* ═══ Hero Banner ═══ */
.loyalty-hero {
  position: relative;
  background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
  border-radius: 20px;
  padding: 2.5rem 3rem;
  color: white;
  overflow: hidden;
  min-height: 180px;
}
.hero-bg-circles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.circle { position: absolute; border-radius: 50%; opacity: 0.08; background: #f59e0b; }
.c1 { width: 200px; height: 200px; top: -60px; right: -30px; opacity: 0.12; }
.c2 { width: 120px; height: 120px; bottom: -40px; right: 80px; opacity: 0.06; }
.c3 { width: 80px; height: 80px; top: 20px; right: 160px; opacity: 0.1; }

.hero-content {
  position: relative; z-index: 1;
  display: flex; align-items: center; justify-content: space-between; gap: 2rem;
}
.hero-left { display: flex; align-items: flex-start; gap: 1.5rem; flex: 1; }
.hero-star {
  width: 72px; height: 72px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 50%; color: white;
  box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
}
.hero-text { display: flex; flex-direction: column; gap: 0.5rem; }
.hero-text h2 { margin: 0; font-size: 1.5rem; font-weight: 800; }
.hero-text p { margin: 0; font-size: 0.875rem; opacity: 0.75; line-height: 1.6; max-width: 400px; }
.hero-points { display: flex; align-items: baseline; gap: 0.5rem; margin-top: 0.25rem; }
.hero-points-label { font-size: 0.875rem; opacity: 0.8; color: #f59e0b; }
.hero-points-value { font-size: 2.5rem; font-weight: 900; color: #f59e0b; line-height: 1; }

.btn-exchange {
  padding: 0.75rem 1.75rem;
  background: #374151; color: white;
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 10px; font-weight: 600; font-size: 0.9rem;
  cursor: pointer; white-space: nowrap; transition: all 0.2s; flex-shrink: 0;
}
.btn-exchange:hover { background: #4b5563; border-color: rgba(255,255,255,0.3); transform: translateY(-1px); }

/* ═══ Section ═══ */
.section-block { display: flex; flex-direction: column; gap: 1.25rem; }
.section-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #111827; padding-bottom: 0.5rem; }
.section-title--gold { color: #b45309; }
.section-title--green { color: #047857; }

/* ═══ Ways Grid ═══ */
.ways-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.way-card {
  background: white; border: 1px solid #e5e7eb; border-radius: 14px; padding: 1.5rem;
  display: flex; flex-direction: column; gap: 1rem; transition: box-shadow 0.2s, transform 0.2s;
}
.way-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-2px); }
.way-icon {
  width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;
  background: #f3f4f6; border-radius: 12px; color: #6b7280;
}
.way-info { display: flex; flex-direction: column; gap: 0.25rem; flex: 1; }
.way-points { font-size: 1.1rem; font-weight: 800; color: #111827; }
.way-desc { font-size: 0.8125rem; color: #6b7280; line-height: 1.5; }

.btn-way {
  padding: 0.5rem 1rem; background: white; color: #374151;
  border: 1px solid #d1d5db; border-radius: 8px; font-weight: 500; font-size: 0.8125rem;
  cursor: pointer; transition: all 0.2s; text-align: center;
}
.btn-way:hover:not(:disabled) { border-color: #9ca3af; background: #f9fafb; }
.btn-way--done { background: #d1fae5 !important; color: #065f46 !important; border-color: #a7f3d0 !important; cursor: default; font-size: 1rem; }

.share-input-group { display: flex; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
.share-url-input { flex: 1; padding: 0.5rem 0.75rem; border: none; outline: none; font-size: 0.8rem; color: #6b7280; background: #f9fafb; min-width: 0; text-overflow: ellipsis; }
.btn-copy {
  display: flex; align-items: center; justify-content: center; padding: 0.5rem 0.75rem;
  background: white; border: none; border-left: 1px solid #d1d5db; cursor: pointer; color: #6b7280; transition: background 0.2s;
}
.btn-copy:hover { background: #f3f4f6; }

/* ═══ Reward Cards ═══ */
.rewards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem; }
.reward-card { background: white; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; transition: box-shadow 0.2s, transform 0.2s; }
.reward-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-2px); }
.reward-image { height: 140px; display: flex; align-items: center; justify-content: center; position: relative; }
.reward-image--discount { background: linear-gradient(135deg, #fef3c7, #fde68a); }
.reward-image--shipping { background: linear-gradient(135deg, #ede9fe, #ddd6fe); }
.reward-img { width: 100%; height: 100%; object-fit: cover; }
.reward-badge { font-size: 3rem; font-weight: 900; color: #b45309; opacity: 0.85; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.reward-image--shipping .reward-badge { font-size: 3.5rem; color: #7c3aed; }

.reward-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }
.reward-body h4 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #111827; }
.reward-desc { margin: 0; font-size: 0.75rem; color: #6b7280; line-height: 1.6; }
.reward-cost {
  display: inline-block; padding: 0.25rem 0.75rem;
  background: #f3f4f6; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; color: #374151;
  align-self: flex-start;
}

/* Redeem Button */
.btn-redeem {
  margin-top: 0.5rem; padding: 0.6rem 1.25rem;
  background: linear-gradient(135deg, #f59e0b, #d97706); color: white;
  border: none; border-radius: 10px; font-weight: 700; font-size: 0.85rem;
  cursor: pointer; transition: all 0.2s; text-align: center;
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
}
.btn-redeem:hover:not(:disabled) { background: linear-gradient(135deg, #d97706, #b45309); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
.btn-redeem:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
.btn-redeem--shipping { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.btn-redeem--shipping:hover:not(:disabled) { background: linear-gradient(135deg, #7c3aed, #6d28d9); box-shadow: 0 4px 12px rgba(124,58,237,0.3); }

.btn-spinner {
  display: inline-block; width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,0.3); border-top-color: white;
  border-radius: 50%; animation: spin 0.6s linear infinite;
}

/* ═══ My Coupons ═══ */
.coupons-list { display: flex; flex-direction: column; gap: 0.75rem; }
.coupon-item {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem 1.25rem;
  transition: box-shadow 0.2s;
}
.coupon-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.coupon-item--used { opacity: 0.6; background: #f9fafb; }
.coupon-item--expired { opacity: 0.5; background: #fef2f2; border-color: #fecaca; }

.coupon-left { display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 0; }
.coupon-type-icon { font-size: 1.75rem; flex-shrink: 0; }
.coupon-info { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.coupon-name { font-weight: 600; font-size: 0.9rem; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.coupon-meta { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.coupon-value { font-size: 0.8rem; font-weight: 700; color: #059669; }
.coupon-value--shipping { color: #7c3aed; }
.coupon-expiry { font-size: 0.75rem; color: #9ca3af; }

.coupon-right { display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem; flex-shrink: 0; }
.coupon-status-badge {
  font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.6rem;
  border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px;
}
.coupon-status--active { background: #d1fae5; color: #065f46; }
.coupon-status--used { background: #fef3c7; color: #92400e; }
.coupon-status--expired { background: #fee2e2; color: #991b1b; }

.coupon-code-group { display: flex; align-items: center; gap: 0.5rem; }
.coupon-code {
  font-family: monospace; font-size: 0.8rem; font-weight: 700; color: #dc2626;
  background: #fef2f2; padding: 0.25rem 0.5rem; border-radius: 6px;
  letter-spacing: 0.5px;
}
.btn-copy-sm {
  display: flex; align-items: center; justify-content: center; padding: 0.25rem;
  background: none; border: none; cursor: pointer; color: #6b7280; transition: color 0.2s;
}
.btn-copy-sm:hover { color: #374151; }

/* ═══ Confirm Modal ═══ */
.modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-box {
  background: white; border-radius: 16px; padding: 2rem;
  max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.modal-title { margin: 0 0 0.75rem; font-size: 1.1rem; font-weight: 700; color: #111827; }
.modal-desc { margin: 0 0 1rem; font-size: 0.875rem; color: #6b7280; line-height: 1.5; }
.modal-reward-summary {
  display: flex; justify-content: space-between; align-items: center;
  background: #f9fafb; border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 1rem;
}
.modal-reward-name { font-weight: 600; font-size: 0.9rem; color: #111827; }
.modal-reward-cost { font-weight: 700; font-size: 0.9rem; color: #dc2626; }
.modal-error { margin: 0 0 1rem; font-size: 0.8rem; color: #dc2626; background: #fef2f2; padding: 0.5rem 0.75rem; border-radius: 8px; }
.modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
.btn-modal {
  padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.875rem;
  cursor: pointer; transition: all 0.2s; border: none;
  display: flex; align-items: center; gap: 0.5rem;
}
.btn-modal--cancel { background: #f3f4f6; color: #374151; }
.btn-modal--cancel:hover { background: #e5e7eb; }
.btn-modal--primary { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.btn-modal--primary:hover:not(:disabled) { background: linear-gradient(135deg, #d97706, #b45309); }
.btn-modal--primary:disabled { opacity: 0.5; cursor: not-allowed; }

/* Success state */
.modal-success { text-align: center; padding: 0.5rem 0; }
.success-icon { font-size: 2.5rem; color: #10b981; margin-bottom: 0.75rem; }
.success-msg { margin: 0 0 1rem; font-size: 0.95rem; font-weight: 600; color: #065f46; }
.success-coupon {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  background: #f0fdf4; border: 1px dashed #a7f3d0; border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 1.25rem;
}
.success-label { font-size: 0.8rem; color: #6b7280; }
.success-code { font-family: monospace; font-size: 1rem; font-weight: 700; color: #059669; letter-spacing: 1px; }

/* Transitions */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* ═══ Loading ═══ */
.loading-state { text-align: center; padding: 4rem; color: #6b7280; }
.spinner { width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top-color: #f59e0b; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ═══ Responsive ═══ */
@media (max-width: 768px) {
  .loyalty-hero { padding: 1.75rem 1.5rem; }
  .hero-content { flex-direction: column; align-items: flex-start; }
  .hero-left { flex-direction: column; align-items: flex-start; }
  .hero-points-value { font-size: 2rem; }
  .ways-grid { grid-template-columns: 1fr; }
  .rewards-grid { grid-template-columns: 1fr; }
  .coupon-item { flex-direction: column; align-items: flex-start; }
  .coupon-right { align-items: flex-start; width: 100%; flex-direction: row; justify-content: space-between; }
}
</style>
