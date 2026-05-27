<template>
  <div class="cart-page">
    <!-- Breadcrumb -->
    <nav class="container cart-breadcrumbs">
      <ol class="breadcrumb-list">
        <li><router-link to="/">{{ $t('breadcrumb.home') }}</router-link></li>
        <li class="breadcrumb-sep"><svg width="14" height="14" viewBox="0 0 32 32"><path d="M11.438 22.479l6.125-6.125-6.125-6.125 1.875-1.875 8 8-8 8z" fill="currentColor"/></svg></li>
        <li class="breadcrumb-current">{{ $t('cart.shoppingCart') }}</li>
      </ol>
    </nav>

    <div class="container">
      <!-- Empty Cart -->
      <div v-if="cart.items.length === 0" class="cart-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
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
              <div class="cart-item__top-row">
                <div class="cart-item__info">
                  <h3 class="cart-item__name">{{ item.productName }}</h3>
                  <p v-if="item.variantName" class="cart-item__variant">{{ item.variantName }}</p>
                  <p class="cart-item__unit-price">{{ formatPrice(item.unitPrice) }}</p>
                </div>

                <!-- Quantity + Total + Remove -->
                <div class="cart-item__controls">
                  <div class="cart-item__qty">
                    <button class="cart-qty-btn" @click="cart.updateQuantity(item.id, item.quantity + 1)" aria-label="Increase">+</button>
                    <span class="cart-qty-value">{{ item.quantity }}</span>
                    <button class="cart-qty-btn" @click="cart.updateQuantity(item.id, item.quantity - 1)" aria-label="Decrease">−</button>
                  </div>
                  <div class="cart-item__total">
                    <span class="cart-item__total-label">{{ $t('cart.total') }}</span>
                    <span class="cart-item__total-price">{{ formatPrice(item.lineTotal) }}</span>
                  </div>
                </div>

                <!-- Remove Button -->
                <button class="cart-item__remove" @click="cart.removeItem(item.id)" aria-label="Remove item">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>

              <!-- Product Attributes -->
              <div v-if="item.attributes && item.attributes.length > 0" class="cart-item__options-row">
                <div v-for="attr in item.attributes" :key="attr.id" class="cart-item__option">
                  <span class="cart-item__option-label">{{ attr.name }}:</span>
                  <span class="cart-item__option-value">{{ getSelectedValueName(attr, item.selectedAttributeValues) }}</span>
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
                placeholder="Coupon code"
                class="cart-summary__coupon-input"
              />
              <button class="cart-summary__coupon-btn" @click="applyCoupon">{{ $t('cart.apply') }}</button>
            </div>
          </div>

          <!-- Divider -->
          <div class="cart-summary__divider"></div>

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
import { ref, onMounted } from 'vue'
import { useCartStore } from '@/stores/cartStore'
import { fetchFeaturedProducts } from '@/api/services'
import ProductCard from '@/components/home/ProductCard.vue'

const cart = useCartStore()
const couponInput = ref('')

function formatPrice(price: any): string {
  // Support API PriceValue objects
  if (price && typeof price === 'object' && price.formatted) {
    return price.formatted
  }
  return `${Number(price || 0).toFixed(0)} SAR`
}

function getSelectedValueName(attr: any, selectedValues?: number[]): string {
  if (!attr?.values?.length) return '-'
  if (!selectedValues?.length) {
    // If no specific selection stored, show first value
    return attr.values[0]?.value || '-'
  }
  // Find the value whose id is in the selected array
  const selected = attr.values.find((v: any) => selectedValues.includes(v.id))
  return selected?.value || attr.values[0]?.value || '-'
}

async function applyCoupon() {
  if (couponInput.value.trim()) {
    const result = await cart.applyCoupon(couponInput.value.trim())
    if (!result.success) {
      alert(result.message)
    }
  }
}

// ─── Related Products ───
const relatedTrackRef = ref<HTMLElement | null>(null)
function scrollRelated(dir: number) {
  if (!relatedTrackRef.value) return
  relatedTrackRef.value.scrollBy({ left: dir * 300, behavior: 'smooth' })
}

const relatedProducts = ref<any[]>([])

onMounted(async () => {
  // Load related/suggested products
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
</script>

<style scoped>
/* ─── Page ─── */
.cart-page {
  background: var(--bg-primary, #fff);
  padding-bottom: 3rem;
  min-height: 60vh;
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
.cart-breadcrumbs {
  padding: 0.75rem 0;
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
}
.breadcrumb-list a {
  color: #6b7280;
  text-decoration: none;
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
  font-weight: 500;
}

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
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 1.5rem 0 0.5rem;
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
.cart-empty__btn:hover {
  opacity: 0.9;
}

/* ─── Cart Layout ─── */
.cart-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}
@media (min-width: 768px) {
  .cart-layout {
    grid-template-columns: 1fr 360px;
    gap: 2rem;
  }
}

/* ─── Cart Items ─── */
.cart-items {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.cart-item {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  margin-bottom: 1rem;
  position: relative;
  background: #fff;
}
.cart-item__image {
  width: 72px;
  height: 72px;
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
.cart-item__details {
  flex: 1;
  min-width: 0;
}
.cart-item__top-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}
.cart-item__info {
  flex: 1;
  min-width: 0;
}
.cart-item__name {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 0.125rem;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.cart-item__variant {
  font-size: 0.75rem;
  color: #9ca3af;
  margin: 0 0 0.25rem;
}
.cart-item__unit-price {
  font-size: 0.8125rem;
  color: var(--color-primary, #858585);
  font-weight: 600;
  margin: 0;
}

/* Quantity */
.cart-item__controls {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-shrink: 0;
}
.cart-item__qty {
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.cart-qty-btn {
  width: 32px;
  height: 32px;
  background: transparent;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  color: var(--store-text-primary, #111827);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.cart-qty-btn:hover {
  background: #f3f4f6;
}
.cart-qty-value {
  width: 32px;
  text-align: center;
  font-size: 0.875rem;
  font-weight: 600;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  line-height: 32px;
  color: var(--store-text-primary, #111827);
}

/* Total */
.cart-item__total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  white-space: nowrap;
}
.cart-item__total-label {
  font-size: 0.6875rem;
  color: #9ca3af;
  text-transform: uppercase;
}
.cart-item__total-price {
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}

/* Remove */
.cart-item__remove {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  background: #ef4444;
  color: #fff;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.cart-item__remove:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* Options Row */
.cart-item__options-row {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f3f4f6;
}
.cart-item__option {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.cart-item__option-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  white-space: nowrap;
}
.cart-item__option-label .required {
  color: #ef4444;
}
.cart-item__option-value {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--store-text-primary, #111827);
  padding: 0.125rem 0.5rem;
  background: #f3f4f6;
  border-radius: 4px;
}
.cart-item__option-select {
  flex: 1;
  padding: 0.375rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
  outline: none;
  cursor: pointer;
}

/* ─── Order Summary ─── */
.cart-summary {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  background: #fff;
  height: fit-content;
  position: sticky;
  top: 5rem;
}
.cart-summary__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  margin: 0 0 1.25rem;
}
.cart-summary__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  color: var(--store-text-primary, #111827);
  margin-bottom: 1rem;
}
.cart-summary__amount {
  font-weight: 600;
}
.cart-summary__row--total {
  font-weight: 700;
  font-size: 1rem;
}
.cart-summary__total-amount {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
}

/* Coupon */
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
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.8125rem;
  outline: none;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
}
.cart-summary__coupon-input:focus {
  border-color: var(--color-primary, #858585);
}
.cart-summary__coupon-btn {
  padding: 0.5rem 1.25rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  white-space: nowrap;
}
.cart-summary__coupon-btn:hover {
  opacity: 0.9;
}

/* Divider */
.cart-summary__divider {
  height: 1px;
  background: #e5e7eb;
  margin: 1rem 0;
}

/* Submit */
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
  margin-bottom: 1.25rem;
}
.cart-related__title {
  font-size: 1.25rem;
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
.cart-arrow-btn:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}
.cart-related__track {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
  -ms-overflow-style: none;
  padding-bottom: 0.5rem;
}
.cart-related__track::-webkit-scrollbar {
  display: none;
}
.cart-related__item {
  flex: 0 0 200px;
  scroll-snap-align: start;
}
@media (min-width: 768px) {
  .cart-related__item {
    flex: 0 0 calc(20% - 0.8rem);
    min-width: 180px;
  }
}
</style>
