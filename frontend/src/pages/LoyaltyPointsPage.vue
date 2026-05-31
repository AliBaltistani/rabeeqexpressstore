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
          <!-- Complete Profile -->
          <div class="way-card">
            <div class="way-icon">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="way-info">
              <span class="way-points">{{ loyaltyData?.profileCompletionPoints || 17 }} {{ $t('wallet.point') || 'Point' }}</span>
              <span class="way-desc">{{ $t('wallet.completeProfile') || 'Complete your personal information' }}</span>
            </div>
            <button
              class="btn-way"
              @click="$router.push('/account')"
              :class="{ 'btn-way--done': loyaltyData?.profileCompleted }"
              :disabled="loyaltyData?.profileCompleted"
            >
              {{ loyaltyData?.profileCompleted ? '✓' : ($t('wallet.completeInformation') || 'Complete Information') }}
            </button>
          </div>

          <!-- Buy from Store -->
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

          <!-- Share Store Link -->
          <div class="way-card">
            <div class="way-icon">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <div class="way-info">
              <span class="way-points">{{ loyaltyData?.sharePoints || 50 }} {{ $t('wallet.point') || 'Point' }}</span>
              <span class="way-desc">{{ $t('wallet.shareStoreLink') || 'Share store link' }}</span>
            </div>
            <div class="share-input-group">
              <input
                type="text"
                :value="loyaltyData?.storeUrl || 'https://eseven-store.com'"
                readonly
                class="share-url-input"
                ref="shareUrlInput"
              />
              <button class="btn-copy" @click="copyStoreUrl" :title="$t('wallet.copiedToClipboard') || 'Copy'">
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
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchLoyaltyPoints, fetchLoyaltyRewards } from '@/api/services'

const isLoading = ref(true)
const loyaltyData = ref<any>(null)
const discountRewards = ref<any[]>([])
const shippingRewards = ref<any[]>([])
const copied = ref(false)
const shareUrlInput = ref<HTMLInputElement | null>(null)

async function loadData() {
  isLoading.value = true
  try {
    const [loyaltyRes, rewardsRes] = await Promise.all([
      fetchLoyaltyPoints(),
      fetchLoyaltyRewards(),
    ])
    loyaltyData.value = loyaltyRes
    discountRewards.value = rewardsRes?.discounts || []
    shippingRewards.value = rewardsRes?.freeShipping || []
  } catch (error) {
    console.error('Failed to load loyalty data', error)
  } finally {
    isLoading.value = false
  }
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
.hero-bg-circles {
  position: absolute; inset: 0; pointer-events: none; overflow: hidden;
}
.circle {
  position: absolute; border-radius: 50%; opacity: 0.08;
  background: #f59e0b;
}
.c1 { width: 200px; height: 200px; top: -60px; right: -30px; opacity: 0.12; }
.c2 { width: 120px; height: 120px; bottom: -40px; right: 80px; opacity: 0.06; }
.c3 { width: 80px; height: 80px; top: 20px; right: 160px; opacity: 0.1; }

.hero-content {
  position: relative; z-index: 1;
  display: flex; align-items: center; justify-content: space-between;
  gap: 2rem;
}
.hero-left { display: flex; align-items: flex-start; gap: 1.5rem; flex: 1; }

.hero-star {
  width: 72px; height: 72px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 50%;
  color: white;
  box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
}
.hero-text { display: flex; flex-direction: column; gap: 0.5rem; }
.hero-text h2 { margin: 0; font-size: 1.5rem; font-weight: 800; }
.hero-text p {
  margin: 0; font-size: 0.875rem; opacity: 0.75;
  line-height: 1.6; max-width: 400px;
}
.hero-points { display: flex; align-items: baseline; gap: 0.5rem; margin-top: 0.25rem; }
.hero-points-label { font-size: 0.875rem; opacity: 0.8; color: #f59e0b; }
.hero-points-value {
  font-size: 2.5rem; font-weight: 900; color: #f59e0b;
  line-height: 1;
}

.btn-exchange {
  padding: 0.75rem 1.75rem;
  background: #374151; color: white;
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 10px; font-weight: 600; font-size: 0.9rem;
  cursor: pointer; white-space: nowrap;
  transition: all 0.2s;
  flex-shrink: 0;
}
.btn-exchange:hover {
  background: #4b5563;
  border-color: rgba(255,255,255,0.3);
  transform: translateY(-1px);
}

/* ═══ Section Blocks ═══ */
.section-block { display: flex; flex-direction: column; gap: 1.25rem; }
.section-title {
  margin: 0; font-size: 1.1rem; font-weight: 700; color: #111827;
  padding-bottom: 0.5rem;
}
.section-title--gold { color: #b45309; }

/* ═══ Ways to Get Points ═══ */
.ways-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;
}
.way-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 1.5rem;
  display: flex; flex-direction: column; gap: 1rem;
  transition: box-shadow 0.2s, transform 0.2s;
}
.way-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.way-icon {
  width: 48px; height: 48px;
  display: flex; align-items: center; justify-content: center;
  background: #f3f4f6; border-radius: 12px;
  color: #6b7280;
}
.way-info { display: flex; flex-direction: column; gap: 0.25rem; flex: 1; }
.way-points { font-size: 1.1rem; font-weight: 800; color: #111827; }
.way-desc { font-size: 0.8125rem; color: #6b7280; line-height: 1.5; }

.btn-way {
  padding: 0.5rem 1rem;
  background: white; color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 8px; font-weight: 500; font-size: 0.8125rem;
  cursor: pointer; transition: all 0.2s;
  text-align: center;
}
.btn-way:hover:not(:disabled) {
  border-color: #9ca3af; background: #f9fafb;
}
.btn-way--done {
  background: #d1fae5 !important; color: #065f46 !important;
  border-color: #a7f3d0 !important; cursor: default;
  font-size: 1rem;
}

/* Share URL */
.share-input-group {
  display: flex; border: 1px solid #d1d5db; border-radius: 8px;
  overflow: hidden;
}
.share-url-input {
  flex: 1; padding: 0.5rem 0.75rem;
  border: none; outline: none;
  font-size: 0.8rem; color: #6b7280;
  background: #f9fafb;
  min-width: 0;
  text-overflow: ellipsis;
}
.btn-copy {
  display: flex; align-items: center; justify-content: center;
  padding: 0.5rem 0.75rem;
  background: white; border: none;
  border-left: 1px solid #d1d5db;
  cursor: pointer; color: #6b7280;
  transition: background 0.2s;
}
.btn-copy:hover { background: #f3f4f6; }

/* ═══ Reward Cards ═══ */
.rewards-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem;
}
.reward-card {
  background: white; border: 1px solid #e5e7eb;
  border-radius: 14px; overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
}
.reward-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.reward-image {
  height: 140px; display: flex; align-items: center; justify-content: center;
  position: relative;
}
.reward-image--discount {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
}
.reward-image--shipping {
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
}
.reward-img {
  width: 100%; height: 100%; object-fit: cover;
}
.reward-badge {
  font-size: 3rem; font-weight: 900;
  color: #b45309; opacity: 0.85;
  text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.reward-image--shipping .reward-badge {
  font-size: 3.5rem; color: #7c3aed;
}

.reward-body { padding: 1.25rem; }
.reward-body h4 {
  margin: 0 0 0.5rem; font-size: 0.95rem;
  font-weight: 700; color: #111827;
}
.reward-desc {
  margin: 0 0 0.75rem; font-size: 0.75rem;
  color: #6b7280; line-height: 1.6;
}
.reward-cost {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f3f4f6; border-radius: 9999px;
  font-size: 0.8rem; font-weight: 600; color: #374151;
}

/* ═══ Loading ═══ */
.loading-state { text-align: center; padding: 4rem; color: #6b7280; }
.spinner {
  width: 32px; height: 32px;
  border: 3px solid #e5e7eb; border-top-color: #f59e0b;
  border-radius: 50%; animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ═══ Responsive ═══ */
@media (max-width: 768px) {
  .loyalty-hero { padding: 1.75rem 1.5rem; }
  .hero-content { flex-direction: column; align-items: flex-start; }
  .hero-left { flex-direction: column; align-items: flex-start; }
  .hero-points-value { font-size: 2rem; }
  .ways-grid { grid-template-columns: 1fr; }
  .rewards-grid { grid-template-columns: 1fr; }
}
</style>
