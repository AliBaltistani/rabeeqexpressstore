<template>
  <div class="product-card">
    <!-- Image Area -->
    <div class="product-card__image-wrap">
      <router-link :to="'/product/' + product.slug" class="product-card__image-link">
        <!-- Tag/Label Badge -->
        <span v-if="product.subtitle" class="product-card__tag">{{ product.subtitle }}</span>
        <img
          :src="product.image"
          :alt="product.name"
          class="product-card__image"
          loading="lazy"
        />
      </router-link>
    </div>

    <!-- Action Icons (heart + eye) centered below image -->
    <div class="product-card__icons">
      <button class="product-card__icon-btn" aria-label="Add to wishlist" @click.prevent="toggleWishlist">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </button>
      <button class="product-card__icon-btn" aria-label="Quick view" @click.prevent="$emit('quickView', product)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
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
      <button class="product-card__add-btn" @click.prevent="addToCart">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span>Add to cart</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

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

const props = defineProps<{
  product: Product
}>()

defineEmits(['quickView'])

function formatPrice(price: number): string {
  const cur = props.product.currency || 'SAR'
  return `${price.toFixed(0)} ${cur}`
}

function addToCart() {
  console.log('Add to cart:', props.product.id)
}

function toggleWishlist() {
  console.log('Toggle wishlist:', props.product.id)
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
.product-card__add-btn svg {
  flex-shrink: 0;
}
</style>
