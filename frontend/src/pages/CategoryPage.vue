<template>
  <div class="category-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumbs container">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
          <router-link to="/">Home</router-link>
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
          <button
            class="filters-close-btn"
            @click="showMobileFilters = false"
            aria-label="Close"
          >✕</button>

          <!-- Categories -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('categories')">
              <span>Categories</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.categories }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.categories">
              <div class="filter-widget__search">
                <input type="text" v-model="categorySearch" placeholder="Search" class="filter-search-input" />
              </div>
              <div class="filter-widget__values">
                <label
                  v-for="cat in filteredCategories"
                  :key="cat.id"
                  class="filter-label"
                >
                  <input
                    type="radio"
                    name="category_id"
                    :value="cat.id"
                    v-model="selectedCategory"
                    class="filter-radio"
                  />
                  <span class="filter-option-name">{{ cat.name }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Brands -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('brands')">
              <span>Brands</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.brands }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.brands">
              <div class="filter-widget__search">
                <input type="text" v-model="brandSearch" placeholder="Search" class="filter-search-input" />
              </div>
              <div class="filter-widget__values">
                <label
                  v-for="brand in filteredBrands"
                  :key="brand.id"
                  class="filter-label"
                >
                  <input
                    type="radio"
                    name="brand_id"
                    :value="brand.id"
                    v-model="selectedBrand"
                    class="filter-radio"
                  />
                  <span class="filter-option-name">{{ brand.name }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Rating -->
          <div class="filter-widget">
            <h3 class="filter-widget__title" @click="toggleWidget('rating')">
              <span>Rating</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.rating }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.rating">
              <div class="filter-widget__values">
                <label
                  v-for="r in [5, 4, 3, 2, 1]"
                  :key="r"
                  class="filter-label filter-label--rating"
                >
                  <input
                    type="radio"
                    name="rating"
                    :value="r"
                    v-model="selectedRating"
                    class="filter-radio"
                  />
                  <span class="rating-stars">
                    <svg
                      v-for="s in 5"
                      :key="s"
                      class="rating-star"
                      :class="{ filled: s <= r }"
                      width="14" height="14" viewBox="0 0 30 32"
                    >
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
              <span>Price</span>
              <span class="filter-widget__toggle" :class="{ active: widgetOpen.price }"></span>
            </h3>
            <div class="filter-widget__content" v-show="widgetOpen.price">
              <div class="filter-widget__values">
                <label
                  v-for="(range, idx) in priceRanges"
                  :key="idx"
                  class="filter-label"
                >
                  <input
                    type="radio"
                    name="price"
                    :value="idx"
                    v-model="selectedPrice"
                    class="filter-radio"
                  />
                  <span>{{ range.label }}</span>
                </label>

                <!-- Custom price range -->
                <div class="price-range-custom">
                  <div class="price-range-inputs">
                    <div class="price-range-field">
                      <span class="price-range-currency">SAR</span>
                      <input type="number" v-model.number="priceFrom" placeholder="from" class="price-range-input" />
                    </div>
                    <span class="price-range-sep">-</span>
                    <div class="price-range-field">
                      <span class="price-range-currency">SAR</span>
                      <input type="number" v-model.number="priceTo" placeholder="to" class="price-range-input" />
                    </div>
                    <button class="price-range-go" aria-label="Apply price">
                      <svg width="18" height="18" viewBox="0 0 32 32"><path d="M29.217 15.465c-0.019-0.044-0.056-0.077-0.080-0.119-0.067-0.116-0.139-0.227-0.236-0.317l-10.667-9.333c-0.553-0.484-1.396-0.429-1.881 0.125-0.484 0.555-0.428 1.396 0.127 1.881l7.996 6.997h-20.452c-0.737 0-1.333 0.597-1.333 1.333s0.596 1.333 1.333 1.333h20.452l-7.996 6.997c-0.555 0.485-0.611 1.327-0.127 1.881 0.264 0.3 0.633 0.455 1.004 0.455 0.312 0 0.625-0.109 0.877-0.331l10.667-9.333c0.097-0.091 0.169-0.201 0.236-0.317 0.024-0.041 0.060-0.075 0.080-0.119 0.073-0.163 0.116-0.343 0.116-0.533s-0.043-0.371-0.116-0.535z" fill="currentColor"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reset -->
          <div class="filters-footer">
            <button class="filters-reset-btn" @click="resetFilters">Reset</button>
          </div>
        </aside>

        <!-- Mobile overlay -->
        <div
          v-if="showMobileFilters"
          class="filters-overlay"
          @click="showMobileFilters = false"
        ></div>

        <!-- ============ MAIN CONTENT ============ -->
        <div class="main-content">
          <!-- Header Row -->
          <div class="category-header">
            <h1 class="category-title">{{ categoryTitle }} | Trend Shoes</h1>
            <div class="category-controls">
              <button
                class="filter-trigger-btn"
                @click="showMobileFilters = true"
                aria-label="Filters"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
              </button>
              <div class="sort-wrapper">
                <label class="sort-label" for="product-filter">Sort By</label>
                <select id="product-filter" v-model="sortBy" class="sort-select">
                  <option value="ourSuggest">Our Suggestions</option>
                  <option value="bestSell">Best seller</option>
                  <option value="topRated">Top rated</option>
                  <option value="priceFromTopToLow">Price high to low</option>
                  <option value="priceFromLowToTop">Price low to high</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Product Grid -->
          <div class="products-grid">
            <ProductCard
              v-for="product in visibleProducts"
              :key="product.id"
              :product="product"
            />
          </div>

          <!-- Load More -->
          <div class="load-more-wrapper" v-if="visibleCount < allProducts.length">
            <button class="load-more-btn" @click="loadMore">
              <span>Load more</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Testimonials Section -->
    <TestimonialsSlider title="Customers Reviews" :reviews="customerReviews" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import ProductCard from '@/components/home/ProductCard.vue'
import TestimonialsSlider from '@/components/home/TestimonialsSlider.vue'

const route = useRoute()

// ─── Category Title ───
const categoryTitle = computed(() => {
  const slug = (route.params.slug as string) || 'unisex-shoes'
  return slug.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})

// ─── Filter widget open/close state ───
const widgetOpen = ref({
  categories: true,
  brands: true,
  rating: true,
  price: true,
})
function toggleWidget(key: keyof typeof widgetOpen.value) {
  widgetOpen.value[key] = !widgetOpen.value[key]
}

// ─── Mobile filters ───
const showMobileFilters = ref(false)

// ─── Category filter ───
const categorySearch = ref('')
const selectedCategory = ref<number | null>(null)
const categories = [
  { id: 1, name: 'Unisex shoes' },
  { id: 2, name: 'Nike' },
  { id: 3, name: 'Asics shoes' },
  { id: 4, name: 'Adidas' },
  { id: 5, name: "Nike Men's" },
  { id: 6, name: 'Hermes' },
  { id: 7, name: 'New Balance' },
  { id: 8, name: 'On Running (Cloud)' },
  { id: 9, name: 'Asics' },
  { id: 10, name: 'Loro Piana' },
  { id: 11, name: 'kids shoes' },
  { id: 12, name: 'Air Force' },
  { id: 13, name: 'Louis Vuitton' },
  { id: 14, name: 'onitsuka tiger' },
]
const filteredCategories = computed(() =>
  categories.filter(c =>
    c.name.toLowerCase().includes(categorySearch.value.toLowerCase())
  )
)

// ─── Brand filter ───
const brandSearch = ref('')
const selectedBrand = ref<number | null>(null)
const brands = [
  { id: 1, name: 'NIKE' },
  { id: 2, name: 'adidas' },
  { id: 3, name: 'New balance' },
  { id: 4, name: 'Chanel' },
  { id: 5, name: 'LOUIS VUITION' },
  { id: 6, name: 'Dior' },
  { id: 7, name: 'GUCCI' },
  { id: 8, name: 'PRADA' },
  { id: 9, name: 'Valentino' },
  { id: 10, name: 'Saint Laurent' },
  { id: 11, name: 'FENDI' },
  { id: 12, name: 'Rene Caovilla' },
  { id: 13, name: 'Converse' },
]
const filteredBrands = computed(() =>
  brands.filter(b =>
    b.name.toLowerCase().includes(brandSearch.value.toLowerCase())
  )
)

// ─── Rating filter ───
const selectedRating = ref<number | null>(null)

// ─── Price filter ───
const selectedPrice = ref<number | null>(null)
const priceFrom = ref<number | undefined>(undefined)
const priceTo = ref<number | undefined>(undefined)
const priceRanges = [
  { label: '200 SAR to 700 SAR', min: 200, max: 700 },
  { label: '700 SAR to 1200 SAR', min: 700, max: 1200 },
  { label: '1200 SAR to 1700 SAR', min: 1200, max: 1700 },
  { label: '1700 SAR to 2800 SAR', min: 1700, max: 2800 },
  { label: 'more than 2800 SAR', min: 2800, max: Infinity },
]

// ─── Sort ───
const sortBy = ref('ourSuggest')

// ─── Reset ───
function resetFilters() {
  selectedCategory.value = null
  selectedBrand.value = null
  selectedRating.value = null
  selectedPrice.value = null
  priceFrom.value = undefined
  priceTo.value = undefined
  categorySearch.value = ''
  brandSearch.value = ''
}

// ─── Product Data ───
interface Product {
  id: number
  slug: string
  name: string
  subtitle?: string
  image: string
  price: number
  oldPrice?: number
  discount?: number
  currency?: string
}

const allProducts = ref<Product[]>([
  {
    id: 1,
    slug: 'nike-mind-001-slides',
    name: 'Nike Mind 001 slides in a sleek black and navy blue',
    subtitle: 'nike mind 001 ksa',
    image: 'https://cdn.salla.sa/RvPxw/66ca527f-6b33-4789-b61c-26656df04678-500x500-YSQQShayDkZ31YdxOeWr88KMEWLuNC2TSvIEKAss.png',
    price: 480,
    currency: 'SAR',
  },
  {
    id: 2,
    slug: 'nb-1906lae-olive',
    name: 'New Balance 1906LAE Mesh Slip-On Shoes Olive Green with Navy',
    subtitle: 'Unisex shoes',
    image: 'https://cdn.salla.sa/RvPxw/086fec69-45c4-4455-ba6e-2feb05b55bd0-500x500-ewOakK3feXpxYkdichrfR1Y7Rz7kFrbGBHZslSVu.png',
    price: 600,
    oldPrice: 690,
    currency: 'SAR',
  },
  {
    id: 3,
    slug: 'nb-1906lae-silver-pink',
    name: 'New Balance 1906LAE Mesh Slip-On Silver Pink',
    subtitle: "Women's shoes",
    image: 'https://cdn.salla.sa/RvPxw/51ab9cf7-127d-4f55-b4ea-df153473c194-500x500-N6pv0aEMA0fWqJGzSnzIAURAMZOZMrPqLBjcyQDZ.png',
    price: 600,
    currency: 'SAR',
  },
  {
    id: 4,
    slug: 'adidas-yeezy-slide-green',
    name: 'Adidas Yeezy Slide Flax Green Comfort',
    subtitle: 'Unisex shoes',
    image: 'https://cdn.salla.sa/RvPxw/086fec69-45c4-4455-ba6e-2feb05b55bd0-500x500-ewOakK3feXpxYkdichrfR1Y7Rz7kFrbGBHZslSVu.png',
    price: 350,
    oldPrice: 420,
    currency: 'SAR',
  },
  {
    id: 5,
    slug: 'asics-gel-kayano-14',
    name: 'ASICS Gel-Kayano 14 Silver Grey Running Shoes',
    subtitle: 'Unisex shoes',
    image: 'https://cdn.salla.sa/RvPxw/51ab9cf7-127d-4f55-b4ea-df153473c194-500x500-N6pv0aEMA0fWqJGzSnzIAURAMZOZMrPqLBjcyQDZ.png',
    price: 520,
    oldPrice: 650,
    currency: 'SAR',
  },
  {
    id: 6,
    slug: 'on-cloud-monster-black',
    name: 'On Running Cloudmonster Triple Black',
    subtitle: 'On Running (Cloud)',
    image: 'https://cdn.salla.sa/RvPxw/66ca527f-6b33-4789-b61c-26656df04678-500x500-YSQQShayDkZ31YdxOeWr88KMEWLuNC2TSvIEKAss.png',
    price: 780,
    currency: 'SAR',
  },
  {
    id: 7,
    slug: 'nike-vomero-5-white',
    name: 'Nike Zoom Vomero 5 Triple White Sneakers',
    subtitle: 'Nike',
    image: 'https://cdn.salla.sa/RvPxw/086fec69-45c4-4455-ba6e-2feb05b55bd0-500x500-ewOakK3feXpxYkdichrfR1Y7Rz7kFrbGBHZslSVu.png',
    price: 655,
    oldPrice: 799,
    currency: 'SAR',
  },
  {
    id: 8,
    slug: 'nb-530-white-silver',
    name: 'New Balance 530 White Silver Running',
    subtitle: 'New Balance',
    image: 'https://cdn.salla.sa/RvPxw/51ab9cf7-127d-4f55-b4ea-df153473c194-500x500-N6pv0aEMA0fWqJGzSnzIAURAMZOZMrPqLBjcyQDZ.png',
    price: 490,
    currency: 'SAR',
  },
  {
    id: 9,
    slug: 'adidas-samba-og-black',
    name: 'Adidas Samba OG Classic Black White Gum',
    subtitle: 'Adidas',
    image: 'https://cdn.salla.sa/RvPxw/66ca527f-6b33-4789-b61c-26656df04678-500x500-YSQQShayDkZ31YdxOeWr88KMEWLuNC2TSvIEKAss.png',
    price: 450,
    oldPrice: 550,
    currency: 'SAR',
  },
  {
    id: 10,
    slug: 'nike-air-max-dn-olive',
    name: 'Nike Air Max Dn Olive Green Lifestyle Shoes',
    subtitle: 'Nike',
    image: 'https://cdn.salla.sa/RvPxw/086fec69-45c4-4455-ba6e-2feb05b55bd0-500x500-ewOakK3feXpxYkdichrfR1Y7Rz7kFrbGBHZslSVu.png',
    price: 720,
    currency: 'SAR',
  },
  {
    id: 11,
    slug: 'adidas-ultraboost-black',
    name: 'Adidas Ultraboost Light Core Black',
    subtitle: 'Adidas',
    image: 'https://cdn.salla.sa/RvPxw/51ab9cf7-127d-4f55-b4ea-df153473c194-500x500-N6pv0aEMA0fWqJGzSnzIAURAMZOZMrPqLBjcyQDZ.png',
    price: 690,
    oldPrice: 850,
    currency: 'SAR',
  },
  {
    id: 12,
    slug: 'on-cloud-5-sand',
    name: 'On Cloud 5 Sand Rose Running Shoes',
    subtitle: 'On Running (Cloud)',
    image: 'https://cdn.salla.sa/RvPxw/66ca527f-6b33-4789-b61c-26656df04678-500x500-YSQQShayDkZ31YdxOeWr88KMEWLuNC2TSvIEKAss.png',
    price: 699,
    currency: 'SAR',
  },
  {
    id: 13,
    slug: 'nike-dunk-low-panda',
    name: 'Nike Dunk Low Retro Panda Black White',
    subtitle: 'Nike',
    image: 'https://cdn.salla.sa/RvPxw/086fec69-45c4-4455-ba6e-2feb05b55bd0-500x500-ewOakK3feXpxYkdichrfR1Y7Rz7kFrbGBHZslSVu.png',
    price: 549,
    oldPrice: 699,
    currency: 'SAR',
  },
  {
    id: 14,
    slug: 'asics-gel-1130-cream',
    name: 'ASICS Gel-1130 Cream Birch Retro',
    subtitle: 'Asics',
    image: 'https://cdn.salla.sa/RvPxw/51ab9cf7-127d-4f55-b4ea-df153473c194-500x500-N6pv0aEMA0fWqJGzSnzIAURAMZOZMrPqLBjcyQDZ.png',
    price: 580,
    currency: 'SAR',
  },
  {
    id: 15,
    slug: 'nb-2002r-protection-pack',
    name: 'New Balance 2002R Protection Pack Rain Cloud',
    subtitle: 'New Balance',
    image: 'https://cdn.salla.sa/RvPxw/66ca527f-6b33-4789-b61c-26656df04678-500x500-YSQQShayDkZ31YdxOeWr88KMEWLuNC2TSvIEKAss.png',
    price: 750,
    oldPrice: 899,
    currency: 'SAR',
  },
])

// ─── Pagination ───
const visibleCount = ref(9)
const visibleProducts = computed(() => allProducts.value.slice(0, visibleCount.value))
function loadMore() {
  visibleCount.value = Math.min(visibleCount.value + 6, allProducts.value.length)
}

// ─── Testimonials ───
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
/* ─── Page ─── */
.category-page {
  background: var(--bg-primary, #fff);
  margin-bottom: 2.5rem;
}

/* ─── Container ─── */
.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 0.625rem;
}
@media (min-width: 480px) {
  .container { padding: 0 1.25rem; }
}

/* ─── Breadcrumbs ─── */
.breadcrumbs {
  padding-top: 1.25rem;
  padding-bottom: 1.25rem;
}
.breadcrumb-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
}
.breadcrumb-item a {
  color: #6b7280;
  text-decoration: none;
  transition: color 0.2s;
}
.breadcrumb-item a:hover {
  color: var(--color-primary, #858585);
}
.breadcrumb-current {
  color: var(--store-text-primary, #111827);
  font-weight: 500;
}
.breadcrumb-arrow {
  display: flex;
  align-items: center;
  color: #9ca3af;
}

/* ─── Layout ─── */
.category-layout {
  display: flex;
  align-items: flex-start;
  flex-direction: column;
  gap: 0;
}
@media (min-width: 768px) {
  .category-layout {
    flex-direction: row;
  }
}

/* ─── Sidebar ─── */
.filters-sidebar {
  width: 100%;
  flex-shrink: 0;
  display: none;
}
@media (min-width: 768px) {
  .filters-sidebar {
    display: block;
    width: 18rem;
    position: sticky;
    top: 5rem;
  }
}
/* Mobile overlay mode */
.filters-sidebar--open {
  display: block;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 85%;
  max-width: 320px;
  z-index: 100;
  background: var(--bg-primary, #fff);
  overflow-y: auto;
  box-shadow: 4px 0 20px rgba(0,0,0,0.15);
  padding: 1rem;
  animation: slideIn 0.25s ease;
}
@keyframes slideIn {
  from { transform: translateX(-100%); }
  to   { transform: translateX(0); }
}
.filters-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  z-index: 99;
}
.filters-close-btn {
  display: none;
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #6b7280;
  z-index: 5;
}
.filters-sidebar--open .filters-close-btn {
  display: block;
}

/* ─── Filter Widget ─── */
.filter-widget {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.75rem;
  margin-bottom: 0.75rem;
}
.filter-widget__title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  cursor: pointer;
  padding: 0.5rem 0;
  margin: 0;
  user-select: none;
}
.filter-widget__toggle {
  width: 12px;
  height: 12px;
  position: relative;
}
.filter-widget__toggle::before,
.filter-widget__toggle::after {
  content: '';
  position: absolute;
  background: #6b7280;
  transition: transform 0.2s;
}
.filter-widget__toggle::before {
  width: 12px;
  height: 2px;
  top: 5px;
  left: 0;
}
.filter-widget__toggle::after {
  width: 2px;
  height: 12px;
  top: 0;
  left: 5px;
}
.filter-widget__toggle.active::after {
  transform: rotate(90deg);
  opacity: 0;
}
.filter-widget__content {
  padding-top: 0.25rem;
}
.filter-widget__search {
  padding: 0.25rem 0;
  margin-bottom: 0.5rem;
}
.filter-search-input {
  width: 100%;
  padding: 0.4rem 0.625rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
  outline: none;
  transition: border-color 0.2s;
}
.filter-search-input:focus {
  border-color: var(--color-primary, #858585);
}
.filter-widget__values {
  max-height: 350px;
  overflow-y: auto;
}
.filter-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.3rem 0;
  cursor: pointer;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  transition: color 0.15s;
}
.filter-label:hover {
  color: var(--color-primary, #858585);
}
.filter-radio {
  accent-color: var(--color-primary, #858585);
  width: 15px;
  height: 15px;
  flex-shrink: 0;
  cursor: pointer;
}
.filter-option-name {
  line-height: 1.4;
}

/* Rating stars */
.filter-label--rating {
  align-items: center;
}
.rating-stars {
  display: flex;
  gap: 1px;
}
.rating-star {
  fill: #d1d5db;
  transition: fill 0.15s;
}
.rating-star.filled {
  fill: #f59e0b;
}

/* Price range */
.price-range-custom {
  margin-top: 0.75rem;
}
.price-range-inputs {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}
.price-range-field {
  position: relative;
  flex: 1;
}
.price-range-currency {
  position: absolute;
  left: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.6875rem;
  color: #9ca3af;
  pointer-events: none;
}
.price-range-input {
  width: 100%;
  padding: 0.4rem 0.5rem 0.4rem 2.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.8125rem;
  outline: none;
  appearance: textfield;
  -moz-appearance: textfield;
  background: var(--bg-primary, #fff);
  color: var(--store-text-primary, #111827);
}
.price-range-input::-webkit-inner-spin-button,
.price-range-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.price-range-sep {
  color: #9ca3af;
  font-size: 0.875rem;
}
.price-range-go {
  width: 34px;
  height: 34px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  flex-shrink: 0;
  transition: all 0.2s;
}
.price-range-go:hover {
  border-color: var(--color-primary, #858585);
  color: var(--color-primary, #858585);
}

/* Filters footer */
.filters-footer {
  padding: 0.75rem 0;
}
.filters-reset-btn {
  width: 100%;
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: transparent;
  color: var(--store-text-primary, #111827);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}
.filters-reset-btn:hover {
  border-color: var(--color-primary, #858585);
  color: var(--color-primary, #858585);
}

/* ─── Main Content ─── */
.main-content {
  flex: 1;
  width: 100%;
  min-width: 0;
}
@media (min-width: 768px) {
  .main-content {
    margin-left: 2rem;
  }
}

/* ─── Category Header ─── */
.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  gap: 0.75rem;
  flex-wrap: wrap;
}
@media (min-width: 640px) {
  .category-header { margin-bottom: 1.5rem; }
}
.category-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0;
  line-height: 1.3;
}
.category-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.filter-trigger-btn {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: #f3f4f6;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}
.filter-trigger-btn:hover {
  background: #e5e7eb;
}
@media (min-width: 768px) {
  .filter-trigger-btn { display: none; }
}
.sort-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.sort-label {
  display: none;
  white-space: nowrap;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
}
@media (min-width: 640px) {
  .sort-label { display: block; }
}
.sort-select {
  padding: 0.375rem 2.25rem 0.375rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  background: var(--bg-secondary, #f9fafb);
  outline: none;
  cursor: pointer;
  appearance: auto;
  min-width: 140px;
}

/* ─── Product Grid ─── */
.products-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 480px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 900px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* ─── Load More ─── */
.load-more-wrapper {
  display: flex;
  justify-content: center;
  padding: 2rem 0;
}
.load-more-btn {
  padding: 0.625rem 2rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s;
}
.load-more-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
