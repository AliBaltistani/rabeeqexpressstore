<template>
  <div class="category-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">{{ $t('breadcrumb.home') || 'Home' }}</router-link>
        </li>
        <li class="breadcrumb-arrow">
          <svg width="16" height="16" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-item breadcrumb-current">{{ categoryTitle }}</li>
      </ol>
    </nav>

    <div class="container">
      <div class="category-layout">
        <!-- ============ SIDEBAR FILTERS ============ -->
        <aside
          class="filters-sidebar"
          :class="{ 'filters-sidebar--open': showMobileFilters }"
        >
          <!-- Close button (mobile) -->
          <button class="filters-close-btn" @click="showMobileFilters = false" aria-label="Close">✕</button>

          <!-- Categories -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('categories')">
              <span>{{ $t('category.categories') || 'Categories' }}</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.categories }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.categories">
              <!-- Sidebar shimmer when filters loading -->
              <template v-if="isFiltersLoading">
                <div class="sidebar-shimmer-item" v-for="n in 5" :key="'cat-skel-'+n">
                  <div class="shimmer-box shimmer-radio"></div>
                  <div class="shimmer-box shimmer-text" :style="{ width: (50 + Math.random() * 40) + '%' }"></div>
                </div>
              </template>
              <template v-else>
                <div class="filter-widget__search">
                  <input type="text" v-model="categorySearch" :placeholder="$t('category.searchPlaceholder') || 'Search'" class="filter-search-input" />
                </div>
                <div class="filter-widget__values">
                  <label
                    v-for="cat in filteredCategories"
                    :key="cat.id"
                    class="filter-label"
                    :class="{ 'filter-label--active': selectedCategory === cat.id }"
                  >
                    <input type="radio" name="category_id" :value="cat.id" v-model="selectedCategory" class="filter-radio" />
                    <span class="filter-option-name">{{ cat.name }}</span>
                  </label>
                </div>
              </template>
            </div>
          </div>

          <!-- Brands -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('brands')">
              <span>{{ $t('category.brands') || 'Brands' }}</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.brands }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.brands">
              <template v-if="isFiltersLoading">
                <div class="sidebar-shimmer-item" v-for="n in 4" :key="'brand-skel-'+n">
                  <div class="shimmer-box shimmer-radio"></div>
                  <div class="shimmer-box shimmer-text" :style="{ width: (40 + Math.random() * 50) + '%' }"></div>
                </div>
              </template>
              <template v-else>
                <div class="filter-widget__search">
                  <input type="text" v-model="brandSearch" :placeholder="$t('category.searchPlaceholder') || 'Search'" class="filter-search-input" />
                </div>
                <div class="filter-widget__values">
                  <label
                    v-for="brand in filteredBrands"
                    :key="brand.id"
                    class="filter-label"
                    :class="{ 'filter-label--active': selectedBrand === brand.id }"
                  >
                    <input type="radio" name="brand_id" :value="brand.id" v-model="selectedBrand" class="filter-radio" />
                    <span class="filter-option-name">{{ brand.name }}</span>
                  </label>
                </div>
              </template>
            </div>
          </div>

          <!-- Rating -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('rating')">
              <span>{{ $t('category.rating') || 'Rating' }}</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.rating }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.rating">
              <div class="filter-widget__values">
                <label
                  v-for="r in [5, 4, 3, 2, 1]"
                  :key="r"
                  class="filter-label filter-label--rating"
                  :class="{ 'filter-label--active': selectedRating === r }"
                >
                  <input type="radio" name="rating" :value="r" v-model="selectedRating" class="filter-radio" />
                  <span class="rating-stars">
                    <svg v-for="s in 5" :key="s" class="rating-star" :class="{ filled: s <= r }" width="14" height="14" viewBox="0 0 30 32">
                      <path d="M29.714 11.839c0 0.321-0.232 0.625-0.464 0.857l-6.482 6.321 1.536 8.929c0.018 0.125 0.018 0.232 0.018 0.357 0 0.464-0.214 0.893-0.732 0.893-0.25 0-0.5-0.089-0.714-0.214l-8.018-4.214-8.018 4.214c-0.232 0.125-0.464 0.214-0.714 0.214-0.518 0-0.75-0.429-0.75-0.893 0-0.125 0.018-0.232 0.036-0.357l1.536-8.929-6.5-6.321c-0.214-0.232-0.446-0.536-0.446-0.857 0-0.536 0.554-0.75 1-0.821l8.964-1.304 4.018-8.125c0.161-0.339 0.464-0.732 0.875-0.732s0.714 0.393 0.875 0.732l4.018 8.125 8.964 1.304c0.429 0.071 1 0.286 1 0.821z"/>
                    </svg>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <!-- Price -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('price')">
              <span>{{ $t('category.priceFilter') || 'Price' }}</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.price }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.price">
              <div class="filter-widget__values">
                <label
                  v-for="(range, idx) in priceRanges"
                  :key="idx"
                  class="filter-label"
                  :class="{ 'filter-label--active': selectedPrice === idx }"
                >
                  <input type="radio" name="price" :value="idx" v-model="selectedPrice" class="filter-radio" />
                  <span>{{ range.label }}</span>
                </label>
                <!-- Custom price range -->
                <div class="price-range-custom">
                  <div class="price-range-inputs">
                    <div class="price-range-field">
                      <span class="price-range-currency">SAR</span>
                      <input type="number" v-model.number="priceFrom" placeholder="from" class="price-range-input" @keyup.enter="applyCustomPrice" />
                    </div>
                    <span class="price-range-sep">-</span>
                    <div class="price-range-field">
                      <span class="price-range-currency">SAR</span>
                      <input type="number" v-model.number="priceTo" placeholder="to" class="price-range-input" @keyup.enter="applyCustomPrice" />
                    </div>
                    <button class="price-range-go" aria-label="Apply price" @click="applyCustomPrice">
                      <svg width="18" height="18" viewBox="0 0 32 32"><path d="M29.217 15.465c-0.019-0.044-0.056-0.077-0.080-0.119-0.067-0.116-0.139-0.227-0.236-0.317l-10.667-9.333c-0.553-0.484-1.396-0.429-1.881 0.125-0.484 0.555-0.428 1.396 0.127 1.881l7.996 6.997h-20.452c-0.737 0-1.333 0.597-1.333 1.333s0.596 1.333 1.333 1.333h20.452l-7.996 6.997c-0.555 0.485-0.611 1.327-0.127 1.881 0.264 0.3 0.633 0.455 1.004 0.455 0.312 0 0.625-0.109 0.877-0.331l10.667-9.333c0.097-0.091 0.169-0.201 0.236-0.317 0.024-0.041 0.060-0.075 0.080-0.119 0.073-0.163 0.116-0.343 0.116-0.533s-0.043-0.371-0.116-0.535z" fill="currentColor"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reset -->
          <div class="filters-footer">
            <button class="filters-reset-btn" @click="resetFilters">{{ $t('category.reset') || 'Reset Filters' }}</button>
          </div>
        </aside>

        <!-- Mobile overlay -->
        <div v-if="showMobileFilters" class="filters-overlay" @click="showMobileFilters = false"></div>

        <!-- ============ MAIN CONTENT ============ -->
        <div class="main-content">
          <!-- Header Row -->
          <div class="category-header">
            <h1 class="category-title">{{ categoryTitle }}</h1>
            <div class="category-controls">
              <button class="filter-trigger-btn" @click="showMobileFilters = true" aria-label="Filters">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
              </button>
              <div class="sort-wrapper">
                <label class="sort-label" for="product-filter">{{ $t('category.sortBy') || 'Sort By' }}</label>
                <select id="product-filter" v-model="sortBy" class="sort-select">
                  <option value="ourSuggest">{{ $t('category.ourSuggestions') || 'Our Suggestions' }}</option>
                  <option value="bestSell">{{ $t('category.bestSeller') || 'Best Seller' }}</option>
                  <option value="topRated">{{ $t('category.topRated') || 'Top Rated' }}</option>
                  <option value="priceFromTopToLow">{{ $t('category.priceHighToLow') || 'Price: High to Low' }}</option>
                  <option value="priceFromLowToTop">{{ $t('category.priceLowToHigh') || 'Price: Low to High' }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Active Filter Chips -->
          <div class="active-filters" v-if="activeFilters.length > 0">
            <span v-for="chip in activeFilters" :key="chip.key" class="filter-chip">
              <span class="filter-chip__label">{{ chip.label }}</span>
              <button class="filter-chip__remove" @click="chip.remove()" aria-label="Remove filter">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </span>
            <button class="active-filters__clear" @click="resetFilters">
              {{ $t('category.clearAll') || 'Clear All' }}
            </button>
          </div>

          <!-- ───────── PRODUCT AREA ───────── -->

          <!-- STATE 1: Skeleton shimmer (initial page load) -->
          <div v-if="initialLoading" class="products-grid">
            <div v-for="n in 12" :key="'prod-skel-'+n" class="product-skeleton-card">
              <div class="shimmer-box ps-img-box"></div>
              <div class="ps-circles-row">
                <div class="shimmer-box ps-circle"></div>
                <div class="shimmer-box ps-circle"></div>
              </div>
              <div class="ps-lines-col">
                <div class="shimmer-box ps-line ps-line-t1"></div>
                <div class="shimmer-box ps-line ps-line-t2"></div>
                <div class="shimmer-box ps-line ps-line-t3"></div>
                <div class="shimmer-box ps-btn"></div>
              </div>
            </div>
          </div>

          <!-- STATE 2: Products loaded (with optional filter-change overlay) -->
          <div v-else-if="allProducts.length > 0" class="products-area">
            <!-- Filter-change overlay spinner -->
            <div v-if="isFilterLoading" class="filter-overlay-mask">
              <div class="filter-overlay-spinner">
                <div class="spinner-ring"></div>
                <span class="spinner-text">{{ $t('common.loading') || 'Loading...' }}</span>
              </div>
            </div>

            <div class="products-grid" :class="{ 'products-grid--dimmed': isFilterLoading }">
              <ProductCard
                v-for="product in allProducts"
                :key="product.id"
                :product="product"
              />
            </div>

            <!-- Load More -->
            <div v-if="hasMore" class="load-more-wrapper">
              <div ref="loadMoreSentinel" class="load-more-sentinel"></div>
              <button class="load-more-btn" :disabled="isLoadingMore" @click="loadMore">
                <span v-if="isLoadingMore" class="load-more-spinner"></span>
                <span v-else>{{ $t('category.loadMore') || 'Load More' }}</span>
              </button>
            </div>
          </div>

          <!-- STATE 3: Empty (no products, not loading) -->
          <div v-else class="empty-state">
            <svg class="empty-state__icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p class="empty-state__title">{{ $t('category.noProducts') || 'No products found' }}</p>
            <p class="empty-state__desc">{{ $t('category.tryDifferentFilters') || 'Try adjusting your filters.' }}</p>
            <button class="empty-state__btn" @click="resetFilters">{{ $t('category.reset') || 'Reset Filters' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Testimonials -->
    <TestimonialsSlider :title="$t('common.customersReviews')" :reviews="customerReviews" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import ProductCard from '@/components/home/ProductCard.vue'
import TestimonialsSlider from '@/components/home/TestimonialsSlider.vue'
import { fetchProducts, fetchCategories, fetchBrands, fetchCategoryBySlug } from '@/api/services'
import type { Product, Category } from '@/types'

const props = defineProps<{ isShop?: boolean }>()
const route = useRoute()
const { t } = useI18n()

// ═══════════════════════════════════════════════
// ROUTE DETECTION
// ═══════════════════════════════════════════════
const isBrandRoute = computed(() => route.name === 'brand')
const isCategoryRoute = computed(() => route.name === 'category')

// ═══════════════════════════════════════════════
// PAGE STATE MACHINE
// ═══════════════════════════════════════════════
const initialLoading = ref(true)      // true until first fetch completes
const isFilterLoading = ref(false)    // true during filter-change re-fetch
const isLoadingMore = ref(false)      // true during pagination append
const isFiltersLoading = ref(true)    // sidebar filter data loading

const allProducts = ref<any[]>([])
const currentPage = ref(1)
const hasMore = ref(false)

// Suppress watcher-triggered fetches during initialization
const suppressWatcher = ref(true)

// ═══════════════════════════════════════════════
// OFFERS MODE
// ═══════════════════════════════════════════════
const isOffersMode = computed(() => route.query.offers === 'true')

// ═══════════════════════════════════════════════
// CATEGORY TITLE
// ═══════════════════════════════════════════════
const categoryData = ref<Category | null>(null)
const categoryTitle = computed(() => {
  if (isOffersMode.value) return t('nav.offers') || 'Offers'
  // FIX: header.allProducts key was invalid, using category.categories or Shop as fallback
  if (props.isShop) return t('category.categories') || 'Shop'
  if (isBrandRoute.value) {
    const brand = brands.value.find(b => b.id === selectedBrand.value)
    if (brand) return brand.name
  }
  if (categoryData.value) return categoryData.value.name
  const slug = (route.params.slug as string) || 'Category'
  return slug.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})

// ═══════════════════════════════════════════════
// FILTER WIDGETS
// ═══════════════════════════════════════════════
const widgetOpen = ref({ categories: true, brands: true, rating: true, price: true })
function toggleWidget(key: keyof typeof widgetOpen.value) {
  widgetOpen.value[key] = !widgetOpen.value[key]
}

const showMobileFilters = ref(false)

// Category filter
const categorySearch = ref('')
const selectedCategory = ref<number | null>(null)
const categories = ref<{ id: number; name: string; slug: string }[]>([])
const filteredCategories = computed(() =>
  categories.value.filter(c => c.name.toLowerCase().includes(categorySearch.value.toLowerCase()))
)

// Brand filter
const brandSearch = ref('')
const selectedBrand = ref<number | null>(null)
const brands = ref<{ id: number; name: string; slug: string }[]>([])
const filteredBrands = computed(() =>
  brands.value.filter(b => b.name.toLowerCase().includes(brandSearch.value.toLowerCase()))
)

// Rating filter
const selectedRating = ref<number | null>(null)

// Price filter
const selectedPrice = ref<number | null>(null)
const priceFrom = ref<number | undefined>(undefined)
const priceTo = ref<number | undefined>(undefined)
const priceRanges = [
  { label: '200 SAR – 700 SAR', min: 200, max: 700 },
  { label: '700 SAR – 1200 SAR', min: 700, max: 1200 },
  { label: '1200 SAR – 1700 SAR', min: 1200, max: 1700 },
  { label: '1700 SAR – 2800 SAR', min: 1700, max: 2800 },
  { label: 'more than 2800 SAR', min: 2800, max: Infinity },
]

function applyCustomPrice() {
  if (!priceFrom.value && !priceTo.value) return
  selectedPrice.value = null
  currentPage.value = 1
  fetchAndSet()
}

// Sort
const sortBy = ref('ourSuggest')
const sortMap: Record<string, string> = {
  ourSuggest: 'newest',
  bestSell: 'best_seller',
  topRated: 'name_asc',
  priceFromTopToLow: 'price_desc',
  priceFromLowToTop: 'price_asc',
}

// ═══════════════════════════════════════════════
// ACTIVE FILTER CHIPS
// ═══════════════════════════════════════════════
const activeFilters = computed(() => {
  const chips: { key: string; label: string; remove: () => void }[] = []

  if (selectedCategory.value) {
    const cat = categories.value.find(c => c.id === selectedCategory.value)
    if (cat) chips.push({ key: 'cat', label: cat.name, remove: () => { selectedCategory.value = null } })
  }
  if (selectedBrand.value) {
    const b = brands.value.find(b => b.id === selectedBrand.value)
    if (b) chips.push({ key: 'brand', label: b.name, remove: () => { selectedBrand.value = null } })
  }
  if (selectedRating.value) {
    chips.push({ key: 'rating', label: `${selectedRating.value}+ ★`, remove: () => { selectedRating.value = null } })
  }
  if (selectedPrice.value !== null) {
    const r = priceRanges[selectedPrice.value]
    if (r) chips.push({ key: 'price', label: r.label, remove: () => { selectedPrice.value = null } })
  } else if (priceFrom.value || priceTo.value) {
    chips.push({
      key: 'cprice',
      label: `${priceFrom.value || 0} – ${priceTo.value || '∞'} SAR`,
      remove: () => { priceFrom.value = undefined; priceTo.value = undefined; currentPage.value = 1; fetchAndSet() },
    })
  }
  if (isOffersMode.value) {
    chips.push({ key: 'offers', label: t('nav.offers') || 'Offers', remove: () => {} })
  }

  return chips
})

// ═══════════════════════════════════════════════
// RESET
// ═══════════════════════════════════════════════
function resetFilters() {
  suppressWatcher.value = true
  selectedCategory.value = null
  selectedBrand.value = null
  selectedRating.value = null
  selectedPrice.value = null
  priceFrom.value = undefined
  priceTo.value = undefined
  categorySearch.value = ''
  brandSearch.value = ''
  currentPage.value = 1
  suppressWatcher.value = false
  fetchAndSet()
}

// ═══════════════════════════════════════════════
// DATA FETCHING
// ═══════════════════════════════════════════════
function mapProduct(p: Product) {
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    subtitle: p.category?.name || undefined,
    image: p.primaryImage || '',
    primaryImage: p.primaryImage || '',
    price: p.flashSalePrice?.raw ?? p.price?.raw ?? 0,
    oldPrice: p.comparePrice?.raw || undefined,
    discount: p.discountPercent || undefined,
    currency: p.currency || 'SAR',
  }
}

function buildParams() {
  // Determine category slug for the API
  let categorySlug: string | undefined
  if (props.isShop) {
    categorySlug = selectedCategory.value
      ? categories.value.find(c => c.id === selectedCategory.value)?.slug
      : undefined
  } else if (isCategoryRoute.value) {
    categorySlug = route.params.slug as string
  } else {
    categorySlug = selectedCategory.value
      ? categories.value.find(c => c.id === selectedCategory.value)?.slug
      : undefined
  }

  // Determine brand slug for the API
  let brandSlug: string | undefined
  if (isBrandRoute.value && !selectedBrand.value) {
    brandSlug = route.params.slug as string
  } else if (selectedBrand.value) {
    brandSlug = brands.value.find(b => b.id === selectedBrand.value)?.slug
  }

  let minPrice: number | undefined
  let maxPrice: number | undefined
  if (selectedPrice.value !== null) {
    const range = priceRanges[selectedPrice.value]
    if (range) { minPrice = range.min; maxPrice = range.max === Infinity ? undefined : range.max }
  } else if (priceFrom.value || priceTo.value) {
    minPrice = priceFrom.value; maxPrice = priceTo.value
  }

  return {
    category: categorySlug,
    brand: brandSlug,
    minPrice,
    maxPrice,
    rating: selectedRating.value || undefined,
    offers: isOffersMode.value || undefined,
    sortBy: sortMap[sortBy.value] || 'newest',
    page: currentPage.value,
    perPage: 12,
  }
}

// Core fetch: for filter changes (not append)
async function fetchAndSet() {
  if (!initialLoading.value) {
    isFilterLoading.value = true
  }

  try {
    const result = await fetchProducts(buildParams())
    allProducts.value = result.data.map(mapProduct)
    hasMore.value = result.meta ? currentPage.value < result.meta.lastPage : false
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    // Explicit 400ms delay to ensure shimmer is seen gracefully.
    setTimeout(() => {
      initialLoading.value = false
      isFilterLoading.value = false
      nextTick(() => observeSentinel())
    }, 400)
  }
}

// Load more (append)
async function loadMore() {
  if (!hasMore.value || isLoadingMore.value) return
  currentPage.value++
  isLoadingMore.value = true
  try {
    const result = await fetchProducts(buildParams())
    const mapped = result.data.map(mapProduct)
    allProducts.value = [...allProducts.value, ...mapped]
    hasMore.value = result.meta ? currentPage.value < result.meta.lastPage : false
  } catch (error) {
    console.error('Failed to load more:', error)
    currentPage.value-- // rollback
  } finally {
    isLoadingMore.value = false
    await nextTick()
    observeSentinel()
  }
}

// ═══════════════════════════════════════════════
// INFINITE SCROLL
// ═══════════════════════════════════════════════
const loadMoreSentinel = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

function observeSentinel() {
  if (observer) observer.disconnect()
  if (!loadMoreSentinel.value || !hasMore.value) return
  observer = new IntersectionObserver((entries) => {
    if (entries[0]?.isIntersecting && hasMore.value && !isLoadingMore.value && !isFilterLoading.value) {
      loadMore()
    }
  }, { rootMargin: '200px' })
  observer.observe(loadMoreSentinel.value)
}

// ═══════════════════════════════════════════════
// WATCHERS
// ═══════════════════════════════════════════════
watch([selectedCategory, selectedBrand, selectedRating, selectedPrice, sortBy], () => {
  if (suppressWatcher.value) return
  currentPage.value = 1
  fetchAndSet()
})

watch(() => route.params.slug, (newSlug, oldSlug) => {
  if (newSlug !== oldSlug) {
    currentPage.value = 1
    loadPageData()
  }
})

watch(() => route.query.offers, () => {
  currentPage.value = 1
  fetchAndSet()
})

// ═══════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════
async function loadPageData() {
  initialLoading.value = true
  isFiltersLoading.value = true
  suppressWatcher.value = true

  // Reset filters
  selectedCategory.value = null
  selectedBrand.value = null
  selectedRating.value = null
  selectedPrice.value = null
  priceFrom.value = undefined
  priceTo.value = undefined

  // Fetch category data for title
  if (isCategoryRoute.value) {
    const slug = route.params.slug as string
    if (slug) {
      try { categoryData.value = await fetchCategoryBySlug(slug) } catch { categoryData.value = null }
    }
  }

  // Load filter data first, then auto-select
  await loadFilterData()
  autoSelectFromUrl()

  suppressWatcher.value = false
  await fetchAndSet()
}

function autoSelectFromUrl() {
  const slug = route.params.slug as string
  if (!slug) return

  if (isCategoryRoute.value) {
    const match = categories.value.find(c => c.slug === slug)
    if (match) selectedCategory.value = match.id
  }

  if (isBrandRoute.value) {
    const match = brands.value.find(b => b.slug === slug)
    if (match) selectedBrand.value = match.id
  }
}

async function loadFilterData() {
  try {
    const [catsData, brandsData] = await Promise.allSettled([fetchCategories(), fetchBrands()])
    if (catsData.status === 'fulfilled') {
      const flat: { id: number; name: string; slug: string }[] = []
      function flatten(cats: Category[]) {
        cats.forEach(c => { flat.push({ id: c.id, name: c.name, slug: c.slug }); if (c.children) flatten(c.children) })
      }
      flatten(catsData.value)
      categories.value = flat
    }
    if (brandsData.status === 'fulfilled') {
      brands.value = brandsData.value.map(b => ({ id: b.id, name: b.name, slug: b.slug }))
    }
  } catch {} finally {
    isFiltersLoading.value = false
  }
}

onMounted(loadPageData)
onBeforeUnmount(() => { if (observer) observer.disconnect() })

// ═══════════════════════════════════════════════
// TESTIMONIALS
// ═══════════════════════════════════════════════
const customerReviews = [
  { name: 'ناصر السهلي', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', rating: 5, text: 'رائع رائع الشوز اكرمكم الله فوق الخياااال والكواليتي وكل شيء رهيببب يعطيكم العافيه والله يرزقكم من واسع فضله🤍' },
  { name: 'سمية باحاذق', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'حلوة ❤️' },
  { name: 'ماجد سلطان', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', rating: 5, text: 'ممتاز' },
  { name: 'عمر بيما', avatar: 'https://cdn.salla.sa/customer_profiles/EIUcfpOl29SJD1iFG0EbnAnLjfEXD6ScrvZp7UIN.jpg', rating: 5, text: 'متجر فنان وجودة الشوزات بطلة' },
  { name: 'نوره العمري', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'شكراً' },
  { name: 'غدي محمد', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'رائع جداً المتجر وتعاملهم راقي' },
  { name: 'Layla Fahad', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', rating: 5, text: 'Your journey to comfort is HERE' },
  { name: 'أحمد', avatar: 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', rating: 5, text: 'ممتاز جداً وسريع التوصيل' },
]
</script>

<style scoped>
/* ═══════════════════════════════════════════════
   PAGE LAYOUT
   ═══════════════════════════════════════════════ */
.category-page { background: var(--bg-primary, #fff); margin-bottom: 2.5rem; }
.container { max-width: 1280px; margin: 0 auto; padding: 0 0.625rem; }
@media (min-width: 480px) { .container { padding: 0 1.25rem; } }

/* Breadcrumbs */
.breadcrumbs { padding: 1.25rem 0; }
.breadcrumb-list { list-style: none; margin: 0; padding: 0; display: flex; align-items: center; gap: 0.25rem; font-size: 0.875rem; color: #6b7280; }
.breadcrumb-item a { color: #6b7280; text-decoration: none; transition: color 0.2s; }
.breadcrumb-item a:hover { color: var(--color-primary, #858585); }
.breadcrumb-current { color: var(--store-text-primary, #111827); font-weight: 500; }
.breadcrumb-arrow { display: flex; align-items: center; color: #9ca3af; }

/* Layout */
.category-layout { display: flex; align-items: flex-start; flex-direction: column; gap: 0; }
@media (min-width: 768px) { .category-layout { flex-direction: row; } }

/* ═══════════════════════════════════════════════
   SIDEBAR
   ═══════════════════════════════════════════════ */
.filters-sidebar { width: 100%; flex-shrink: 0; display: none; }
@media (min-width: 768px) { .filters-sidebar { display: block; width: 18rem; position: sticky; top: 5rem; } }
.filters-sidebar--open { display: block; position: fixed; top: 0; left: 0; bottom: 0; width: 85%; max-width: 320px; z-index: 100; background: var(--bg-primary, #fff); overflow-y: auto; box-shadow: 4px 0 20px rgba(0,0,0,0.15); padding: 1rem; animation: slideIn 0.25s ease; }
@keyframes slideIn { from { transform: translateX(-100%); } to { transform: translateX(0); } }
.filters-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 99; }
.filters-close-btn { display: none; position: absolute; top: 0.75rem; right: 0.75rem; background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280; z-index: 5; }
.filters-sidebar--open .filters-close-btn { display: block; }

/* Filter Widget */
.filter-widget { border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; margin-bottom: 0.75rem; }
.filter-widget__title { display: flex; align-items: center; justify-content: space-between; font-size: 0.9375rem; font-weight: 700; color: var(--store-text-primary, #111827); cursor: pointer; padding: 0.5rem 0; margin: 0; user-select: none; }
.filter-widget__toggle { width: 12px; height: 12px; position: relative; }
.filter-widget__toggle::before, .filter-widget__toggle::after { content: ''; position: absolute; background: #6b7280; transition: transform 0.2s; }
.filter-widget__toggle::before { width: 12px; height: 2px; top: 5px; left: 0; }
.filter-widget__toggle::after { width: 2px; height: 12px; top: 0; left: 5px; }
.filter-widget__toggle.active::after { transform: rotate(90deg); opacity: 0; }
.filter-widget__content { padding-top: 0.25rem; }
.filter-widget__search { padding: 0.25rem 0; margin-bottom: 0.5rem; }
.filter-search-input { width: 100%; padding: 0.4rem 0.625rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8125rem; color: var(--store-text-primary, #111827); background: var(--bg-primary, #fff); outline: none; transition: border-color 0.2s; box-sizing: border-box; }
.filter-search-input:focus { border-color: var(--color-primary, #858585); }
.filter-widget__values { max-height: 350px; overflow-y: auto; }

/* Filter labels */
.filter-label { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.25rem; cursor: pointer; font-size: 0.8125rem; color: var(--store-text-primary, #111827); transition: all 0.15s; border-radius: 4px; }
.filter-label:hover { color: var(--color-primary, #858585); background: rgba(133,133,133,0.04); }
.filter-label--active { color: var(--color-primary, #858585); font-weight: 600; background: rgba(133,133,133,0.08); }
.filter-radio { accent-color: var(--color-primary, #858585); width: 15px; height: 15px; flex-shrink: 0; cursor: pointer; display: flex; }
.filter-option-name { line-height: 1.4; }
.filter-label--rating { align-items: center; }
.rating-stars { display: flex; gap: 1px; }
.rating-star { fill: #d1d5db; transition: fill 0.15s; }
.rating-star.filled { fill: #f59e0b; }

/* ═══════════════════════════════════════════════
   SHIMMER ANIMATION FOR BOTH SIDEBAR AND GRID
   Using pure radial/linear gradient background animation.
   Compatible perfectly with scoped styling!
   ═══════════════════════════════════════════════ */
@keyframes sweep {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.shimmer-box {
  background: #f6f7f8;
  background: linear-gradient(to right, #eeeeee 8%, #dddddd 18%, #eeeeee 33%);
  background-size: 200% 100%;
  animation: sweep 1.5s infinite linear;
}

/* Sidebar Specific */
.sidebar-shimmer-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.25rem; }
.shimmer-radio { width: 15px; height: 15px; border-radius: 50%; flex-shrink: 0; }
.shimmer-text { height: 12px; border-radius: 4px; }

/* Grid specific */
.product-skeleton-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.ps-img-box {
  width: 100%;
  aspect-ratio: 1 / 1;
}
.ps-circles-row {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.75rem 0 0.25rem;
}
.ps-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
}
.ps-lines-col {
  padding: 0 1rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}
.ps-line {
  height: 12px;
  border-radius: 4px;
}
.ps-line-t1 { width: 70%; height: 14px; }
.ps-line-t2 { width: 50%; }
.ps-line-t3 { width: 35%; height: 16px; }
.ps-btn {
  width: 100%;
  height: 36px;
  border-radius: 6px;
  margin-top: 0.5rem;
}

/* Price range */
.price-range-custom { margin-top: 0.75rem; }
.price-range-inputs { display: flex; align-items: center; gap: 0.375rem; }
.price-range-field { position: relative; flex: 1; }
.price-range-currency { position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); font-size: 0.6875rem; color: #9ca3af; pointer-events: none; }
.price-range-input { width: 100%; padding: 0.4rem 0.5rem 0.4rem 2.25rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8125rem; outline: none; appearance: textfield; -moz-appearance: textfield; background: var(--bg-primary, #fff); color: var(--store-text-primary, #111827); box-sizing: border-box; }
.price-range-input::-webkit-inner-spin-button, .price-range-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.price-range-sep { color: #9ca3af; font-size: 0.875rem; }
.price-range-go { width: 34px; height: 34px; border-radius: 6px; border: 1px solid #e5e7eb; background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6b7280; flex-shrink: 0; transition: all 0.2s; }
.price-range-go:hover { border-color: var(--color-primary, #858585); color: var(--color-primary, #858585); }

/* Filters footer */
.filters-footer { padding: 0.75rem 0; }
.filters-reset-btn { width: 100%; padding: 0.5rem 1rem; border: 1px solid #e5e7eb; border-radius: 6px; background: transparent; color: var(--store-text-primary, #111827); font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
.filters-reset-btn:hover { border-color: var(--color-primary, #858585); color: var(--color-primary, #858585); }

/* ═══════════════════════════════════════════════
   MAIN CONTENT
   ═══════════════════════════════════════════════ */
.main-content { flex: 1; width: 100%; min-width: 0; }
@media (min-width: 768px) { .main-content { margin-left: 2rem; } }

/* Header */
.category-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 0.75rem; flex-wrap: wrap; }
@media (min-width: 640px) { .category-header { margin-bottom: 1.5rem; } }
.category-title { font-size: 1.25rem; font-weight: 700; color: var(--store-text-primary, #111827); margin: 0; line-height: 1.3; }
.category-controls { display: flex; align-items: center; gap: 0.5rem; }
.filter-trigger-btn { width: 40px; height: 40px; border-radius: 8px; background: #f3f4f6; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6b7280; transition: all 0.2s; }
.filter-trigger-btn:hover { background: #e5e7eb; }
@media (min-width: 768px) { .filter-trigger-btn { display: none; } }
.sort-wrapper { display: flex; align-items: center; gap: 0.5rem; }
.sort-label { display: none; white-space: nowrap; font-size: 0.875rem; color: var(--store-text-primary, #111827); }
@media (min-width: 640px) { .sort-label { display: block; } }
.sort-select { padding: 0.375rem 2.25rem 0.375rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8125rem; color: var(--store-text-primary, #111827); background: var(--bg-secondary, #f9fafb); outline: none; cursor: pointer; appearance: auto; min-width: 140px; }

/* ═══════════════════════════════════════════════
   ACTIVE FILTER CHIPS
   ═══════════════════════════════════════════════ */
.active-filters { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-chip { display: inline-flex; align-items: center; gap: 0.375rem; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px; padding: 0.25rem 0.5rem 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 500; color: var(--store-text-primary, #111827); animation: chipIn 0.25s ease; }
.filter-chip:hover { border-color: var(--color-primary, #858585); }
.filter-chip__remove { display: flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; border: none; background: #e5e7eb; cursor: pointer; color: #6b7280; padding: 0; transition: all 0.15s; }
.filter-chip__remove:hover { background: #ef4444; color: #fff; }
.active-filters__clear { font-size: 0.75rem; color: #ef4444; background: none; border: none; cursor: pointer; font-weight: 500; padding: 0.25rem 0.5rem; border-radius: 4px; transition: all 0.15s; white-space: nowrap; }
.active-filters__clear:hover { background: rgba(239,68,68,0.08); }
@keyframes chipIn { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }

/* ═══════════════════════════════════════════════
   PRODUCTS GRID
   ═══════════════════════════════════════════════ */
.products-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 480px) { .products-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 900px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
.products-grid--dimmed { opacity: 0.3; pointer-events: none; transition: opacity 0.25s ease; }
.products-area { position: relative; min-height: 200px; }

/* ═══════════════════════════════════════════════
   FILTER-CHANGE OVERLAY (spinner on top of dimmed grid)
   ═══════════════════════════════════════════════ */
.filter-overlay-mask {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 5rem;
  animation: fadeIn 0.2s ease;
}
.filter-overlay-spinner {
  background: rgba(255,255,255,0.95);
  border-radius: 14px;
  padding: 1.25rem 2rem;
  box-shadow: 0 8px 30px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}
.spinner-ring {
  width: 36px;
  height: 36px;
  border: 3.5px solid #e5e7eb;
  border-top-color: var(--color-primary, #858585);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.spinner-text {
  font-size: 0.8125rem;
  color: #6b7280;
  font-weight: 500;
}
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* ═══════════════════════════════════════════════
   EMPTY STATE
   ═══════════════════════════════════════════════ */
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1rem; text-align: center; }
.empty-state__icon { margin-bottom: 1.25rem; opacity: 0.6; }
.empty-state__title { font-size: 1.125rem; font-weight: 600; color: var(--store-text-primary, #111827); margin: 0 0 0.5rem; }
.empty-state__desc { font-size: 0.875rem; color: #9ca3af; margin: 0 0 1.5rem; max-width: 28rem; line-height: 1.5; }
.empty-state__btn { padding: 0.5rem 1.5rem; border: 1px solid #e5e7eb; border-radius: 8px; background: transparent; color: var(--store-text-primary, #111827); font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
.empty-state__btn:hover { border-color: var(--color-primary, #858585); color: var(--color-primary, #858585); }

/* ═══════════════════════════════════════════════
   LOAD MORE
   ═══════════════════════════════════════════════ */
.load-more-wrapper { display: flex; flex-direction: column; align-items: center; padding: 2rem 0; }
.load-more-sentinel { height: 1px; width: 100%; }
.load-more-btn { padding: 0.625rem 2rem; background: var(--color-primary, #858585); color: #fff; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; min-width: 160px; min-height: 44px; }
.load-more-btn:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.load-more-btn:disabled { cursor: wait; opacity: 0.8; transform: none; }
.load-more-spinner { width: 18px; height: 18px; border: 2.5px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
</style>
