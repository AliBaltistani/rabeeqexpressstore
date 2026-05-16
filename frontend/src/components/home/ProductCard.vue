<template>
  <div class="product-card">
    <!-- Image Area -->
    <div class="product-card__image-wrap">
      <router-link :to="'/product/' + product.slug" class="product-card__image-link">
        <!-- Tag/Label Badge -->
        <span v-if="product.subtitle" class="product-card__tag">{{ product.subtitle }}</span>
        <img
          ref="productImgRef"
          :src="product.primaryImage || product.image || '/storage/dummy/placeholder.jpg'"
          :alt="product.name"
          class="product-card__image"
          loading="lazy"
        />
      </router-link>
    </div>

    <!-- Action Icons (heart + eye) centered below image -->
    <div class="product-card__icons">
      <button class="product-card__icon-btn" aria-label="Add to wishlist" @click.prevent="toggleWishlist" :disabled="togglingWishlist">
        <svg v-if="!togglingWishlist" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <span v-else class="btn-spinner"></span>
      </button>
      <button class="product-card__icon-btn" aria-label="Quick view" @click.prevent="openQuickView" :disabled="openingQuickView">
        <svg v-if="!openingQuickView" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        <span v-else class="btn-spinner"></span>
      </button>
    </div>

    <!-- Product Info -->
    <div class="product-card__info">
      <router-link :to="'/product/' + product.slug" class="product-card__name">
        {{ product.name }}
      </router-link>
      <p v-if="product.subtitle" class="product-card__subtitle">{{ product.subtitle }}</p>
      <div class="product-card__price-row">
        <span class="product-card__price">{{ formatPrice(product.price) }}</span>
        <span v-if="product.oldPrice" class="product-card__old-price">{{ formatPrice(product.oldPrice) }}</span>
      </div>
    </div>

    <!-- Add to Cart Button -->
    <div class="product-card__footer">
      <button class="product-card__add-btn" @click.prevent="addToCart" :disabled="addingToCart">
        <span v-if="addingToCart" class="btn-spinner"></span>
        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span>{{ $t('product.addToCart') }}</span>
      </button>
    </div>

    <!-- Added to Cart Toast -->
    <Teleport to="body">
      <transition name="toast-slide">
        <div v-if="showCartToast" class="cart-toast" @click="showCartToast = false">
          <div class="cart-toast__inner" @click.stop>
            <button class="cart-toast__close" @click="showCartToast = false" aria-label="Close">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="cart-toast__header">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="cart-toast__title">{{ $t('cart.addedToCart') || 'Added to Cart' }}</span>
            </div>
            <div class="cart-toast__product">
              <img :src="product.primaryImage || product.image || '/storage/dummy/placeholder.jpg'" :alt="product.name" class="cart-toast__img" />
              <div class="cart-toast__info">
                <span class="cart-toast__name">{{ product.name }}</span>
                <span class="cart-toast__price">{{ formatPrice(product.price) }}</span>
              </div>
            </div>
            <div class="cart-toast__actions">
              <router-link to="/checkout" class="cart-toast__btn cart-toast__btn--primary" @click="showCartToast = false">{{ $t('cart.submitOrder') || 'Submit Order' }}</router-link>
              <router-link to="/cart" class="cart-toast__btn cart-toast__btn--secondary" @click="showCartToast = false">{{ $t('cart.viewCart') || 'View Cart' }}</router-link>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useQuickView } from '@/composables/useQuickView'
import { useCartStore } from '@/stores/cartStore'
import { useWishlistStore } from '@/stores/wishlistStore'
import type { Product } from '@/types'

const props = defineProps<{
  product: any
}>()

const { open: openQuickView_ } = useQuickView()
const cart = useCartStore()
const wishlist = useWishlistStore()

const productImgRef = ref<HTMLImageElement | null>(null)
const addingToCart = ref(false)
const togglingWishlist = ref(false)
const openingQuickView = ref(false)
const showCartToast = ref(false)
let toastTimer: ReturnType<typeof setTimeout> | null = null

function openQuickView() {
  openingQuickView.value = true
  openQuickView_(props.product)
  setTimeout(() => { openingQuickView.value = false }, 400)
}

function formatPrice(price: any): string {
  // Support both API PriceValue objects and raw numbers
  if (price && typeof price === 'object' && price.formatted) {
    return price.formatted
  }
  const cur = props.product.currency || 'SAR'
  return `${Number(price).toFixed(0)} ${cur}`
}

async function addToCart() {
  addingToCart.value = true
  try {
    // Fly-to-cart animation
    flyToCart()
    await cart.addItem(props.product.id, 1)
    // Show toast
    if (toastTimer) clearTimeout(toastTimer)
    showCartToast.value = true
    toastTimer = setTimeout(() => { showCartToast.value = false }, 5000)
  } catch (e) {
    console.error('Add to cart failed:', e)
  } finally {
    addingToCart.value = false
  }
}

function flyToCart() {
  const imgEl = productImgRef.value
  if (!imgEl) return
  // Find the cart icon in the header
  const cartIcon = document.querySelector('[aria-label="Cart"]') as HTMLElement
  if (!cartIcon) return

  const imgRect = imgEl.getBoundingClientRect()
  const cartRect = cartIcon.getBoundingClientRect()

  // Create flying clone
  const clone = imgEl.cloneNode(true) as HTMLImageElement
  clone.style.cssText = `
    position: fixed;
    top: ${imgRect.top}px;
    left: ${imgRect.left}px;
    width: ${imgRect.width}px;
    height: ${imgRect.height}px;
    object-fit: contain;
    z-index: 10000;
    pointer-events: none;
    border-radius: 8px;
    transition: all 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  `
  document.body.appendChild(clone)

  // Force reflow then animate
  clone.getBoundingClientRect()
  requestAnimationFrame(() => {
    clone.style.top = `${cartRect.top + cartRect.height / 2 - 10}px`
    clone.style.left = `${cartRect.left + cartRect.width / 2 - 10}px`
    clone.style.width = '20px'
    clone.style.height = '20px'
    clone.style.opacity = '0.3'
    clone.style.transform = 'scale(0.2)'
  })

  setTimeout(() => {
    clone.remove()
  }, 700)
}

async function toggleWishlist() {
  togglingWishlist.value = true
  try {
    await wishlist.toggleItem(props.product.id)
  } catch (e) {
    console.error('Toggle wishlist failed:', e)
  } finally {
    togglingWishlist.value = false
  }
}
</script>

<style scoped>
.product-card {
  background: #FFFFFF;
  border: 2px solid #707070;
  border-radius: 20px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
  transition: box-shadow 0.3s ease;
}
.product-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* ---- Image Area ---- */
.product-card__image-wrap {
  position: relative;
  background: #FFFFFF;
  overflow: hidden;
}
.product-card__image-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  aspect-ratio: 1 / 1;
  position: relative;
}
.product-card__tag {
  position: absolute;
  top: 10px;
  left: 10px;
  background: #ef4444;
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 4px;
  z-index: 2;
  line-height: 1.3;
  white-space: nowrap;
}
.product-card__image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.4s ease;
}
.product-card:hover .product-card__image {
  transform: scale(1.05);
}

/* ---- Action Icons ---- */
.product-card__icons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.625rem 0 0.25rem;
}
.product-card__icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: transparent;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9ca3af;
  transition: all 0.2s ease;
  padding: 0;
  position: relative;
}
.product-card__icon-btn:hover {
  background: #808080;
  color: #111827;
  border-color: #808080;
}
.product-card__icon-btn:disabled {
  cursor: wait;
  opacity: 0.7;
}
.product-card__icon-btn::before {
  content: attr(aria-label);
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: #000;
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease;
  z-index: 10;
}
.product-card__icon-btn::after {
  content: '';
  position: absolute;
  bottom: calc(100% + 4px);
  left: 50%;
  transform: translateX(-50%);
  border-width: 4px 4px 0;
  border-style: solid;
  border-color: #000 transparent transparent transparent;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease;
  z-index: 10;
}
.product-card__icon-btn:hover::before,
.product-card__icon-btn:hover::after {
  opacity: 1;
  visibility: visible;
}

/* ---- Product Info ---- */
.product-card__info {
  padding: 0.25rem 0.875rem 0;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.product-card__name {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  text-decoration: none;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 0.25rem;
  transition: color 0.2s;
}
.product-card__name:hover {
  color: var(--primary, #858585);
}
.product-card__subtitle {
  font-size: 0.75rem;
  color: #9ca3af;
  margin: 0 0 0.375rem;
  line-height: 1.3;
}
.product-card__price-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: auto;
  padding: 0.25rem 0 0.375rem;
}
.product-card__price {
  font-size: 1rem;
  font-weight: 700;
  color: var(--primary, #ef4444);
}
.product-card__old-price {
  font-size: 0.8125rem;
  color: #9ca3af;
  text-decoration: line-through;
}

/* ---- Add to Cart ---- */
.product-card__footer {
  padding: 0 0.75rem 0.75rem;
}
.product-card__add-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1.5px solid #e5e7eb;
  background: transparent;
  color: var(--store-text-primary, #111827);
  border-radius: 4px;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s ease;
}
.product-card__add-btn:hover {
  background: #808080;
  border-color: #808080;
  color: #111827;
}
.product-card__add-btn:disabled {
  cursor: wait;
  opacity: 0.7;
}
.product-card__add-btn svg {
  flex-shrink: 0;
}

/* ---- Spinner ---- */
.btn-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: btn-spin 0.6s linear infinite;
  flex-shrink: 0;
}
@keyframes btn-spin {
  to { transform: rotate(360deg); }
}
</style>

<!-- Cart Toast (unscoped for Teleport) -->
<style>
/* ─── Added to Cart Toast ─── */
.cart-toast {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10001;
  display: flex;
  justify-content: center;
  padding: 1rem;
  pointer-events: none;
}
.cart-toast__inner {
  pointer-events: auto;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0,0,0,0.05);
  padding: 1rem 1.25rem;
  width: 100%;
  max-width: 380px;
  position: relative;
}
.cart-toast__close {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s;
}
.cart-toast__close:hover { color: #111827; }
.cart-toast__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.cart-toast__title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #22c55e;
}
.cart-toast__product {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0;
  border-top: 1px solid #f3f4f6;
  border-bottom: 1px solid #f3f4f6;
}
.cart-toast__img {
  width: 48px;
  height: 48px;
  object-fit: contain;
  border-radius: 6px;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.cart-toast__info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}
.cart-toast__name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #111827;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.cart-toast__price {
  font-size: 0.8125rem;
  font-weight: 700;
  color: #ef4444;
}
.cart-toast__actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}
.cart-toast__btn {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
  text-align: center;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}
.cart-toast__btn--primary {
  background: var(--color-primary, #858585);
  color: #fff;
}
.cart-toast__btn--primary:hover { opacity: 0.9; }
.cart-toast__btn--secondary {
  background: #f3f4f6;
  color: #374151;
}
.cart-toast__btn--secondary:hover { background: #e5e7eb; }

/* Toast Animation */
.toast-slide-enter-active { transition: all 0.35s cubic-bezier(0.22, 0.61, 0.36, 1); }
.toast-slide-leave-active { transition: all 0.25s ease-in; }
.toast-slide-enter-from { transform: translateY(-100%); opacity: 0; }
.toast-slide-leave-to { transform: translateY(-100%); opacity: 0; }
</style>
