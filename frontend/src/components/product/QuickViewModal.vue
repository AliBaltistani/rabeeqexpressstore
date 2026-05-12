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
            <!-- Left: Image -->
            <div class="product-quickview__image-col">
              <div class="product-quickview__images">
                <img
                  :src="product.image"
                  :alt="product.name"
                  class="product-quickview__img"
                />
              </div>
            </div>

            <!-- Right: Info -->
            <div class="product-quickview__info-col">
              <div class="product-quickview__details">
                <!-- Header: Brand + Wishlist/Share -->
                <div class="quickview__header">
                  <span v-if="product.subtitle" class="quickview-brand">{{ product.subtitle }}</span>
                  <div class="quickview-actions">
                    <button class="quickview-actions__btn" aria-label="Add to wishlist" @click="toggleWishlist">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                      </svg>
                    </button>
                    <button class="quickview-actions__btn" aria-label="Share product" @click="shareProduct">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Title -->
                <router-link :to="'/product/' + product.slug" class="product-quickview__title" @click="close">
                  <h2>{{ product.name }}</h2>
                </router-link>

                <!-- Subtitle -->
                <h3 v-if="product.subtitle" class="product-quickview__subtitle">{{ product.subtitle }}</h3>

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

                <!-- Stock Status -->
                <div class="quickview__stats">
                  <div class="inventory-content">
                    <div id="variant-inventory" class="in-stock">
                      <i class="stock-dot"></i>
                      <span>In Stock</span>
                    </div>
                  </div>

                  <!-- Sold count -->
                  <div class="sold-count">
                    <span class="sold-count__icon">
                      <svg xmlns="http://www.w3.org/2000/svg" class="fire-icon" version="1.1" x="0" y="0" viewBox="0 0 512.165 512.165" xml:space="preserve">
                        <g>
                          <g>
                            <path d="m437.476 320.656c-.004-.05-.007-.101-.011-.15-1.541-21.368-5.481-39.513-12.39-57.037-.093-.265-.193-.528-.302-.79-.395-.951-39.225-95.684-21.044-147.746 1.641-4.697.845-9.901-2.123-13.894s-7.716-6.249-12.691-6.037c-1.333.058-28.492 1.565-57.3 26.911-25.213-52.021-29.194-107.287-29.235-107.89-.341-5.227-3.383-9.896-8.026-12.32-4.644-2.425-10.213-2.249-14.697.457-39.788 24.034-70.525 53.365-91.356 87.18-16.82 27.302-27.192 57.459-30.829 89.634-2.184 19.327-1.665 37.002-.054 51.85.746 6.879-6.125 12.051-12.529 9.432l-38.613-15.797c-2.632-1.077-5.512-1.525-8.32-1.073-5.912.952-10.518 5.194-12.095 10.682-3.504 12.189-6.128 23.455-8.023 34.441-6.596 38.261-3.897 77.447 7.802 113.325 11.762 36.068 32.059 67.129 58.697 89.824 31.481 26.822 69.604 40.477 111.176 40.477 22.939 0 46.93-4.158 71.431-12.557 38.865-13.321 69.645-39.707 89.011-76.303 16.095-30.419 23.739-66.863 21.521-102.619z" fill="#ff001e" data-original="#ff001e"></path>
                            <path d="m296.687 260.081c-3.792-5.657-10.918-8.056-17.356-5.829-6.438 2.223-10.57 8.503-10.063 15.295 1.789 23.976-.608 94.475-33.954 103.618-28.387 7.787-43.529-5.362-45-6.726-3.681-4.013-9.084-5.533-14.39-4.261-5.342 1.285-9.38 5.573-10.805 10.879-.283 1.053-2.793 10.461-3.851 16.59-6.784 39.356 6.081 78.356 33.576 101.781 16.127 13.74 35.578 20.736 56.725 20.736 11.536 0 23.58-2.084 35.855-6.292 19.779-6.78 36.691-21.121 48.908-41.475 10.219-17.024 16.161-36.889 16.305-54.5.366-45.31-16.889-91.515-55.95-149.816z" fill="#ffeb00" data-original="#ffeb00"></path>
                          </g>
                        </g>
                      </svg>
                    </span>
                    <span>Sold</span>
                    <span>&nbsp;24 times</span>
                  </div>
                </div>

                <!-- Description -->
                <div class="quickview-description">
                  <p>Premium quality product from {{ product.subtitle || 'our store' }}. Experience comfort and style with this exceptional piece.</p>
                  <router-link :to="'/product/' + product.slug" class="link--primary" @click="close">More details</router-link>
                </div>

                <!-- Add to Cart + Quantity -->
                <div class="quickview__cart-row">
                  <button class="quickview__add-btn" @click="addToCart">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <span>Add to cart</span>
                  </button>
                  <div class="quickview__quantity">
                    <button class="quickview__qty-btn" @click="decrementQty" :disabled="quantity <= 1">−</button>
                    <input type="number" v-model.number="quantity" min="1" class="quickview__qty-input" />
                    <button class="quickview__qty-btn" @click="incrementQty">+</button>
                  </div>
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
import { ref } from 'vue'
import { useQuickView } from '@/composables/useQuickView'

const { isOpen, product, close } = useQuickView()

const quantity = ref(1)

function formatPrice(price: number): string {
  const cur = product.value?.currency || 'SAR'
  return `${price.toFixed(0)} ${cur}`
}

function addToCart() {
  console.log('Quick view - Add to cart:', product.value?.id, 'qty:', quantity.value)
}

function toggleWishlist() {
  console.log('Quick view - Toggle wishlist:', product.value?.id)
}

function shareProduct() {
  console.log('Quick view - Share:', product.value?.id)
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
  right: 1.25rem;
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
  align-items: center;
  justify-content: center;
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
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.product-quickview__img {
  width: 100%;
  height: 100%;
  max-height: 420px;
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

/* Header row */
.quickview__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.quickview-brand {
  font-size: 0.8125rem;
  color: #9ca3af;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.quickview-actions {
  display: flex;
  gap: 0.5rem;
  margin-left: auto;
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
}
.product-quickview__title:hover {
  color: var(--primary, #858585);
}
.product-quickview__title h2 {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.4;
  margin: 0 0 0.25rem;
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
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.inventory-content {
  flex-grow: 1;
  flex-shrink: 0;
}
#variant-inventory.in-stock {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #108043;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Pulsing green dot */
.stock-dot {
  position: relative;
  display: inline-block;
  width: 10px;
  height: 10px;
  background: #108043;
  border-radius: 50%;
  flex-shrink: 0;
}
.stock-dot::after {
  animation-duration: 1.5s;
  animation-iteration-count: infinite;
  animation-name: scale;
  animation-timing-function: ease-out;
  background: #108043;
  border-radius: 50%;
  content: "";
  display: inline-block;
  height: 100%;
  left: 0;
  opacity: 0.25;
  position: absolute;
  top: 0;
  transform: scale(1);
  width: 100%;
}
@keyframes scale {
  0% {
    transform: scale(1);
    opacity: 0.25;
  }
  100% {
    transform: scale(2.5);
    opacity: 0;
  }
}

/* Sold count */
.sold-count {
  display: flex;
  align-items: center;
  color: #f11e1e;
  font-size: 0.875rem;
  font-weight: 500;
  flex-grow: 1;
  flex-shrink: 0;
}
.sold-count__icon {
  margin-right: 0.375rem;
  margin-left: 0;
  display: inline-flex;
}
.fire-icon {
  width: 20px;
  height: 20px;
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
  -moz-appearance: textfield;
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
@media (max-width: 768px) {
  .quickview__content {
    padding: 1rem;
    align-items: flex-end;
  }

  .product-quickview {
    max-height: 90vh;
    border-radius: 12px 12px 0 0;
  }

  .product-quickview__row {
    flex-direction: column;
    min-height: auto;
  }

  .product-quickview__image-col {
    flex: none;
    height: 280px;
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
    padding: 1.25rem 1rem;
    animation-name: qv-slide-up;
    animation-delay: 0.15s;
  }

  .quickview__cart-row {
    flex-direction: column;
  }

  .quickview__add-btn {
    width: 100%;
  }

  .quickview__quantity {
    width: 100%;
    justify-content: center;
  }

  .quickview__qty-input {
    flex: 1;
  }
}
</style>
