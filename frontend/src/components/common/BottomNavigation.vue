<template>
  <div class="bottom-nav">
    <router-link to="/" class="bottom-nav__item" :class="{ 'bottom-nav__item--active': $route.name === 'home' }">
      <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      <span>{{ $t('nav.home') }}</span>
    </router-link>
    <router-link to="/products" class="bottom-nav__item" :class="{ 'bottom-nav__item--active': $route.name === 'shop' }">
      <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>{{ $t('nav.categories') }}</span>
    </router-link>
    <router-link to="/cart" class="bottom-nav__item" :class="{ 'bottom-nav__item--active': $route.name === 'cart' }">
      <div style="position: relative;">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
      </div>
      <span>{{ $t('nav.cart') }}</span>
    </router-link>
    <!-- Account: open LoginModal if not authenticated -->
    <router-link v-if="auth.isAuthenticated" to="/account" class="bottom-nav__item" :class="{ 'bottom-nav__item--active': $route.name === 'account' }">
      <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <span>{{ $t('nav.myAccount') }}</span>
    </router-link>
    <button v-else class="bottom-nav__item" @click="showLoginModal = true">
      <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <span>{{ $t('nav.myAccount') }}</span>
    </button>

    <!-- Login Modal -->
    <LoginModal v-model:visible="showLoginModal" @authenticated="handleAuthenticated" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSettingsStore } from '@/stores/settingsStore'
import { useCartStore } from '@/stores/cartStore'
import { useAuthStore } from '@/stores/authStore'
import LoginModal from '@/components/common/LoginModal.vue'

const router = useRouter()
const settings = useSettingsStore()
const cart = useCartStore()
const auth = useAuthStore()

const cartCount = computed(() => cart.itemCount)
const showLoginModal = ref(false)

function handleAuthenticated() {
  router.push('/account')
}
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 200;
  background: var(--bottom-nav-bg);
  color: var(--bottom-nav-text-color);
  border-top: 1px solid var(--product-border-color);
  padding: 0.375rem 0;
  display: none;
  box-shadow: 0 -2px 10px rgb(0 0 0 / 0.05);
}
@media (max-width: 1023px) { .bottom-nav { display: flex; } }

.bottom-nav__item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.125rem;
  font-size: 0.625rem;
  padding: 0.25rem;
  color: inherit;
  text-decoration: none;
  transition: color 0.15s ease;
  background: none;
  border: none;
  cursor: pointer;
}
.bottom-nav__item--active,
.bottom-nav__item:hover { color: var(--color-primary); }
.bottom-nav__item .icon { font-size: 1.25rem; }

.cart-badge {
  position: absolute;
  top: -4px;
  right: -8px;
  background: var(--color-primary);
  color: #fff;
  font-size: 0.5625rem;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
</style>

