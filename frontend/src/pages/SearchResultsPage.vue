<template>
  <div class="search-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">{{ $t('breadcrumb.home') }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item breadcrumb-current">{{ $t('search.title') || 'Search' }}</li>
      </ol>
    </nav>

    <div class="container">
      <!-- Search Header -->
      <div class="search-header">
        <h1>{{ $t('search.resultsFor') || 'Search results for' }}: <span class="search-query">"{{ query }}"</span></h1>
        <p v-if="!isLoading && meta">{{ meta.total }} {{ $t('search.resultsFound') || 'results found' }}</p>
      </div>

      <!-- Sort -->
      <div class="search-toolbar" v-if="!isLoading && products.length > 0">
        <div class="sort-wrap">
          <label>{{ $t('category.sortBy') }}:</label>
          <select v-model="sortBy" @change="loadProducts(1)">
            <option value="">{{ $t('category.ourSuggestions') }}</option>
            <option value="best_selling">{{ $t('category.bestSeller') }}</option>
            <option value="top_rated">{{ $t('category.topRated') }}</option>
            <option value="price_high_to_low">{{ $t('category.priceHighToLow') }}</option>
            <option value="price_low_to_high">{{ $t('category.priceLowToHigh') }}</option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="products.length === 0" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3>{{ $t('search.noResults') || 'No results found' }}</h3>
        <p>{{ $t('search.noResultsDesc') || 'Try searching with different keywords.' }}</p>
        <router-link to="/products" class="btn-browse">{{ $t('common.shopNow') }}</router-link>
      </div>

      <!-- Product Grid -->
      <div v-else>
        <div class="products-grid">
          <ProductCard
            v-for="item in mappedProducts"
            :key="item.id"
            :product="item"
          />
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.lastPage > 1" class="pagination">
          <button
            class="pagination-btn"
            :disabled="currentPage <= 1"
            @click="loadProducts(currentPage - 1)"
          >
            &laquo; {{ $t('search.prev') || 'Prev' }}
          </button>
          <span class="pagination-info">{{ currentPage }} / {{ meta.lastPage }}</span>
          <button
            class="pagination-btn"
            :disabled="currentPage >= meta.lastPage"
            @click="loadProducts(currentPage + 1)"
          >
            {{ $t('search.next') || 'Next' }} &raquo;
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '@/components/home/ProductCard.vue'
import { searchProducts } from '@/api/services'
import type { Product, PaginationMeta } from '@/types'

const route = useRoute()
const router = useRouter()

const products = ref<Product[]>([])
const meta = ref<PaginationMeta | undefined>(undefined)
const isLoading = ref(true)
const currentPage = ref(1)
const sortBy = ref('')

const query = computed(() => (route.query.q as string) || '')

const mappedProducts = computed(() => {
  return products.value.map((p: any) => ({
    id: p.id,
    slug: p.slug,
    name: p.name,
    subtitle: p.category?.name || undefined,
    image: p.primaryImage || '',
    price: p.flashSalePrice || p.price,
    oldPrice: p.flashSalePrice ? p.price : p.comparePrice || undefined,
    discount: p.discountPercent || undefined,
    currency: p.currency || 'SAR',
  }))
})

async function loadProducts(page = 1) {
  isLoading.value = true
  currentPage.value = page
  try {
    const result = await searchProducts(query.value, page, 12)
    products.value = result.data
    meta.value = result.meta
  } catch (error) {
    console.error('Search failed:', error)
    products.value = []
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.query.q,
  () => {
    if (query.value) {
      loadProducts(1)
    } else {
      products.value = []
      isLoading.value = false
    }
  }
)

onMounted(() => {
  if (query.value) {
    loadProducts(1)
  } else {
    isLoading.value = false
  }
})
</script>

<style scoped>
.search-page {
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

.search-header {
  margin-bottom: 1.5rem;
}
.search-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 0.25rem;
}
.search-query {
  color: var(--color-primary, #858585);
}
.search-header p {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.search-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1.5rem;
}
.sort-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}
.sort-wrap select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  background: #fff;
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

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2.5rem;
  padding: 1rem 0;
}
.pagination-btn {
  padding: 0.5rem 1.25rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  font-size: 0.875rem;
  cursor: pointer;
  color: var(--store-text-primary, #111827);
  transition: all 0.2s;
}
.pagination-btn:hover:not(:disabled) {
  background: var(--color-primary, #858585);
  color: #fff;
  border-color: var(--color-primary, #858585);
}
.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pagination-info {
  font-size: 0.875rem;
  color: #6b7280;
}
</style>
