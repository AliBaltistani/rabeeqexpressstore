<template>
  <Teleport to="body">
    <!-- Overlay -->
    <transition name="qv-overlay">
      <div v-if="isOpen" class="quickview__overlay" @click="close"></div>
    </transition>

    <!-- Modal Content -->
    <transition name="qv-content">
      <div v-if="isOpen && product" class="quickview__content" @click.self="close">
        <!-- Close Button -->
        <button class="quickview__btn-close" @click="close" aria-label="Close quick view">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>

        <div class="product-quickview">
          <div class="product-quickview__row">
            <!-- Left: Image Gallery -->
            <div class="product-quickview__image-col">
              <div class="product-quickview__images">
                <img
                  :src="selectedImage"
                  :alt="product.name"
                  class="product-quickview__img"
                />
              </div>
              <!-- Thumbnails (only if multiple images) -->
              <div v-if="productImages.length > 1" class="product-quickview__thumbs">
                <button
                  v-for="(img, i) in productImages"
                  :key="img.id || i"
                  class="product-quickview__thumb"
                  :class="{ active: selectedImage === img.url }"
                  @click="selectedImage = img.url"
                >
                  <img :src="img.url" :alt="img.alt || `${product.name} view ${i + 1}`" />
                </button>
              </div>
            </div>

            <!-- Right: Info -->
            <div class="product-quickview__info-col">
              <div class="product-quickview__details">
                <!-- Title row: title left, actions right -->
                <div class="quickview__title-row">
                  <router-link :to="'/product/' + product.slug" class="product-quickview__title" @click="close">
                    <h2>{{ product.name }}</h2>
                  </router-link>
                  <div class="quickview-actions">
                    <button class="quickview-actions__btn" :class="{ 'quickview-actions__btn--active': isWishlisted }" aria-label="Add to wishlist" @click="toggleWishlist" :disabled="togglingWishlist">
                      <svg v-if="!togglingWishlist" width="20" height="20" viewBox="0 0 24 24" :fill="isWishlisted ? '#ef4444' : 'none'" :stroke="isWishlisted ? '#ef4444' : 'currentColor'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                      </svg>
                      <span v-else class="btn-spinner"></span>
                    </button>
                    <button ref="qvShareBtnRef" class="quickview-actions__btn" aria-label="Share product" @click="shareProduct">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Price -->
                <div class="product-quickview__price">
                  <div class="price-container">
                    <div v-if="product.oldPrice" class="price-sale">
                      <p class="sale-price">{{ formatPrice(product.price) }}</p>
                      <span class="regular-price">{{ formatPrice(product.oldPrice) }}</span>
                    </div>
                    <p v-else class="total-price">{{ formatPrice(product.price) }}</p>
                  </div>
                </div>

                <!-- Category tag -->
                <div v-if="fullProduct?.category?.name ?? product.category?.name" class="qv-category-tag">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                  {{ fullProduct?.category?.name ?? product.category?.name }}
                </div>

                <!-- Stock + Sold stats (always shown, matching PDP logic) -->
                <div class="quickview__stats">

                  <!-- In Stock badge -->
                  <div v-if="fullProduct?.inStock ?? product.inStock" class="inventory-content">
                    <div class="qv-stock-badge">
                      <svg class="qv-check-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline class="qv-check-poly" points="20 6 9 17 4 12"/>
                      </svg>
                      <span>{{ $t('product.inStock') }}</span>
                    </div>
                  </div>

                  <!-- Out of Stock badge (shown when explicitly false) -->
                  <div v-else-if="(fullProduct?.inStock ?? product.inStock) === false" class="inventory-content">
                    <div class="qv-out-of-stock-badge">
                      <svg class="qv-oos-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                      </svg>
                      <span>{{ $t('product.outOfStock') }}</span>
                    </div>
                  </div>

                  <!-- Sold count — always renders (no count guard), same as PDP -->
                  <div class="sold-count">
                    <svg class="qv-flame-svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path class="qv-flame-path" d="M12 2C12 2 7 8 7 13a5 5 0 0 0 10 0c0-2.5-1.5-5-3-7 0 0-.5 2-2 3C10.5 7.5 12 2 12 2z" fill="#f97316"/>
                      <path d="M12 10c0 0-1.5 2-1.5 3.5a1.5 1.5 0 0 0 3 0C13.5 12 12 10 12 10z" fill="#fbbf24"/>
                    </svg>
                    <span>{{ $t('product.soldCount', { count: fullProduct?.reviews?.count ?? product.soldCount ?? 0 }) }}</span>
                  </div>

                </div>

                <!-- Description -->
                <div class="quickview-description">
                  <p>Premium quality product from {{ product.subtitle || 'our store' }}. Experience comfort and style with this exceptional piece.</p>
                  <router-link :to="'/product/' + product.slug" class="link--primary" @click="close">More details</router-link>
                </div>

                <!-- Product Attributes -->
                <div v-if="productAttributes.length > 0" class="quickview__attributes">
                  <div v-for="group in productAttributes" :key="group.id" class="quickview__attr-group">
                    <div class="quickview__attr-header">
                      <span class="quickview__attr-label">{{ group.name }} <span class="quickview__attr-req">*</span></span>
                    </div>
                    <select
                      v-model="selectedAttributes[group.id]"
                      class="quickview__attr-select"
                    >
                      <option value="" disabled>Choose</option>
                      <option v-for="val in group.values" :key="val.id" :value="val.id">{{ val.value }}</option>
                    </select>
                  </div>
                </div>

                <!-- Add to Cart + Quantity (in stock only) -->
                <div v-if="fullProduct?.inStock ?? product.inStock ?? true" class="quickview__cart-row">
                  <button class="quickview__add-btn" @click="addToCart" :disabled="addingToCart">
                    <span v-if="addingToCart" class="qv-spinner"></span>
                    <template v-else>
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                      </svg>
                      <span>Add to cart</span>
                    </template>
                  </button>
                  <div class="quickview__quantity">
                    <button class="quickview__qty-btn" @click="decrementQty" :disabled="quantity <= 1">−</button>
                    <input type="number" v-model.number="quantity" min="1" class="quickview__qty-input" />
                    <button class="quickview__qty-btn" @click="incrementQty">+</button>
                  </div>
                </div>

                <!-- Out of Stock actions -->
                <div v-else class="quickview__cart-row quickview__cart-row--oos">
                  <div class="qv-oos-pill">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ $t('product.outOfStock') }}
                  </div>
                  <a
                    v-if="settingsStore.storeSettings.whatsappNumber"
                    :href="'https://wa.me/' + settingsStore.storeSettings.whatsappNumber.replace('+', '') + '?text=' + encodeURIComponent($t('product.whatsappMsg', { name: product.name }))"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="qv-contact-btn"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    {{ $t('footer.contactUs') }}
                  </a>
                  <a
                    v-else
                    :href="'mailto:' + (settingsStore.storeSettings.email || '') + '?subject=' + encodeURIComponent($t('product.whatsappMsg', { name: product.name }))"
                    class="qv-contact-btn"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0 1.1.9 2 2 2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    {{ $t('footer.contactUs') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useQuickView } from '@/composables/useQuickView'
import { useCartStore } from '@/stores/cartStore'
import { useWishlistStore } from '@/stores/wishlistStore'
import { flyToCart, pulseElement } from '@/composables/useActionAnimations'
import { useCartToast } from '@/composables/useCartToast'
import { useShareMenu } from '@/composables/useShareMenu'
import { fetchProductBySlug } from '@/api/services'
import { useSettingsStore } from '@/stores/settingsStore'

const { isOpen, product, close } = useQuickView()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const settingsStore = useSettingsStore()
const { showToast } = useCartToast()
const { openShare } = useShareMenu()

const quantity = ref(1)
const selectedImage = ref('')
const addingToCart = ref(false)
const togglingWishlist = ref(false)
const qvShareBtnRef = ref<HTMLElement | null>(null)
const selectedAttributes = reactive<Record<number, number | string>>({})
const isLoadingDetail = ref(false)
const fullProduct = ref<any>(null)

// Build images list from product data
const productImages = computed(() => {
  if (!product.value) return []
  const imgs = product.value.images
  if (imgs && imgs.length > 0) {
    return imgs
  }
  const fallback = product.value.primaryImage || product.value.image
  return fallback ? [{ id: 0, url: fallback, alt: product.value.name, isPrimary: true }] : []
})

// Build attribute groups from full product data (fetched via API)
const productAttributes = computed(() => {
  if (fullProduct.value?.attributes?.length) return fullProduct.value.attributes
  if (product.value?.attributes?.length) return product.value.attributes
  return []
})

// Auto-select the primary image and fetch full product when product changes
watch(() => product.value, async (p) => {
  if (!p) return
  const imgs = productImages.value
  const primary = imgs.find(img => img.isPrimary) || imgs[0]
  selectedImage.value = primary?.url || p.primaryImage || p.image || ''
  quantity.value = 1
  fullProduct.value = null

  // Fetch full product detail to get attributes
  if (p.slug) {
    isLoadingDetail.value = true
    try {
      const detail = await fetchProductBySlug(p.slug)
      fullProduct.value = detail
      // Build images from fetched detail if available
      if (detail.images?.length) {
        const fetchedPrimary = detail.images.find((img: any) => img.isPrimary) || detail.images[0]
        selectedImage.value = fetchedPrimary?.url || selectedImage.value
      }
    } catch (e) {
      console.error('Failed to fetch product detail for QuickView:', e)
    } finally {
      isLoadingDetail.value = false
    }
  }

  // Init attribute selections
  Object.keys(selectedAttributes).forEach(k => delete selectedAttributes[Number(k)])
  // Use a short delay to let fullProduct populate
  setTimeout(() => {
    for (const group of productAttributes.value) {
      if (group.values?.length) {
        selectedAttributes[group.id] = group.values[0].id
      }
    }
  }, 100)
}, { immediate: true })

function formatPrice(price: any): string {
  if (price && typeof price === 'object' && price.formatted) {
    return price.formatted
  }
  const cur = product.value?.currency || 'SAR'
  return `${Number(price).toFixed(0)} ${cur}`
}

const isWishlisted = computed(() => product.value ? wishlistStore.isInWishlist(product.value.id) : false)

async function addToCart() {
  if (!product.value?.id || addingToCart.value) return
  addingToCart.value = true
  try {
    // Fly the quickview image to cart
    const imgEl = document.querySelector('.product-quickview__img') as HTMLElement | null
    flyToCart(imgEl)
    await cartStore.addItem(product.value.id, quantity.value, undefined,
      Object.values(selectedAttributes).filter(Boolean).map(v => Number(v)) || undefined
    )
    const p = product.value
    showToast({
      name: p.name,
      image: p.primaryImage || p.image || '/storage/dummy/placeholder.jpg',
      price: formatPrice(p.price),
    })
  } catch (e) {
    console.error('Quick view - Add to cart failed:', e)
  } finally {
    addingToCart.value = false
  }
}

async function toggleWishlist() {
  if (!product.value?.id) return
  togglingWishlist.value = true
  try {
    await wishlistStore.toggleItem(product.value.id)
  } catch (e) {
    console.error('Toggle wishlist failed:', e)
  } finally {
    togglingWishlist.value = false
  }
}

function shareProduct() {
  if (!product.value) return
  openShare(
    { title: product.value.name, url: `${window.location.origin}/product/${product.value.slug}` },
    qvShareBtnRef.value,
  )
}

function incrementQty() {
  quantity.value++
}

function decrementQty() {
  if (quantity.value > 1) quantity.value--
}
</script>

<style>
/* ======== OVERLAY ======== */
.quickview__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 9998;
  cursor: pointer;
}

/* Overlay transition */
.qv-overlay-enter-active,
.qv-overlay-leave-active {
  transition: opacity 0.35s ease;
}
.qv-overlay-enter-from,
.qv-overlay-leave-to {
  opacity: 0;
}

/* ======== CONTENT PANEL ======== */
.quickview__content {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  pointer-events: none;
}

.quickview__content > * {
  pointer-events: auto;
}

/* Content transition — scale + fade from center */
.qv-content-enter-active {
  transition: opacity 0.35s ease, transform 0.4s cubic-bezier(0.22, 0.61, 0.36, 1);
}
.qv-content-leave-active {
  transition: opacity 0.25s ease, transform 0.3s ease-in;
}
.qv-content-enter-from {
  opacity: 0;
  transform: scale(0.92);
}
.qv-content-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* ======== CLOSE BUTTON ======== */
.quickview__btn-close {
  position: absolute;
  top: 1.25rem;
  inset-inline-end: 1.25rem; /* RTL-aware: sits at the end edge (right in LTR, left in RTL) */
  z-index: 10;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  pointer-events: auto;
}
.quickview__btn-close:hover {
  background: #f3f4f6;
  color: #111827;
}

/* ======== QUICKVIEW CARD ======== */
.product-quickview {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  max-width: 900px;
  width: 100%;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
  position: relative;
}

.product-quickview__row {
  display: flex;
  min-height: 450px;
}

/* Left: Image column */
.product-quickview__image-col {
  flex: 0 0 45%;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: qv-slide-left 0.45s cubic-bezier(0.22, 0.61, 0.36, 1) both;
  animation-delay: 0.1s;
}

@keyframes qv-slide-left {
  from {
    opacity: 0;
    transform: translateX(-40px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.product-quickview__images {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  /* padding: 1.5rem; */
  min-height: 0;
}

.product-quickview__img {
  width: 100%;
  height: 100%;
  max-height: 380px;
  object-fit: contain;
}

/* Thumbnails strip */
.product-quickview__thumbs {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  overflow-x: auto;
  border-top: 1px solid #e5e7eb;
  background: #fff;
}
.product-quickview__thumb {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  background: #fff;
  padding: 3px;
  transition: border-color 0.2s;
}
.product-quickview__thumb.active {
  border-color: var(--color-primary, #858585);
}
.product-quickview__thumb:hover {
  border-color: #9ca3af;
}
.product-quickview__thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* Right: Info column */
.product-quickview__info-col {
  flex: 1;
  padding: 2rem 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  animation: qv-slide-right 0.45s cubic-bezier(0.22, 0.61, 0.36, 1) both;
  animation-delay: 0.2s;
}

@keyframes qv-slide-right {
  from {
    opacity: 0;
    transform: translateX(40px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.product-quickview__details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Title + actions row */
.quickview__title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.quickview-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
  padding-top: 0.125rem;
}

.quickview-actions__btn {
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
  padding: 0;
}
.quickview-actions__btn:hover {
  background: #808080;
  color: #111827;
  border-color: #808080;
}

/* Title */
.product-quickview__title {
  text-decoration: none;
  color: #111827;
  transition: color 0.2s;
  flex: 1;
}
.product-quickview__title:hover {
  color: var(--primary, #858585);
}
.product-quickview__title h2 {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.4;
  margin: 0;
}

/* Subtitle */
.product-quickview__subtitle {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0 0 0.75rem;
  font-weight: 400;
}

/* Price */
.product-quickview__price {
  margin-bottom: 1rem;
}
.price-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.price-sale {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.sale-price {
  font-size: 1.125rem;
  font-weight: 700;
  color: #ef4444;
  margin: 0;
}
.regular-price {
  font-size: 1rem;
  color: #9ca3af;
  text-decoration: line-through;
}
.total-price {
  font-size: 1.125rem;
  font-weight: 700;
  color: #374151;
  margin: 0;
}

/* Stats */
.quickview__stats {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}
.inventory-content {
  flex-shrink: 0;
}

/* Animated checkmark badge */
.qv-stock-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #22c55e;
}
.qv-check-poly {
  stroke-dasharray: 30;
  stroke-dashoffset: 30;
  animation: qvCheckLoop 2s ease-in-out infinite;
}
@keyframes qvCheckLoop {
  0%   { stroke-dashoffset: 30; opacity: 1; }
  30%  { stroke-dashoffset: 0;  opacity: 1; }
  70%  { stroke-dashoffset: 0;  opacity: 1; }
  90%  { stroke-dashoffset: 0;  opacity: 0; }
  100% { stroke-dashoffset: 30; opacity: 0; }
}
.qv-check-svg {
  animation: qvCheckGlow 0.9s ease-in-out infinite alternate;
}
@keyframes qvCheckGlow {
  from { filter: drop-shadow(0 0 2px #22c55e66); }
  to   { filter: drop-shadow(0 0 9px #22c55eff); }
}

/* Category tag */
.qv-category-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary, #858585);
  background: color-mix(in srgb, var(--color-primary, #858585) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-primary, #858585) 25%, transparent);
  border-radius: 20px;
  padding: 0.2rem 0.65rem;
  margin-bottom: 0.625rem;
  text-transform: capitalize;
}

/* Out of Stock badge */
.qv-out-of-stock-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #ef4444;
  letter-spacing: 0.01em;
}
.qv-oos-svg {
  animation: oosGlow 0.7s ease-in-out infinite alternate, oosPulse 1.4s ease-in-out infinite;
  flex-shrink: 0;
}
@keyframes oosGlow {
  from { filter: drop-shadow(0 0 2px #ef444466); }
  to   { filter: drop-shadow(0 0 8px #ef4444cc); }
}
@keyframes oosPulse {
  0%, 100% { transform: scale(1); }
  15%       { transform: scale(1.25) rotate(-8deg); }
  30%       { transform: scale(1) rotate(0deg); }
}
.qv-out-of-stock-badge span {
  animation: oosTextPulse 1.4s ease-in-out infinite;
}
@keyframes oosTextPulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.55; }
}

/* Sold count */
.sold-count {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #ea580c;
  font-size: 0.875rem;
  font-weight: 600;
}
/* Animated flame */
.qv-flame-svg {
  flex-shrink: 0;
  transform-origin: center bottom;
  animation: qvFlamePulse 0.4s ease-in-out infinite alternate;
}
@keyframes qvFlamePulse {
  0%   { transform: scaleY(1)    scaleX(1)    rotate(-3deg); filter: drop-shadow(0 0 5px #f97316bb); }
  100% { transform: scaleY(1.1)  scaleX(0.94) rotate(-1deg); filter: drop-shadow(0 0 10px #fb923cdd); }
}
.qv-flame-path {
  animation: qvFlameColor 0.35s ease-in-out infinite alternate;
}
@keyframes qvFlameColor {
  from { fill: #f97316; }
  to   { fill: #dc2626; }
}

/* Description */
.quickview-description {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}
.quickview-description p {
  margin: 0 0 0.5rem;
}
.link--primary {
  color: var(--primary, #858585);
  font-weight: 600;
  text-decoration: none;
  font-size: 0.8125rem;
  transition: opacity 0.2s;
}
.link--primary:hover {
  opacity: 0.8;
}

/* Attribute Select Boxes */
.quickview__attributes {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  margin-bottom: 1.25rem;
}
.quickview__attr-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}
.quickview__attr-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.quickview__attr-label {
  font-size: 0.875rem;
  font-weight: 700;
  color: #111827;
}
.quickview__attr-req {
  color: #ef4444;
}
.quickview__attr-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #111827;
  background: #fff;
  outline: none;
  cursor: pointer;
  appearance: auto;
  transition: border-color 0.2s;
}
.quickview__attr-select:focus {
  border-color: var(--color-primary, #858585);
}

/* Spinner */
.qv-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid #e5e7eb;
  border-top-color: #111827;
  border-radius: 50%;
  animation: qv-spin 0.6s linear infinite;
}
@keyframes qv-spin {
  to { transform: rotate(360deg); }
}

/* Cart Row */
.quickview__cart-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: auto;
  padding-top: 1rem;
}

.quickview__add-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: 1.5px solid #e5e7eb;
  background: transparent;
  color: #111827;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
}
.quickview__add-btn:hover {
  background: #808080;
  border-color: #808080;
  color: #111827;
}

/* Quantity */
.quickview__quantity {
  display: flex;
  align-items: center;
  border: 1.5px solid #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}
.quickview__qty-btn {
  width: 36px;
  height: 38px;
  border: none;
  background: transparent;
  font-size: 1.125rem;
  cursor: pointer;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.quickview__qty-btn:hover {
  background: #f3f4f6;
}
.quickview__qty-btn:disabled {
  opacity: 0.4;
  cursor: default;
}
.quickview__qty-input {
  width: 40px;
  text-align: center;
  border: none;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
  outline: none;
  --moz-appearance: textfield;
  height: 38px;
}
.quickview__qty-input::-webkit-outer-spin-button,
.quickview__qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Body scroll lock */
html.quickview-opened {
  overflow: hidden;
}

/* ======== RESPONSIVE ======== */
/* OOS row */
.quickview__cart-row--oos {
  gap: 0.75rem;
}
.qv-oos-pill {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 1.5px solid #e5e7eb;
  background: #f9fafb;
  color: #9ca3af;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: not-allowed;
  user-select: none;
}
.qv-contact-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #25d366;
  color: #fff;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s, transform 0.15s;
}
.qv-contact-btn:hover {
  background: #1ebe5c;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .quickview__content {
    padding: 0;
    align-items: flex-end;
  }

  .product-quickview {
    max-height: 92vh;
    border-radius: 16px 16px 0 0;
    width: 100%;
    max-width: 100%;
  }

  /* Close button: always top-end corner on mobile */
  .quickview__btn-close {
    top: 0.875rem;
    inset-inline-end: 0.875rem;
  }

  .product-quickview__row {
    flex-direction: column;
    min-height: auto;
  }

  .product-quickview__image-col {
    flex: none;
    height: 260px;
    animation-name: qv-slide-up;
  }

  @keyframes qv-slide-up {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .product-quickview__info-col {
    padding: 1rem 1rem 1.25rem;
    animation-name: qv-slide-up;
    animation-delay: 0.15s;
  }

  /* Keep brand + actions on same row, actions at end side */
  .quickview__header {
    margin-bottom: 0.5rem;
  }

  .product-quickview__title h2 {
    font-size: 1.0625rem;
  }

  .quickview__cart-row {
    flex-direction: column;
    gap: 0.625rem;
  }

  .quickview__add-btn {
    width: 100%;
    padding: 0.75rem 1rem;
  }

  .quickview__quantity {
    width: 100%;
    justify-content: center;
  }

  .quickview__qty-input {
    flex: 1;
  }

  /* Description – tighten spacing */
  .quickview-description {
    margin-bottom: 1rem;
  }
}
</style>
