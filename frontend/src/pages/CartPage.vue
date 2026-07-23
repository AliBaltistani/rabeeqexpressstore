<template>
  <div class="cart-page">
    <!-- Breadcrumb -->
    <nav class="container cart-breadcrumbs">
      <ol class="breadcrumb-list">
        <li><router-link to="/">{{ $t('breadcrumb.home') }}</router-link></li>
        <li class="breadcrumb-sep">
          <svg width="14" height="14" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg>
        </li>
        <li class="breadcrumb-current">{{ $t('cart.shoppingCart') }}</li>
      </ol>
    </nav>

    <div class="container">
      <!-- Empty Cart -->
      <div v-if="cart.items.length === 0" class="cart-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <h2 class="cart-empty__title">{{ $t('cart.emptyTitle') }}</h2>
        <p class="cart-empty__text">{{ $t('cart.emptyText') }}</p>
        <router-link to="/" class="cart-empty__btn">{{ $t('cart.continueShopping') }}</router-link>
      </div>

      <!-- Cart Content -->
      <div v-else class="cart-layout">
        <!-- ====== LEFT: CART ITEMS ====== -->
        <div class="cart-items">
          <div
            v-for="item in cart.items"
            :key="item.id"
            class="cart-item"
          >
            <!-- Product Image -->
            <div class="cart-item__image">
              <img :src="item.image || ''" :alt="item.productName" />
            </div>

            <!-- Product Details -->
            <div class="cart-item__details">
              <!-- Top row: name + remove -->
              <div class="cart-item__header">
                <div class="cart-item__info">
                  <h3 class="cart-item__name">{{ item.productName }}</h3>
                  <p v-if="item.variantName" class="cart-item__variant">{{ item.variantName }}</p>
                  <p class="cart-item__unit-price">{{ formatPrice(item.unitPrice) }}</p>
                </div>
                <!-- Remove Button -->
                <button class="cart-item__remove" @click="cart.removeItem(item.id)" aria-label="Remove item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>

              <!-- Product Attributes — unified interactive editor -->
              <AttributeSelector
                v-if="item.attributes && item.attributes.length > 0"
                :attributes="item.attributes"
                :model-value="itemSelections[item.id] || {}"
                :show-update="true"
                :disabled="updatingItems[item.id]"
                :updating="updatingItems[item.id]"
                @update:model-value="(v) => { itemSelections[item.id] = v }"
                @update="updateItemVariant(item)"
              />

              <!-- Bottom row: Quantity + Line Total -->
              <div class="cart-item__footer">
                <div class="cart-item__qty">
                  <button class="cart-qty-btn" @click="cart.updateQuantity(item.id, item.quantity - 1)" aria-label="Decrease">−</button>
                  <span class="cart-qty-value">{{ item.quantity }}</span>
                  <button class="cart-qty-btn" @click="cart.updateQuantity(item.id, item.quantity + 1)" aria-label="Increase">+</button>
                </div>
                <div class="cart-item__total">
                  <span class="cart-item__total-label">{{ $t('cart.total') }}</span>
                  <span class="cart-item__total-price">{{ formatPrice(item.lineTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ====== RIGHT: ORDER SUMMARY ====== -->
        <aside class="cart-summary">
          <h2 class="cart-summary__title">{{ $t('cart.orderSummary') }}</h2>

          <div class="cart-summary__row">
            <span>{{ $t('cart.totalProductsCost') }}</span>
            <span class="cart-summary__amount">{{ formatPrice(cart.subtotal) }}</span>
          </div>

          <!-- Coupon -->
          <div class="cart-summary__coupon">
            <p class="cart-summary__coupon-label">{{ $t('cart.couponQuestion') }}</p>
            <div class="cart-summary__coupon-row">
              <input
                type="text"
                v-model="couponInput"
                :placeholder="$t('cart.couponPlaceholder') || 'Coupon code'"
                class="cart-summary__coupon-input"
                :class="{
                  'cart-summary__coupon-input--error': couponStatus === 'error',
                  'cart-summary__coupon-input--success': couponStatus === 'success',
                }"
                @keyup.enter="applyCoupon"
              />
              <button
                class="cart-summary__coupon-btn"
                @click="applyCoupon"
                :disabled="couponLoading"
              >
                <span v-if="couponLoading" class="coupon-spinner"></span>
                <span v-else>{{ $t('cart.apply') }}</span>
              </button>
            </div>
            <!-- Inline feedback message -->
            <transition name="coupon-msg">
              <p
                v-if="couponMessage"
                class="cart-summary__coupon-message"
                :class="`cart-summary__coupon-message--${couponStatus}`"
              >
                <svg v-if="couponStatus === 'success'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ couponMessage }}
              </p>
            </transition>
          </div>

          <!-- Divider -->
          <div class="cart-summary__divider"></div>

          <!-- Discount row (shown when coupon applied) -->
          <div v-if="cart.discount && cart.discount.raw > 0" class="cart-summary__row cart-summary__row--discount">
            <span>{{ $t('cart.discount') }}</span>
            <span class="cart-summary__discount-amount">− {{ formatPrice(cart.discount) }}</span>
          </div>

          <!-- Final Total -->
          <div class="cart-summary__row cart-summary__row--total">
            <span>{{ $t('cart.finalTotal') }}</span>
            <span class="cart-summary__total-amount">{{ formatPrice(cart.total) }}</span>
          </div>

          <!-- Submit Order -->
          <router-link to="/checkout" class="cart-summary__submit-btn">{{ $t('cart.submitOrder') }}</router-link>
        </aside>
      </div>

      <!-- ====== RELATED PRODUCTS ====== -->
      <div class="cart-related" v-if="cart.items.length > 0">
        <div class="cart-related__header">
          <h2 class="cart-related__title">{{ $t('cart.addMore') }}</h2>
          <div class="cart-related__arrows">
            <button class="cart-arrow-btn" @click="scrollRelated(-1)" aria-label="Previous">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="cart-arrow-btn" @click="scrollRelated(1)" aria-label="Next">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>
        <div class="cart-related__track" ref="relatedTrackRef">
          <div v-for="rp in relatedProducts" :key="rp.id" class="cart-related__item">
            <ProductCard :product="rp" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { useCartStore } from '@/stores/cartStore'
import { fetchFeaturedProducts } from '@/api/services'
import ProductCard from '@/components/home/ProductCard.vue'
import AttributeSelector from '@/components/product/AttributeSelector.vue'

const cart = useCartStore()

// ── Per-item variant selection state ─────────────────────────────────────────
// itemSelections[cartItemId][attributeGroupId] = selectedValueId
const itemSelections = reactive<Record<number, Record<number, number>>>({})
const updatingItems  = reactive<Record<number, boolean>>({})

function initItemSelections(items: typeof cart.items) {
  for (const item of items) {
    if (!item.attributes?.length) continue
    itemSelections[item.id] = {}
    for (const group of item.attributes) {
      // try to match the cart's selectedAttributeValues into the right group
      const match = group.values?.find(
        (v: any) => item.selectedAttributeValues?.includes(v.id)
      )
      itemSelections[item.id][group.id] = Number(match?.id ?? group.values?.[0]?.id ?? 0)
    }
  }
}

async function updateItemVariant(item: any) {
  if (updatingItems[item.id]) return
  const groupSelections = itemSelections[item.id]
  if (!groupSelections) return
  const attrValues = Object.values(groupSelections).filter(Boolean).map(v => Number(v))
  updatingItems[item.id] = true
  try {
    await cart.updateItemAttributes(item.id, item.productId, item.quantity, attrValues)
    // Re-init selections from newly synced cart items
    initItemSelections(cart.items)
  } catch (e) {
    console.error('Failed to update variant:', e)
  } finally {
    updatingItems[item.id] = false
  }
}

// ── Coupon ──────────────────────────────────────────────────────────────────
const couponInput  = ref('')
const couponMessage = ref('')
const couponStatus  = ref<'error' | 'success' | ''>('')
const couponLoading = ref(false)

async function applyCoupon() {
  const code = couponInput.value.trim()
  if (!code) {
    couponStatus.value  = 'error'
    couponMessage.value = 'Please enter a coupon code.'
    return
  }
  couponLoading.value  = true
  couponMessage.value  = ''
  couponStatus.value   = ''
  try {
    const result = await cart.applyCoupon(code)
    if (result.success) {
      couponStatus.value  = 'success'
      couponMessage.value = result.message || 'Coupon applied successfully!'
    } else {
      couponStatus.value  = 'error'
      couponMessage.value = result.message || 'Invalid coupon code.'
    }
  } catch {
    couponStatus.value  = 'error'
    couponMessage.value = 'Something went wrong. Please try again.'
  } finally {
    couponLoading.value = false
  }
}

// ── Pricing ──────────────────────────────────────────────────────────────────
function formatPrice(price: any): string {
  if (price && typeof price === 'object' && price.formatted) return price.formatted
  return `${Number(price || 0).toFixed(0)} SAR`
}


// ── Related Products ─────────────────────────────────────────────────────────
const relatedTrackRef = ref<HTMLElement | null>(null)

function scrollRelated(dir: number) {
  if (!relatedTrackRef.value) return
  relatedTrackRef.value.scrollBy({ left: dir * 300, behavior: 'smooth' })
}

const relatedProducts = ref<any[]>([])

onMounted(async () => {
  // Always fetch fresh cart data from the API when visiting the cart page.
  // This ensures the page is correct even after a hard refresh or direct navigation.
  try {
    await cart.loadCart()
  } catch {}

  // Init variant selections from the (now fresh) cart items
  initItemSelections(cart.items)

  try {
    const featured = await fetchFeaturedProducts(6)
    relatedProducts.value = featured.map(p => ({
      id: p.id,
      slug: p.slug,
      name: p.name,
      subtitle: p.category?.name || '',
      image: p.primaryImage || '',
      price: p.flashSalePrice?.raw ?? p.price?.raw ?? 0,
      oldPrice: p.comparePrice?.raw || undefined,
      currency: p.currency || 'SAR',
    }))
  } catch {}
})

// Re-init per-item selections whenever cart data changes from the API
watch(() => cart.items, (items) => initItemSelections(items), { deep: true })
</script>

<style scoped>
/* ─── Page ─── */
.cart-page {
  background: var(--bg-primary, #fff);
  padding-bottom: 4rem;
  min-height: 60vh;
}

/* Local container override — tighter on mobile */
.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 0.75rem;
}
@media (min-width: 480px) {
  .container { padding: 0 1.25rem; }
}

/* ─── Breadcrumbs ─── */
.cart-breadcrumbs {
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
}
.breadcrumb-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  color: #6b7280;
  flex-wrap: wrap;
}
.breadcrumb-list a {
  color: #6b7280;
  text-decoration: none;
  transition: color 0.2s;
}
.breadcrumb-list a:hover { color: var(--color-primary, #858585); }
.breadcrumb-sep { display: flex; align-items: center; color: #9ca3af; }
.breadcrumb-current { color: var(--store-text-primary, #111827); font-weight: 500; }

/* ─── Empty Cart ─── */
.cart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  text-align: center;
}
.cart-empty__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 1.25rem 0 0.5rem;
}
.cart-empty__text {
  font-size: 0.9375rem;
  color: #9ca3af;
  margin: 0 0 1.5rem;
}
.cart-empty__btn {
  display: inline-block;
  padding: 0.625rem 2rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 600;
  text-decoration: none;
  transition: opacity 0.2s;
}
.cart-empty__btn:hover { opacity: 0.9; }

/* ─── Cart Layout: mobile=1col, desktop=2col ─── */
.cart-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem;
  margin-bottom: 2.5rem;
  align-items: start;
}
@media (min-width: 768px) {
  .cart-layout {
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
  }
}

/* ─── Cart Items List ─── */
.cart-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* ─── Cart Item Card ─── */
.cart-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.875rem;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  position: relative;
}

/* Product image */
.cart-item__image {
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}
.cart-item__image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* Details column */
.cart-item__details {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Header: name + remove */
.cart-item__header {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}
.cart-item__info { flex: 1; min-width: 0; }
.cart-item__name {
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 0.125rem;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.cart-item__variant {
  font-size: 0.6875rem;
  color: #9ca3af;
  margin: 0 0 0.2rem;
}
.cart-item__unit-price {
  font-size: 0.8125rem;
  color: var(--color-primary, #858585);
  font-weight: 600;
  margin: 0;
}

/* Remove button — inline in header row */
.cart-item__remove {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  background: #fef2f2;
  color: #ef4444;
  border: 1px solid #fecaca;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 1px;
}
.cart-item__remove:hover {
  background: #ef4444;
  color: #fff;
  border-color: #ef4444;
}

/* Footer: qty + line total */
.cart-item__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Quantity control */
.cart-item__qty {
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.cart-qty-btn {
  width: 30px;
  height: 30px;
  background: transparent;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  color: var(--store-text-primary, #111827);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
  flex-shrink: 0;
}
.cart-qty-btn:hover { background: #f3f4f6; }
.cart-qty-value {
  min-width: 28px;
  padding: 0 0.25rem;
  text-align: center;
  font-size: 0.875rem;
  font-weight: 600;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  line-height: 30px;
  color: var(--store-text-primary, #111827);
}

/* Line total */
.cart-item__total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  white-space: nowrap;
}
.cart-item__total-label {
  font-size: 0.625rem;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.cart-item__total-price {
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}



/* ─── Order Summary ─── */
.cart-summary {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  background: #fff;
  /* Sticky only on desktop */
}
@media (min-width: 768px) {
  .cart-summary {
    position: sticky;
    top: 5rem;
  }
}

.cart-summary__title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}
.cart-summary__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
  margin-bottom: 0.75rem;
}
.cart-summary__amount { font-weight: 600; }
.cart-summary__row--total {
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 0;
}
.cart-summary__row--discount { color: #16a34a; }
.cart-summary__total-amount {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-primary, #858585);
}
.cart-summary__discount-amount {
  font-weight: 700;
  color: #16a34a;
}

/* ── Coupon field ── */
.cart-summary__coupon {
  margin-bottom: 1rem;
}
.cart-summary__coupon-label {
  font-size: 0.8125rem;
  color: #6b7280;
  margin: 0 0 0.5rem;
}
.cart-summary__coupon-row {
  display: flex;
  gap: 0.5rem;
}
.cart-summary__coupon-input {
  flex: 1;
  min-width: 0;
  padding: 0.5rem 0.75rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 7px;
  font-size: 0.8125rem;
  outline: none;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
  transition: border-color 0.2s;
}
.cart-summary__coupon-input:focus {
  border-color: var(--color-primary, #858585);
}
.cart-summary__coupon-input--error {
  border-color: #ef4444 !important;
}
.cart-summary__coupon-input--success {
  border-color: #16a34a !important;
}
.cart-summary__coupon-btn {
  padding: 0.5rem 1rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 7px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  white-space: nowrap;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  min-width: 68px;
}
.cart-summary__coupon-btn:hover:not(:disabled) { opacity: 0.9; }
.cart-summary__coupon-btn:disabled { opacity: 0.65; cursor: not-allowed; }

/* Inline feedback */
.cart-summary__coupon-message {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  margin: 0.4rem 0 0;
  padding: 0.375rem 0.625rem;
  border-radius: 6px;
}
.cart-summary__coupon-message--error {
  color: #dc2626;
  background: #fef2f2;
}
.cart-summary__coupon-message--success {
  color: #16a34a;
  background: #f0fdf4;
}

/* Loading spinner inside coupon button */
.coupon-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Coupon message enter/leave transition */
.coupon-msg-enter-active,
.coupon-msg-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.coupon-msg-enter-from,
.coupon-msg-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Divider */
.cart-summary__divider {
  height: 1px;
  background: #e5e7eb;
  margin: 0.875rem 0;
}

/* Submit button */
.cart-summary__submit-btn {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 700;
  text-align: center;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.25s;
  margin-top: 1rem;
}
.cart-summary__submit-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* ─── Related Products ─── */
.cart-related {
  margin-top: 1rem;
  margin-bottom: 2rem;
}
.cart-related__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.cart-related__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0;
}
.cart-related__arrows {
  display: flex;
  gap: 0.5rem;
}
.cart-arrow-btn {
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
.cart-arrow-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
.cart-related__track {
  display: flex;
  gap: 0.875rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
  -ms-overflow-style: none;
  padding-bottom: 0.5rem;
}
.cart-related__track::-webkit-scrollbar { display: none; }
.cart-related__item {
  flex: 0 0 160px;
  scroll-snap-align: start;
}
@media (min-width: 480px) {
  .cart-related__item { flex: 0 0 190px; }
}
@media (min-width: 768px) {
  .cart-related__item {
    flex: 0 0 calc(20% - 0.7rem);
    min-width: 160px;
  }
}
</style>
