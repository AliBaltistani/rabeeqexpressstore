<template>
  <div class="flash-sale-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">{{ $t('breadcrumb.home') }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item breadcrumb-current">{{ $t('flashSale.title') || 'Flash Sale' }}</li>
      </ol>
    </nav>

    <div class="container">
      <!-- Loading -->
      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>

      <!-- No active sale -->
      <div v-else-if="!flashSale" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
        <h3>{{ $t('flashSale.noActiveSale') || 'No active flash sale right now' }}</h3>
        <p>{{ $t('flashSale.noActiveSaleDesc') || 'Check back soon for amazing deals!' }}</p>
        <router-link to="/products" class="btn-browse">{{ $t('common.shopNow') }}</router-link>
      </div>

      <!-- Flash Sale Content -->
      <div v-else>
        <!-- Sale Header with Countdown -->
        <div class="flash-header">
          <div class="flash-title-wrap">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            <h1>{{ flashSale.name }}</h1>
          </div>
          <div class="countdown" v-if="countdown.total > 0">
            <div class="countdown-item">
              <span class="countdown-num">{{ String(countdown.days).padStart(2, '0') }}</span>
              <span class="countdown-label">{{ $t('flashSale.days') || 'Days' }}</span>
            </div>
            <span class="countdown-sep">:</span>
            <div class="countdown-item">
              <span class="countdown-num">{{ String(countdown.hours).padStart(2, '0') }}</span>
              <span class="countdown-label">{{ $t('flashSale.hours') || 'Hours' }}</span>
            </div>
            <span class="countdown-sep">:</span>
            <div class="countdown-item">
              <span class="countdown-num">{{ String(countdown.minutes).padStart(2, '0') }}</span>
              <span class="countdown-label">{{ $t('flashSale.minutes') || 'Min' }}</span>
            </div>
            <span class="countdown-sep">:</span>
            <div class="countdown-item">
              <span class="countdown-num">{{ String(countdown.seconds).padStart(2, '0') }}</span>
              <span class="countdown-label">{{ $t('flashSale.seconds') || 'Sec' }}</span>
            </div>
          </div>
          <div v-else class="sale-ended">
            <p>{{ $t('flashSale.ended') || 'This sale has ended.' }}</p>
          </div>
        </div>

        <!-- Product Grid -->
        <div class="products-grid">
          <ProductCard
            v-for="item in mappedProducts"
            :key="item.id"
            :product="item"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import ProductCard from '@/components/home/ProductCard.vue'
import { fetchActiveFlashSale } from '@/api/services'

const flashSale = ref<any>(null)
const isLoading = ref(true)
let timerInterval: ReturnType<typeof setInterval> | null = null

const countdown = ref({ total: 0, days: 0, hours: 0, minutes: 0, seconds: 0 })

function updateCountdown() {
  if (!flashSale.value?.endsAt) return
  const end = new Date(flashSale.value.endsAt).getTime()
  const now = Date.now()
  const diff = end - now

  if (diff <= 0) {
    countdown.value = { total: 0, days: 0, hours: 0, minutes: 0, seconds: 0 }
    if (timerInterval) clearInterval(timerInterval)
    return
  }

  countdown.value = {
    total: diff,
    days: Math.floor(diff / (1000 * 60 * 60 * 24)),
    hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
    minutes: Math.floor((diff / (1000 * 60)) % 60),
    seconds: Math.floor((diff / 1000) % 60),
  }
}

const mappedProducts = computed(() => {
  if (!flashSale.value?.products) return []
  return flashSale.value.products
    .filter((fp: any) => fp.product)
    .map((fp: any) => {
      const p = fp.product
      return {
        id: p.id,
        slug: p.slug,
        name: p.name,
        subtitle: p.category?.name || undefined,
        image: p.primaryImage || '',
        price: fp.salePrice,
        oldPrice: fp.originalPrice,
        discount: fp.discountPercent,
        currency: p.currency || 'SAR',
      }
    })
})

onMounted(async () => {
  try {
    const data = await fetchActiveFlashSale()
    if (data && data.id) {
      flashSale.value = data
      updateCountdown()
      timerInterval = setInterval(updateCountdown, 1000)
    }
  } catch (error) {
    console.error('Failed to load flash sale:', error)
  } finally {
    isLoading.value = false
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.flash-sale-page {
  padding-bottom: 4rem;
}
.breadcrumbs {
  padding: 1.5rem 1rem;
}
.breadcrumb-list {
  display: flex;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 0.5rem;
  font-size: 0.875rem;
}
.breadcrumb-item a {
  color: #6b7280;
  text-decoration: none;
}
.breadcrumb-item a:hover {
  color: var(--color-primary, #858585);
}
.breadcrumb-current {
  color: var(--store-text-primary, #111827);
  font-weight: 500;
}
.breadcrumb-arrow {
  color: #d1d5db;
  display: flex;
  align-items: center;
}

.loading-state {
  text-align: center;
  padding: 6rem 2rem;
  color: #6b7280;
}
.empty-state {
  text-align: center;
  padding: 6rem 2rem;
}
.empty-state svg {
  margin-bottom: 1.5rem;
}
.empty-state h3 {
  font-size: 1.25rem;
  color: #111827;
  margin: 0 0 0.5rem;
}
.empty-state p {
  color: #6b7280;
  margin: 0 0 1.5rem;
}
.btn-browse {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
}

.flash-header {
  background: linear-gradient(135deg, #1e1b4b, #4338ca);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  color: #fff;
}
@media (min-width: 768px) {
  .flash-header {
    flex-direction: row;
    justify-content: space-between;
    padding: 2rem 3rem;
  }
}
.flash-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.flash-title-wrap h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
}
.flash-title-wrap svg {
  color: #fbbf24;
}

.countdown {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.countdown-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(255,255,255,0.15);
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  min-width: 52px;
}
.countdown-num {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}
.countdown-label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  opacity: 0.8;
  letter-spacing: 0.5px;
}
.countdown-sep {
  font-size: 1.5rem;
  font-weight: 700;
  opacity: 0.6;
}

.sale-ended {
  background: rgba(255,255,255,0.15);
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
}
.sale-ended p {
  margin: 0;
  font-weight: 500;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}
@media (min-width: 640px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (min-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style>
