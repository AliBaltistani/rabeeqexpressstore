<template>
  <div class="main-nav" :class="{ 'is-sticky': isSticky }">
    <div class="main-nav__inner">
      <div class="container">
        <div class="main-nav__row">
          <!-- Mobile hamburger -->
          <button class="hamburger-btn" @click="showMobileMenu = true" aria-label="Open menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
          </button>

          <!-- Logo -->
          <router-link to="/" class="navbar-brand" aria-label="E-SEVEN STORE Home">
            <img :src="logoSrc" alt="E-SEVEN STORE Logo" />
            <h1 class="sr-only">ESEVEN STORE</h1>
          </router-link>

          <!-- Desktop Menu -->
          <nav class="main-menu-wrap">
            <ul class="main-menu">
              <!-- Offers -->
              <li class="root-level">
                <router-link to="/products?offers=true" class="nav-link offers-link" aria-label="Offers">
                  <span>{{ $t('nav.offers') }}</span>
                </router-link>
              </li>

              <!-- Category dropdowns with multi-level -->
              <li
                v-for="cat in menuCategories"
                :key="cat.slug"
                class="root-level"
                :class="{ 'has-children': cat.children && cat.children.length > 0 }"
                @mouseenter="activeDropdown = cat.slug"
                @mouseleave="activeDropdown = null; activeSubmenu = null"
              >
                <router-link :to="'/category/' + cat.slug" class="nav-link" :aria-label="cat.name">
                  <span>{{ cat.name }}</span>
                  <!-- Chevron down for categories with children -->
                  <svg v-if="cat.children && cat.children.length" class="chevron-down" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </router-link>

                <!-- Multi-level dropdown panel -->
                <div
                  v-if="cat.children && cat.children.length"
                  class="mega-dropdown"
                  :class="{ 'mega-dropdown--visible': activeDropdown === cat.slug }"
                >
                  <!-- Left: Parent categories list -->
                  <div class="mega-dropdown__left">
                    <ul>
                      <li
                        v-for="child in cat.children"
                        :key="child.slug"
                        @mouseenter="activeSubmenu = child.slug"
                        :class="{ 'is-active': activeSubmenu === child.slug && child.children && child.children.length }"
                      >
                        <router-link :to="'/category/' + child.slug" class="mega-link">
                          <span>{{ child.name }}</span>
                          <!-- Arrow icon for items with sub-children -->
                          <svg v-if="child.children && child.children.length" class="chevron-right" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </router-link>
                      </li>
                    </ul>
                  </div>

                  <!-- Right: Sub-categories panel (shows on hover) -->
                  <div
                    v-for="child in cat.children"
                    :key="'sub-' + child.slug"
                    class="mega-dropdown__right"
                    :class="{ 'mega-dropdown__right--visible': activeSubmenu === child.slug && child.children && child.children.length }"
                  >
                    <ul v-if="child.children && child.children.length">
                      <li v-for="sub in child.children" :key="sub.slug">
                        <router-link :to="'/category/' + sub.slug" class="mega-link">
                          <span>{{ sub.name }}</span>
                        </router-link>
                      </li>
                    </ul>
                  </div>
                </div>
              </li>
            </ul>
          </nav>

          <!-- Right side: Search, User, Cart -->
          <div class="main-nav__right">
            <button class="action-btn" @click="$emit('open-search')" aria-label="Search">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>
            <router-link to="/account" class="action-btn" aria-label="My Account">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </router-link>
            <router-link to="/cart" class="action-btn" aria-label="Cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
              <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Menu Drawer -->
    <MobileMenu :isOpen="showMobileMenu" :menuCategories="mobileCategories" @close="showMobileMenu = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import { useCartStore } from '@/stores/cartStore'
import { useMenuCategories } from '@/composables/useMenuCategories'
import MobileMenu from '@/components/common/MobileMenu.vue'
import logoImage from '@/assets/images/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png'

const settings = useSettingsStore()
const cart = useCartStore()
const { menuItems } = useMenuCategories()
const menuCategories = menuItems
const mobileCategories = menuItems.value

const cartCount = computed(() => cart.itemCount)
const isSticky = ref(false)
const showMobileMenu = ref(false)
const logoSrc = settings.storeSettings.logo || logoImage
const activeDropdown = ref<string | null>(null)
const activeSubmenu = ref<string | null>(null)

defineEmits(['open-search'])

function handleScroll() {
  isSticky.value = window.scrollY > 120
}
onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>


<style scoped>
/* ═══ Main Nav Container ═══ */
.main-nav {
  background: var(--header-bg);
  position: relative;
  z-index: 100;
}
.main-nav.is-sticky {
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}
.main-nav__inner {
  border-bottom: 1px solid var(--product-border-color);
}
.main-nav__row {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
}

/* ═══ Hamburger (mobile only) ═══ */
.hamburger-btn {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  margin-inline-end: 0.5rem;
  cursor: pointer;
  background: none;
  border: none;
  color: var(--header-text-color);
}
@media (min-width: 1024px) { .hamburger-btn { display: none; } }

/* ═══ Logo ═══ */
.navbar-brand {
  display: flex;
  align-items: center;
  padding: 0.375rem 0;
  flex-shrink: 0;
  text-decoration: none;
}
.navbar-brand img { height: 48px; width: auto; }

/* ═══ Desktop Menu Wrapper ═══ */
.main-menu-wrap {
  display: none;
  margin-inline-start: 0.75rem;
  flex: 1;
  min-width: 0;
  overflow: visible;
}
@media (min-width: 1024px) { .main-menu-wrap { display: flex; align-items: stretch; } }

.main-menu {
  display: flex;
  align-items: stretch;
  flex-wrap: wrap;
  list-style: none;
  margin: 0;
  padding: 0;
}

/* ═══ Root Nav Links ═══ */
.root-level { position: relative; }

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.75rem 0.625rem;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  color: var(--header-text-color);
  text-decoration: none;
  transition: color 0.15s ease;
}
.nav-link span {
  text-decoration-color: currentColor;
}
.nav-link:hover { color: var(--color-primary); }

.offers-link { color: #ef4444 !important; font-weight: 600 !important; }

.chevron-down {
  opacity: 0.5;
  transition: transform 0.2s ease;
}
.has-children:hover .chevron-down { transform: rotate(180deg); }

/* ═══ Multi-Level Mega Dropdown ═══ */
.mega-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  display: flex;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 0 0 0.5rem 0.5rem;
  box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1), 0 4px 10px -4px rgb(0 0 0 / 0.05);
  opacity: 0;
  visibility: hidden;
  transform: translateY(4px);
  transition: all 0.2s ease;
  z-index: 50;
  min-width: 240px;
}
html[dir="rtl"] .mega-dropdown { left: auto; right: 0; }
.mega-dropdown--visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

/* ── Left panel ── */
.mega-dropdown__left {
  min-width: 220px;
  max-width: 260px;
  border-inline-end: 1px solid #f3f4f6;
  max-height: 420px;
  overflow-y: auto;
}
.mega-dropdown__left ul {
  list-style: none;
  margin: 0;
  padding: 0.5rem 0;
}

.mega-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  color: #374151;
  text-decoration: none;
  transition: all 0.12s ease;
  white-space: nowrap;
}
.mega-link:hover,
.mega-dropdown__left li.is-active .mega-link {
  background: #f9fafb;
  color: var(--color-primary);
}

.chevron-right {
  opacity: 0.4;
  flex-shrink: 0;
  margin-inline-start: 0.5rem;
  transition: opacity 0.12s ease;
}
html[dir="rtl"] .chevron-right { transform: rotate(180deg); }
.mega-link:hover .chevron-right,
.mega-dropdown__left li.is-active .chevron-right {
  opacity: 0.8;
}

/* ── Right sub-panel ── */
.mega-dropdown__right {
  display: none;
  min-width: 220px;
  max-width: 260px;
  max-height: 420px;
  overflow-y: auto;
  background: #fff;
}
.mega-dropdown__right--visible { display: block; }

.mega-dropdown__right ul {
  list-style: none;
  margin: 0;
  padding: 0.5rem 0;
}
.mega-dropdown__right .mega-link {
  font-size: 0.8125rem;
  color: #4b5563;
  padding: 0.5rem 1rem;
}
.mega-dropdown__right .mega-link:hover {
  background: #f3f4f6;
  color: var(--color-primary);
}

/* Scrollbar */
.mega-dropdown__left::-webkit-scrollbar,
.mega-dropdown__right::-webkit-scrollbar { width: 4px; }
.mega-dropdown__left::-webkit-scrollbar-track,
.mega-dropdown__right::-webkit-scrollbar-track { background: transparent; }
.mega-dropdown__left::-webkit-scrollbar-thumb,
.mega-dropdown__right::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* ═══ Right Action Buttons ═══ */
.main-nav__right {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.action-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 0.5rem;
  color: var(--header-text-color);
  text-decoration: none;
  transition: color 0.15s ease;
  background: none;
  border: none;
  cursor: pointer;
}
.action-btn:hover { color: var(--color-primary); }
.cart-badge {
  position: absolute;
  top: 0.375rem;
  right: 0;
  background: var(--color-primary);
  color: #fff;
  font-size: 0.625rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}
</style>
