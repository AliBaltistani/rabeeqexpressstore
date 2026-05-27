<template>
  <Teleport to="body">
    <transition name="toast-slide">
      <div v-if="isVisible" class="cart-toast" :class="positionClass" @click="hideToast">
        <div class="cart-toast__inner" @click.stop>
          <button class="cart-toast__close" @click="hideToast" aria-label="Close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
          <div class="cart-toast__header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="cart-toast__title">{{ $t('cart.addedToCart') }}</span>
          </div>
          <div class="cart-toast__progress-track">
            <div class="cart-toast__progress-bar" :class="{ 'cart-toast__progress-bar--active': progressActive }"></div>
          </div>
          <div class="cart-toast__product">
            <img :src="toastProduct.image || '/storage/dummy/placeholder.jpg'" :alt="toastProduct.name" class="cart-toast__img" />
            <div class="cart-toast__info">
              <span class="cart-toast__name">{{ toastProduct.name }}</span>
              <span class="cart-toast__price">{{ toastProduct.price }}</span>
            </div>
          </div>
          <div class="cart-toast__actions">
            <router-link to="/checkout" class="cart-toast__btn cart-toast__btn--primary" @click="hideToast">{{ $t('cart.submitOrder') }}</router-link>
            <router-link to="/cart" class="cart-toast__btn cart-toast__btn--secondary" @click="hideToast">{{ $t('cart.viewCart') }}</router-link>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { useCartToast } from '@/composables/useCartToast'

const { isVisible, progressActive, toastProduct, positionClass, hideToast } = useCartToast()
</script>
