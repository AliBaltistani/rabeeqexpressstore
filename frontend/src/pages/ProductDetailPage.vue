<template>
  <div class="pdp">
    <!-- Breadcrumb -->
    <nav class="pdp-breadcrumbs container">
      <ol class="breadcrumb-list">
        <li><router-link to="/">Home</router-link></li>
        <li class="breadcrumb-sep"><svg width="14" height="14" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg></li>
        <li><router-link to="/category/unisex-shoes">Unisex shoes</router-link></li>
        <li class="breadcrumb-sep"><svg width="14" height="14" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg></li>
        <li><router-link to="/category/trend-shoes">Trend Shoes</router-link></li>
        <li class="breadcrumb-sep"><svg width="14" height="14" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg></li>
        <li class="breadcrumb-current">{{ product.name }}</li>
      </ol>
    </nav>

    <div class="container">
      <div class="pdp-layout">
        <!-- ======== LEFT: IMAGE GALLERY ======== -->
        <div class="pdp-gallery">
          <!-- Main Image -->
          <div class="pdp-gallery__main">
            <span class="pdp-gallery__badge">Unisex shoes</span>
            <img ref="mainImgRef" :src="selectedImage" :alt="product.name" class="pdp-gallery__main-img" />
          </div>
          <!-- Thumbnails -->
          <div class="pdp-gallery__thumbs">
            <button
              v-for="(img, i) in product.images"
              :key="i"
              class="pdp-gallery__thumb"
              :class="{ active: selectedImage === img }"
              @click="selectedImage = img"
            >
              <img :src="img" :alt="`${product.name} view ${Number(i) + 1}`" />
            </button>
          </div>
        </div>

        <!-- ======== RIGHT: PRODUCT INFO ======== -->
        <div class="pdp-info">
          <!-- Share & Wishlist -->
          <div class="pdp-info__actions-top">
            <button ref="shareBtnRef" class="pdp-icon-btn" aria-label="Share" @click="handleShare">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </button>
            <button ref="wishlistBtnRef" class="pdp-icon-btn" :class="{ 'pdp-icon-btn--active': isWishlisted }" aria-label="Add to wishlist" @click="toggleWishlist" :disabled="togglingWishlist">
              <svg v-if="!togglingWishlist" width="20" height="20" viewBox="0 0 24 24" :fill="isWishlisted ? '#ef4444' : 'none'" :stroke="isWishlisted ? '#ef4444' : 'currentColor'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              <span v-else class="btn-spinner"></span>
            </button>
          </div>

          <!-- Title -->
          <h1 class="pdp-info__title">{{ product.name }}</h1>

          <!-- Price -->
          <div class="pdp-info__price-row">
            <span class="pdp-info__price">{{ product.salePrice }} SAR</span>
            <span v-if="product.oldPrice" class="pdp-info__old-price">{{ product.oldPrice }} SAR</span>
          </div>

          <!-- Tags/Categories -->
          <div class="pdp-info__tags">
            <router-link v-for="tag in product.tags" :key="tag" :to="`/category/${tag.toLowerCase().replace(/\s+/g, '-')}`" class="pdp-info__tag">{{ tag }}</router-link>
          </div>

          <!-- Stock Status -->
          <div class="pdp-info__stock">
            <span class="pdp-info__stock-badge in-stock">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              In Stock
            </span>
          </div>

          <!-- Sold Count -->
          <div class="pdp-info__sold">
            <span class="pdp-info__sold-icon">🔥</span>
            <span class="pdp-info__sold-text">Sold Out <strong>{{ product.soldCount }}</strong> Time</span>
          </div>

          <!-- Installment -->
          <div class="pdp-info__installment">
            <div class="pdp-info__installment-text">
              <p>Or split in <strong>4 payments</strong> of <strong>SAR {{ (product.salePrice / 4).toFixed(2) }}</strong></p>
              <p class="pdp-info__installment-sub">- No late fees, Sharia compliant! <a href="#" class="pdp-info__link">Learn more</a></p>
            </div>
            <img src="https://cdn.salla.sa/RvPxw/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png" alt="tamara" class="pdp-info__installment-logo" style="height:24px; width:auto;" />
          </div>

          <!-- Product Attributes -->
          <div v-if="productAttributes.length > 0" class="pdp-info__attributes">
            <div v-for="group in productAttributes" :key="group.id" class="pdp-info__attr-group">
              <div class="pdp-info__attr-header">
                <span class="pdp-info__attr-label">{{ group.name }} <span class="pdp-info__attr-req">*</span></span>
              </div>
              <select
                v-model="selectedAttributes[group.id]"
                class="pdp-info__attr-select"
              >
                <option value="" disabled>{{ $t('product.chooseOption') || 'Choose' }}</option>
                <option v-for="val in group.values" :key="val.id" :value="val.id">{{ val.value }}</option>
              </select>
            </div>
          </div>

          <!-- SKU & Weight -->
          <div class="pdp-info__meta-row" v-if="product.sku">
            <div class="pdp-info__meta">
              <span class="pdp-info__meta-icon">☰</span>
              <span class="pdp-info__meta-label">{{ $t('product.sku') }}</span>
              <span class="pdp-info__meta-value">{{ product.sku }}</span>
            </div>
          </div>
          <div class="pdp-info__meta-row" v-if="product.weight">
            <div class="pdp-info__meta">
              <span class="pdp-info__meta-icon">⚖</span>
              <span class="pdp-info__meta-label">{{ $t('product.weight') }}</span>
              <span class="pdp-info__meta-value">{{ product.weight }}</span>
            </div>
          </div>

          <!-- Price (repeated for sticky area) -->
          <div class="pdp-info__price-section">
            <span class="pdp-info__price-label">{{ $t('product.price') }}</span>
            <div class="pdp-info__price-values">
              <span class="pdp-info__price pdp-info__price--red">{{ product.priceFormatted || product.salePrice }}</span>
              <span v-if="product.oldPrice" class="pdp-info__old-price">{{ product.oldPriceFormatted || product.oldPrice }}</span>
            </div>
          </div>

          <!-- Quantity -->
          <div class="pdp-info__quantity-row">
            <span class="pdp-info__quantity-label">{{ $t('product.quantity') }}</span>
            <div class="pdp-info__quantity-control">
              <button class="pdp-qty-btn" @click="incrementQty" aria-label="Increase">+</button>
              <input type="number" v-model.number="quantity" min="1" class="pdp-qty-input" />
              <button class="pdp-qty-btn" @click="decrementQty" aria-label="Decrease">−</button>
            </div>
          </div>

          <!-- Add to Cart & Buy Now -->
          <div class="pdp-info__buttons">
            <button class="pdp-btn pdp-btn--cart" @click="addToCart" :disabled="addingToCart">
              <span v-if="addingToCart" class="btn-spinner"></span>
              <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              {{ $t('product.addToCart') }}
            </button>
            <button class="pdp-btn pdp-btn--buy" @click="buyNow" :disabled="buyingNow">
              <span v-if="buyingNow" class="btn-spinner"></span>
              <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              {{ $t('product.buyNow') }}
            </button>
          </div>
        </div>
      </div>

      <!-- ======== PRODUCT DETAILS TAB ======== -->
      <div class="pdp-details-section">
        <div class="pdp-tabs-layout">
          <!-- Vertical Tabs -->
          <div class="pdp-tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              class="pdp-tab"
              :class="{ active: activeTab === tab.key }"
              @click="activeTab = tab.key"
            >{{ tab.label }}</button>
          </div>

          <!-- Tab Content -->
          <div class="pdp-tab-content-area">
            <!-- Product Details -->
            <div v-show="activeTab === 'details'" class="pdp-tab-content">
              <h3 class="pdp-details__title">{{ product.name }}</h3>
              <div class="pdp-details__description" v-html="product.description"></div>
            </div>

            <!-- Product Rating -->
            <div v-show="activeTab === 'rating'" class="pdp-tab-content">
              <!-- Rating Breakdown -->
              <div class="pdp-rating-breakdown">
                <div class="pdp-rating-summary">
                  <span class="pdp-rating-avg">{{ averageRating }}</span>
                  <div class="pdp-rating-avg-stars">
                    <svg v-for="s in 5" :key="s" width="16" height="16" viewBox="0 0 24 24" :fill="s <= Math.round(Number(averageRating)) ? '#fbbf24' : 'none'" :stroke="s <= Math.round(Number(averageRating)) ? '#fbbf24' : '#d1d5db'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  </div>
                  <span class="pdp-rating-count">{{ reviews.length }} {{ $t('product.productRating') }}</span>
                </div>
                <div class="pdp-rating-bars">
                  <div v-for="star in [5,4,3,2,1]" :key="star" class="pdp-rating-bar-row">
                    <span class="pdp-rating-bar-label">{{ star }}★</span>
                    <div class="pdp-rating-bar-track">
                      <div class="pdp-rating-bar-fill" :style="{ width: ratingBarWidth(star) }"></div>
                    </div>
                    <span class="pdp-rating-bar-count">{{ ratingCounts[star] || 0 }}</span>
                  </div>
                </div>
              </div>

              <!-- Review Form -->
              <div class="pdp-review-form">
                <h4 class="pdp-review-form__title">{{ $t('product.writeReview') || 'Write a Review' }}</h4>
                <div class="pdp-review-form__stars">
                  <svg v-for="s in 5" :key="s" @click="reviewFormRating = s" width="24" height="24" viewBox="0 0 24 24" :fill="s <= reviewFormRating ? '#fbbf24' : 'none'" :stroke="s <= reviewFormRating ? '#fbbf24' : '#d1d5db'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="cursor:pointer;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <textarea v-model="reviewFormBody" class="pdp-review-form__textarea" :placeholder="$t('product.reviewPlaceholder') || 'Share your thoughts about this product...'" rows="4"></textarea>
                <button class="pdp-btn pdp-btn--cart pdp-review-form__submit" @click="handleSubmitReview" :disabled="reviewSubmitting">
                  {{ reviewSubmitting ? ($t('common.loading') || 'Loading...') : ($t('product.submitReview') || 'Submit Review') }}
                </button>
                <p v-if="reviewFormMsg" class="pdp-review-form__msg" :class="{ error: reviewFormError }">{{ reviewFormMsg }}</p>
              </div>

              <!-- Reviews List -->
              <div v-if="reviews.length > 0" class="pdp-reviews-list">
                <div v-for="review in reviews" :key="review.id || review.createdAt" class="pdp-review-item">
                  <div class="pdp-review-header">
                    <div class="pdp-review-avatar">{{ (review.customerName || 'U').charAt(0).toUpperCase() }}</div>
                    <div class="pdp-review-header-info">
                      <span class="pdp-review-author">{{ review.customerName }}</span>
                      <span class="pdp-review-date">{{ new Date(review.createdAt).toLocaleDateString() }}</span>
                    </div>
                    <div class="pdp-review-stars">
                      <svg v-for="s in 5" :key="s" width="14" height="14" viewBox="0 0 24 24" :fill="s <= review.rating ? '#fbbf24' : 'none'" :stroke="s <= review.rating ? '#fbbf24' : '#d1d5db'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                  </div>
                  <h4 v-if="review.title" class="pdp-review-title">{{ review.title }}</h4>
                  <p class="pdp-review-body">{{ review.body }}</p>
                  <div v-if="review.adminReply" class="pdp-review-reply">
                    <strong>Admin Reply:</strong> {{ review.adminReply }}
                  </div>
                </div>
                <button v-if="reviewsHasMore" class="pdp-btn pdp-btn--load-more" @click="loadMoreReviews">
                  {{ $t('category.loadMore') }}
                </button>
              </div>
              <p v-else class="pdp-details__empty">{{ $t('product.noReviews') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ======== RELATED PRODUCTS ======== -->
      <div class="pdp-related" v-if="relatedProducts.length > 0">
        <div class="pdp-related__header">
          <h2 class="pdp-related__title">{{ $t('product.relatedProducts') }}</h2>
          <div class="pdp-related__arrows">
            <button class="pdp-arrow-btn" @click="scrollRelated(-1)" aria-label="Previous"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
            <button class="pdp-arrow-btn" @click="scrollRelated(1)" aria-label="Next"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
          </div>
        </div>
        <div class="pdp-related__track" ref="relatedTrackRef">
          <div v-for="rp in relatedProducts" :key="rp.id" class="pdp-related__item">
            <ProductCard :product="rp" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import ProductCard from '@/components/home/ProductCard.vue'
import { fetchProductBySlug, fetchProducts, fetchProductReviews, submitReview } from '@/api/services'
import { useCartStore } from '@/stores/cartStore'
import { useWishlistStore } from '@/stores/wishlistStore'
import { flyToCart, pulseElement } from '@/composables/useActionAnimations'
import { useCartToast } from '@/composables/useCartToast'
import { useShareMenu } from '@/composables/useShareMenu'
import type { ProductDetail } from '@/types'
import type { ProductAttributeGroup } from '@/types'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const cart = useCartStore()
const wishlist = useWishlistStore()
const { showToast } = useCartToast()
const { openShare } = useShareMenu()

// ─── Product Data (API-driven) ───
const product = ref<any>({
  id: 0,
  name: '',
  slug: '',
  salePrice: 0,
  oldPrice: null,
  currency: 'SAR',
  sku: '',
  weight: '',
  soldCount: 0,
  tags: [],
  attributes: [],
  images: [],
  description: '',
})
const isLoading = ref(true)

const selectedImage = ref('')
const selectedAttributes = reactive<Record<number, number | string>>({})
const quantity = ref(1)
const addingToCart = ref(false)
const buyingNow = ref(false)
const togglingWishlist = ref(false)
const mainImgRef = ref<HTMLImageElement | null>(null)
const shareBtnRef = ref<HTMLElement | null>(null)
const wishlistBtnRef = ref<HTMLElement | null>(null)

const isWishlisted = computed(() => wishlist.isInWishlist(product.value.id))
const activeTab = ref('details')

// Compute attribute groups from product.attributes
const productAttributes = computed(() => {
  return product.value.attributes || []
})

// Initialize selectedAttributes when product changes
function initSelectedAttributes() {
  // Clear previous selections
  Object.keys(selectedAttributes).forEach(k => delete selectedAttributes[Number(k)])
  // Set default selection to first value of each attribute group
  for (const group of productAttributes.value) {
    if (group.values?.length) {
      selectedAttributes[group.id] = group.values[0].id
    }
  }
}

const tabs = computed(() => [
  { key: 'details', label: t('product.productDetails') },
  { key: 'rating', label: t('product.productRating') },
])

function incrementQty() {
  quantity.value++
}
function decrementQty() {
  if (quantity.value > 1) quantity.value--
}
async function addToCart() {
  addingToCart.value = true
  try {
    flyToCart(mainImgRef.value)
    await cart.addItem(product.value.id, quantity.value)
    const p = product.value
    showToast({
      name: p.name,
      image: p.images?.[0] || selectedImage.value || '/storage/dummy/placeholder.jpg',
      price: p.priceFormatted || `${p.salePrice} ${p.currency}`,
    })
  } catch (e) {
    console.error('Add to cart failed:', e)
  } finally {
    addingToCart.value = false
  }
}

async function buyNow() {
  buyingNow.value = true
  try {
    flyToCart(mainImgRef.value)
    await cart.addItem(product.value.id, quantity.value)
    router.push('/checkout')
  } catch (e) {
    console.error('Buy now failed:', e)
  } finally {
    buyingNow.value = false
  }
}

async function toggleWishlist() {
  togglingWishlist.value = true
  try {
    await wishlist.toggleItem(product.value.id)
    pulseElement(wishlistBtnRef.value)
  } catch (e) {
    console.error('Toggle wishlist failed:', e)
  } finally {
    togglingWishlist.value = false
  }
}

function handleShare() {
  openShare(
    { title: product.value.name, url: window.location.href },
    shareBtnRef.value,
  )
}

// ─── Related Products ───
const relatedTrackRef = ref<HTMLElement | null>(null)
function scrollRelated(dir: number) {
  if (!relatedTrackRef.value) return
  relatedTrackRef.value.scrollBy({ left: dir * 300, behavior: 'smooth' })
}
const relatedProducts = ref<any[]>([])

// ─── Reviews ───
const reviews = ref<any[]>([])
const reviewsPage = ref(1)
const reviewsHasMore = ref(false)

async function loadMoreReviews() {
  if (!product.value.slug || !reviewsHasMore.value) return
  try {
    const res = await fetchProductReviews(product.value.slug, reviewsPage.value + 1)
    if (res.data && res.meta) {
      reviews.value.push(...res.data)
      reviewsPage.value = res.meta.page
      reviewsHasMore.value = res.meta.page < res.meta.lastPage
    }
  } catch (error) {
    console.error('Failed to fetch more reviews:', error)
  }
}

// ─── Rating Breakdown ───
const ratingCounts = computed(() => {
  const counts: Record<number, number> = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
  for (const r of reviews.value) {
    const s = Math.round(r.rating)
    if (s >= 1 && s <= 5) counts[s]++
  }
  return counts
})

const averageRating = computed(() => {
  if (!reviews.value.length) return '0.0'
  const sum = reviews.value.reduce((acc: number, r: any) => acc + r.rating, 0)
  return (sum / reviews.value.length).toFixed(1)
})

function ratingBarWidth(star: number): string {
  const total = reviews.value.length
  if (!total) return '0%'
  return `${((ratingCounts.value[star] || 0) / total) * 100}%`
}

// ─── Review Form ───
const reviewFormRating = ref(0)
const reviewFormBody = ref('')
const reviewSubmitting = ref(false)
const reviewFormMsg = ref('')
const reviewFormError = ref(false)

async function handleSubmitReview() {
  reviewFormMsg.value = ''
  reviewFormError.value = false
  if (reviewFormRating.value < 1) {
    reviewFormMsg.value = 'Please select a rating'
    reviewFormError.value = true
    return
  }
  if (!reviewFormBody.value.trim()) {
    reviewFormMsg.value = 'Please write a review'
    reviewFormError.value = true
    return
  }
  reviewSubmitting.value = true
  try {
    await submitReview(product.value.slug, {
      rating: reviewFormRating.value,
      body: reviewFormBody.value.trim(),
    })
    reviewFormMsg.value = 'Review submitted successfully!'
    reviewFormRating.value = 0
    reviewFormBody.value = ''
    // Reload reviews
    const res = await fetchProductReviews(product.value.slug, 1)
    reviews.value = res.data || []
    reviewsPage.value = 1
    reviewsHasMore.value = res.meta ? res.meta.page < res.meta.lastPage : false
  } catch (e: any) {
    reviewFormError.value = true
    reviewFormMsg.value = e.response?.data?.message || 'Failed to submit review. Please login first.'
  } finally {
    reviewSubmitting.value = false
  }
}

// ─── Fetch Product from API ───
async function loadProduct(slug: string) {
  isLoading.value = true
  try {
    const data = await fetchProductBySlug(slug)

    // Map API response to component shape
    const images = (data.images || []).map((img: any) => img.url).filter(Boolean)

    product.value = {
      id: data.id,
      name: data.name,
      slug: data.slug,
      salePrice: data.flashSalePrice?.raw ?? data.price?.raw ?? 0,
      oldPrice: data.comparePrice?.raw || null,
      currency: data.currency || 'SAR',
      sku: data.sku || '',
      weight: data.weight || '',
      soldCount: data.reviewCount || 0,
      tags: (data.tags || []).map((t: any) => typeof t === 'string' ? t : t.name),
      attributes: data.attributes || [],
      images: images.length ? images : [data.primaryImage].filter(Boolean),
      description: data.description || data.shortDescription || '',
      priceFormatted: data.flashSalePrice?.formatted ?? data.price?.formatted ?? '',
      oldPriceFormatted: data.comparePrice?.formatted || '',
      inStock: data.inStock,
      category: data.category,
      brand: data.brand,
    }

    initSelectedAttributes()

    const primaryObj = (data.images || []).find((img: any) => img.isPrimary);
    selectedImage.value = primaryObj ? primaryObj.url : (product.value.images[0] || product.value.primaryImage || '');

    // Fetch related products from the same category
    if (data.category?.slug) {
      try {
        const related = await fetchProducts({ category: data.category.slug, perPage: 6 })
        relatedProducts.value = (related.data || [])
          .filter((p: any) => p.id !== data.id)
          .slice(0, 5)
          .map((p: any) => ({
            id: p.id,
            slug: p.slug,
            name: p.name,
            subtitle: p.category?.name || '',
            image: p.primaryImage || '',
            primaryImage: p.primaryImage || '',
            images: p.images || [],
            price: p.flashSalePrice?.raw ?? p.price?.raw ?? 0,
            currency: p.currency || 'SAR',
          }))
      } catch {
        relatedProducts.value = []
      }
    }

    // Fetch initial reviews
    try {
      reviewsPage.value = 1
      const res = await fetchProductReviews(slug, 1)
      reviews.value = res.data || []
      reviewsHasMore.value = res.meta ? res.meta.page < res.meta.lastPage : false
    } catch (e) {
      reviews.value = []
    }
  } catch (error) {
    console.error('Failed to load product:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  const slug = route.params.slug as string
  if (slug) loadProduct(slug)
})

// Re-fetch when route slug changes (for related product navigation)
watch(() => route.params.slug, (newSlug) => {
  if (newSlug && typeof newSlug === 'string') {
    loadProduct(newSlug)
    quantity.value = 1
  }
})
</script>

<style scoped>
/* ─── Page ─── */
.pdp {
  background: var(--bg-primary, #fff);
  padding-bottom: 3rem;
}
.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 0.625rem;
}
@media (min-width: 480px) {
  .container { padding: 0 1.25rem; }
}

/* ─── Breadcrumbs ─── */
.pdp-breadcrumbs {
  padding: 0.75rem 0;
}
.breadcrumb-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.25rem;
  font-size: 0.8125rem;
  color: #6b7280;
}
.breadcrumb-list a {
  color: #6b7280;
  text-decoration: underline;
  transition: color 0.2s;
}
.breadcrumb-list a:hover {
  color: var(--color-primary, #858585);
}
.breadcrumb-sep {
  display: flex;
  align-items: center;
  color: #9ca3af;
}
.breadcrumb-current {
  color: var(--store-text-primary, #111827);
}

/* ─── Layout ─── */
.pdp-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  margin-bottom: 2.5rem;
}
@media (min-width: 768px) {
  .pdp-layout {
    grid-template-columns: 1fr 1fr;
    gap: 2.5rem;
  }
}

/* ─── Gallery ─── */
.pdp-gallery {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
}
@media (min-width: 768px) {
  .pdp-gallery {
    flex-direction: row;
  }
}
.pdp-gallery__main {
  position: relative;
  flex: 1;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 1;
}
.pdp-gallery__main-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 1rem;
}
.pdp-gallery__badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: #ef4444;
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 4px;
  z-index: 2;
}
.pdp-gallery__thumbs {
  display: flex;
  gap: 0.5rem;
  flex-direction: row;
  overflow-x: auto;
}
@media (min-width: 768px) {
  .pdp-gallery__thumbs {
    flex-direction: column;
    width: 80px;
    flex-shrink: 0;
    overflow-y: auto;
    max-height: 500px;
  }
}
.pdp-gallery__thumb {
  flex-shrink: 0;
  width: 68px;
  height: 68px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  background: #fff;
  padding: 4px;
  transition: border-color 0.2s;
}
.pdp-gallery__thumb.active {
  border-color: var(--color-primary, #858585);
}
.pdp-gallery__thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* ─── Info ─── */
.pdp-info {
  position: relative;
}
.pdp-info__actions-top {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  margin-bottom: 0.5rem;
}
.pdp-icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid #e5e7eb;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9ca3af;
  transition: all 0.2s;
}
.pdp-icon-btn:hover {
  border-color: var(--color-primary, #858585);
  color: var(--color-primary, #858585);
}
.pdp-info__title {
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  line-height: 1.3;
  margin: 0 0 0.75rem;
}
.pdp-info__price-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.pdp-info__price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ef4444;
}
.pdp-info__price--red {
  color: #ef4444;
}
.pdp-info__old-price {
  font-size: 0.9375rem;
  color: #9ca3af;
  text-decoration: line-through;
}
.pdp-info__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.pdp-info__tag {
  font-size: 0.75rem;
  color: var(--store-text-primary, #111827);
  text-decoration: underline;
  transition: color 0.2s;
}
.pdp-info__tag:hover {
  color: var(--color-primary, #858585);
}
.pdp-info__stock {
  margin-bottom: 0.375rem;
}
.pdp-info__stock-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 500;
}
.pdp-info__stock-badge.in-stock {
  color: #22c55e;
}
.pdp-info__sold {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin-bottom: 1rem;
  font-size: 0.8125rem;
  color: #ef4444;
  font-weight: 500;
}
.pdp-info__installment {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.875rem 1rem;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 1.25rem;
}
.pdp-info__installment-text {
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  line-height: 1.5;
}
.pdp-info__installment-text p {
  margin: 0;
}
.pdp-info__installment-sub {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.125rem !important;
}
.pdp-info__link {
  color: var(--color-primary, #858585);
  text-decoration: underline;
  font-weight: 600;
}

/* Option (Size) */
.pdp-info__option {
  margin-bottom: 1.25rem;
}
.pdp-info__option-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.pdp-info__option-label {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}
.pdp-info__required {
  color: #ef4444;
}
.pdp-info__option-sublabel {
  font-size: 0.8125rem;
  color: #6b7280;
}
.pdp-info__select {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
  outline: none;
  cursor: pointer;
  appearance: auto;
}

/* Meta: SKU, Weight */
.pdp-info__meta-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f3f4f6;
}
.pdp-info__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  justify-content: space-between;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
}
.pdp-info__meta-label {
  font-weight: 600;
}
.pdp-info__meta-value {
  color: #6b7280;
}

/* Price section */
.pdp-info__price-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  border-bottom: 1px solid #f3f4f6;
}
.pdp-info__price-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--store-text-primary, #111827);
}
.pdp-info__price-values {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Quantity */
.pdp-info__quantity-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
}
.pdp-info__quantity-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--store-text-primary, #111827);
}
.pdp-info__quantity-control {
  display: flex;
  align-items: center;
  gap: 0;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.pdp-qty-btn {
  width: 36px;
  height: 36px;
  background: transparent;
  border: none;
  font-size: 1.125rem;
  cursor: pointer;
  color: var(--store-text-primary, #111827);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.pdp-qty-btn:hover {
  background: #f3f4f6;
}
.pdp-qty-input {
  width: 40px;
  text-align: center;
  border: none;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  font-size: 0.875rem;
  font-weight: 600;
  outline: none;
  appearance: textfield;
  -moz-appearance: textfield;
  color: var(--store-text-primary, #111827);
  background: transparent;
}
.pdp-qty-input::-webkit-inner-spin-button,
.pdp-qty-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
}

/* Buttons */
.pdp-info__buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-top: 1rem;
}
.pdp-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s;
  border: none;
}
.pdp-btn--cart {
  background: var(--color-primary, #858585);
  color: #fff;
}
.pdp-btn--cart:hover {
  opacity: 0.9;
}
.pdp-btn--buy {
  background: transparent;
  border: 1.5px solid #e5e7eb;
  color: var(--store-text-primary, #111827);
}
.pdp-btn--buy:hover {
  border-color: var(--color-primary, #858585);
  color: var(--color-primary, #858585);
}
.pdp-btn:disabled {
  cursor: wait;
  opacity: 0.7;
}
.pdp-icon-btn:disabled {
  cursor: wait;
  opacity: 0.7;
}

/* ─── Details Tabs (Vertical Layout) ─── */
.pdp-details-section {
  margin-bottom: 3rem;
}
.pdp-tabs-layout {
  display: flex;
  gap: 0;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  min-height: 300px;
}
.pdp-tabs {
  display: flex;
  flex-direction: column;
  border-right: 1px solid #e5e7eb;
  background: #f9fafb;
  flex-shrink: 0;
  min-width: 160px;
}
.pdp-tab {
  padding: 1rem 1.5rem;
  background: none;
  border: none;
  border-right: 3px solid transparent;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}
html[dir="rtl"] .pdp-tabs {
  border-right: none;
  border-left: 1px solid #e5e7eb;
}
html[dir="rtl"] .pdp-tab {
  border-right: none;
  border-left: 3px solid transparent;
  text-align: right;
}
.pdp-tab:hover {
  color: var(--store-text-primary, #111827);
  background: #f3f4f6;
}
.pdp-tab.active {
  color: var(--store-text-primary, #111827);
  border-right-color: var(--color-primary, #858585);
  background: #fff;
}
html[dir="rtl"] .pdp-tab.active {
  border-right-color: transparent;
  border-left-color: var(--color-primary, #858585);
}
.pdp-tab-content-area {
  flex: 1;
  padding: 1.5rem 2rem;
  overflow-y: auto;
}
.pdp-tab-content {
  max-width: 100%;
}
@media (max-width: 767px) {
  .pdp-tabs-layout {
    flex-direction: column;
  }
  .pdp-tabs {
    flex-direction: row;
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    min-width: auto;
    overflow-x: auto;
  }
  .pdp-tab {
    border-right: none;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    padding: 0.75rem 1.25rem;
  }
  .pdp-tab.active {
    border-right-color: transparent;
    border-bottom-color: var(--color-primary, #858585);
  }
  html[dir="rtl"] .pdp-tabs {
    border-left: none;
    border-bottom: 1px solid #e5e7eb;
  }
  html[dir="rtl"] .pdp-tab {
    border-left: none;
    border-bottom: 3px solid transparent;
  }
  html[dir="rtl"] .pdp-tab.active {
    border-left-color: transparent;
    border-bottom-color: var(--color-primary, #858585);
  }
  .pdp-tab-content-area {
    padding: 1.25rem 1rem;
  }
}
.pdp-details__title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
  color: var(--store-text-primary, #111827);
}
.pdp-details__tagline {
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
  margin: 0 0 1rem;
}
.pdp-details__description {
  font-size: 0.875rem;
  line-height: 1.65;
  color: var(--store-text-primary, #111827);
}
.pdp-details__description :deep(ul) {
  padding-left: 1.25rem;
  margin: 0.75rem 0;
}
.pdp-details__description :deep(li) {
  margin-bottom: 0.5rem;
}
.pdp-details__empty {
  color: #9ca3af;
  font-size: 0.875rem;
}

/* Rating Breakdown */
.pdp-rating-breakdown {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}
.pdp-rating-summary {
  text-align: center;
  flex-shrink: 0;
  min-width: 80px;
}
.pdp-rating-avg {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--store-text-primary, #111827);
  line-height: 1;
}
.pdp-rating-avg-stars {
  display: flex;
  justify-content: center;
  gap: 0.125rem;
  margin: 0.375rem 0;
}
.pdp-rating-count {
  font-size: 0.75rem;
  color: #6b7280;
}
.pdp-rating-bars {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}
.pdp-rating-bar-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.pdp-rating-bar-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  min-width: 24px;
  text-align: right;
}
.pdp-rating-bar-track {
  flex: 1;
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}
.pdp-rating-bar-fill {
  height: 100%;
  background: #fbbf24;
  border-radius: 4px;
  transition: width 0.3s ease;
}
.pdp-rating-bar-count {
  font-size: 0.75rem;
  color: #9ca3af;
  min-width: 16px;
  text-align: left;
}

/* Review Form */
.pdp-review-form {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}
.pdp-review-form__title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 0.75rem;
}
.pdp-review-form__stars {
  display: flex;
  gap: 0.25rem;
  margin-bottom: 0.875rem;
}
.pdp-review-form__stars svg:hover ~ svg {
  fill: none !important;
  stroke: #d1d5db !important;
}
.pdp-review-form__textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
  background: #fff;
  outline: none;
  resize: vertical;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.2s;
}
.pdp-review-form__textarea:focus {
  border-color: var(--color-primary, #858585);
}
.pdp-review-form__submit {
  margin-top: 0.75rem;
  width: auto;
  padding: 0.625rem 2rem;
}
.pdp-review-form__msg {
  font-size: 0.8125rem;
  margin: 0.5rem 0 0;
  color: #22c55e;
}
.pdp-review-form__msg.error {
  color: #ef4444;
}

/* Reviews List */
.pdp-reviews-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.pdp-review-item {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 1.5rem;
}
.pdp-review-item:last-child {
  border-bottom: none;
}
.pdp-review-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}
.pdp-review-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--color-primary, #858585);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  flex-shrink: 0;
}
.pdp-review-header-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.pdp-review-author {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
}
.pdp-review-date {
  font-size: 0.6875rem;
  color: #9ca3af;
}
.pdp-review-stars {
  display: flex;
  gap: 0.125rem;
}
.pdp-review-title {
  font-size: 0.9375rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
  color: var(--store-text-primary, #111827);
}
.pdp-review-body {
  font-size: 0.875rem;
  color: #4b5563;
  margin: 0;
  line-height: 1.5;
}
.pdp-review-reply {
  margin-top: 1rem;
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  color: #374151;
  border-left: 3px solid var(--color-primary, #858585);
}
.pdp-btn--load-more {
  background: transparent;
  border: 1px solid var(--color-primary, #858585);
  color: var(--color-primary, #858585);
  margin: 1rem auto 0;
  width: max-content;
}

/* ─── Related Products ─── */
.pdp-related {
  margin-bottom: 2rem;
}
.pdp-related__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.pdp-related__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0;
}
.pdp-related__arrows {
  display: flex;
  gap: 0.5rem;
}
.pdp-arrow-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid #e5e7eb;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}
.pdp-arrow-btn:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}
.pdp-related__track {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
  -ms-overflow-style: none;
  padding-bottom: 0.5rem;
}
.pdp-related__track::-webkit-scrollbar {
  display: none;
}
.pdp-related__item {
  flex: 0 0 220px;
  scroll-snap-align: start;
}
@media (min-width: 768px) {
  .pdp-related__item {
    flex: 0 0 calc(20% - 0.8rem);
    min-width: 200px;
  }
}

/* ─── Product Attributes ─── */
.pdp-info__attributes {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  margin-bottom: 1.25rem;
}
.pdp-info__attr-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}
.pdp-info__attr-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.pdp-info__attr-label {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}
.pdp-info__attr-req {
  color: #ef4444;
}
.pdp-info__attr-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
  outline: none;
  cursor: pointer;
  appearance: auto;
  transition: border-color 0.2s;
}
.pdp-info__attr-select:focus {
  border-color: var(--color-primary, #858585);
}
</style>
