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
      <button class="product-card__icon-btn" :class="{ 'product-card__icon-btn--active': isWishlisted }" aria-label="Add to wishlist" @click.prevent="toggleWishlist" :disabled="togglingWishlist">
        <svg v-if="!togglingWishlist" width="20" height="20" viewBox="0 0 24 24" :fill="isWishlisted ? '#ef4444' : 'none'" :stroke="isWishlisted ? '#ef4444' : 'currentColor'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
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

    <!-- Add to Cart (in stock) -->
    <div class="product-card__footer">
      <template v-if="product.inStock !== false">
        <button class="product-card__add-btn" @click.prevent="addToCart" :disabled="addingToCart">
          <span v-if="addingToCart" class="btn-spinner"></span>
          <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <span>{{ $t('product.addToCart') }}</span>
        </button>
      </template>
      <!-- Out of Stock actions -->
      <template v-else>
        <div class="product-card__footer-oos">
          <div class="product-card__oos-pill">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            {{ $t('product.outOfStock') }}
          </div>
          <a
            v-if="settingsStore.storeSettings.whatsappNumber"
            :href="'https://wa.me/' + settingsStore.storeSettings.whatsappNumber.replace('+', '') + '?text=' + encodeURIComponent($t('product.whatsappMsg', { name: product.name }))"
            target="_blank"
            rel="noopener noreferrer"
            class="product-card__contact-btn"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            {{ $t('footer.contactUs') }}
          </a>
          <a
            v-else
            :href="'mailto:' + (settingsStore.storeSettings.email || '') + '?subject=' + encodeURIComponent($t('product.whatsappMsg', { name: product.name }))"
            class="product-card__contact-btn"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0 1.1.9 2 2 2z"/><polyline points="22,6 12,13 2,6"/></svg>
            {{ $t('footer.contactUs') }}
          </a>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuickView } from '@/composables/useQuickView'
import { useCartStore } from '@/stores/cartStore'
import { useWishlistStore } from '@/stores/wishlistStore'
import { flyToCart, pulseElement } from '@/composables/useActionAnimations'
import { useCartToast } from '@/composables/useCartToast'

import { useSettingsStore } from '@/stores/settingsStore'

const props = defineProps<{
  product: any
}>()

const { open: openQuickView_ } = useQuickView()
const cart = useCartStore()
const wishlist = useWishlistStore()
const settingsStore = useSettingsStore()
const { showToast } = useCartToast()

const productImgRef = ref<HTMLImageElement | null>(null)
const addingToCart = ref(false)
const togglingWishlist = ref(false)
const openingQuickView = ref(false)

const isWishlisted = computed(() => wishlist.isInWishlist(props.product.id))

function openQuickView() {
  openingQuickView.value = true
  openQuickView_(props.product)
  setTimeout(() => { openingQuickView.value = false }, 400)
}

function formatPrice(price: any): string {
  if (price && typeof price === 'object' && price.formatted) {
    return price.formatted
  }
  const cur = props.product.currency || 'SAR'
  return `${Number(price).toFixed(0)} ${cur}`
}

async function addToCart() {
  addingToCart.value = true
  try {
    flyToCart(productImgRef.value)
    await cart.addItem(props.product.id, 1)
    showToast({
      name: props.product.name,
      image: props.product.primaryImage || props.product.image || '/storage/dummy/placeholder.jpg',
      price: formatPrice(props.product.price),
    })
  } catch (e) {
    console.error('Add to cart failed:', e)
  } finally {
    addingToCart.value = false
  }
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

/* Out of Stock footer */
.product-card__footer-oos {
  display: flex;
  gap: 0.5rem;
}
.product-card__oos-pill {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5rem 0.5rem;
  border: 1.5px solid #e5e7eb;
  background: #f9fafb;
  color: #9ca3af;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: not-allowed;
  user-select: none;
}
.product-card__contact-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5rem 0.5rem;
  background: #25d366;
  color: #fff;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s;
}
.product-card__contact-btn:hover {
  background: #1ebe5c;
}
</style>
