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
              <!--<li class="root-level">-->
              <!--  <router-link to="/products?offers=true" class="nav-link offers-link" aria-label="Offers">-->
              <!--    <span>{{ $t('nav.offers') }}</span>-->
              <!--  </router-link>-->
              <!--</li>-->
              
              <li class="root-level">
                <router-link to="/products?offers=true" class="nav-link offers-link" aria-label="Offers">
                  <span>Offer Test</span>
                </router-link>
              </li>
              

              <!-- Category dropdowns with unlimited multi-level -->
              <li
                v-for="cat in menuCategories"
                :key="cat.slug"
                class="root-level"
                :class="{ 'has-children': cat.children && cat.children.length > 0 }"
                @mouseenter="openDropdown(cat.slug)"
                @mouseleave="closeDropdown()"
              >
                <router-link :to="'/category/' + cat.slug" class="nav-link" :aria-label="cat.label">
                  <span>{{ cat.label }}</span>
                  <!-- Chevron down for categories with children -->
                  <svg v-if="cat.children && cat.children.length" class="chevron-down" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </router-link>

                <!-- Multi-level dropdown panel -->
                <div
                  v-if="cat.children && cat.children.length"
                  class="mega-dropdown"
                  :class="{ 'mega-dropdown--visible': activeDropdown === cat.slug }"
                >
                  <!-- Recursive category panels -->
                  <CategoryPanel
                    :items="cat.children"
                    :level="0"
                    :activeTrail="activeTrail"
                    @hover-item="handleHoverItem"
                    @close="closeDropdown()"
                  />
                </div>
              </li>
            </ul>
          </nav>

          <!-- Right side: Search, User, Cart -->
          <div class="main-nav__right">
            <button class="action-btn" @click="$emit('open-search')" aria-label="Search">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>

            <!-- User icon with dropdown -->
            <div class="user-dropdown-wrapper" ref="userDropdownRef">
              <button class="action-btn" @click="handleUserIconClick" aria-label="My Account">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </button>
              <!-- User Account Dropdown -->
              <Transition name="dropdown-fade">
                <div v-if="showUserDropdown" class="user-dropdown">
                  <router-link to="/account/notifications" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span>{{ $t('account.notifications') }}</span>
                  </router-link>
                  <router-link to="/account/orders" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <span>{{ $t('account.orders') }}</span>
                  </router-link>
                  <router-link :to="{ path: '/account/orders', query: { status: 'pending' } }" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>{{ $t('account.pendingPayments') }}</span>
                  </router-link>
                  <!-- <router-link to="/account/wishlist" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>{{ $t('account.wishlist') }}</span>
                  </router-link> -->
                  <router-link to="/account/wallet" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 10H2"/><path d="M6 14h.01"/></svg>
                    <span>{{ $t('account.myWallet') }}</span>
                  </router-link>
                  <router-link to="/account/loyalty-points" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
                    <span>{{ $t('account.loyaltyPoints') }}</span>
                  </router-link>
                  <router-link to="/account" class="user-dropdown__item" @click="showUserDropdown = false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/></svg>
                    <span>{{ $t('account.myAccount') }}</span>
                  </router-link>
                  <div class="user-dropdown__divider"></div>
                  <button class="user-dropdown__item user-dropdown__logout" @click="handleLogout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>{{ $t('account.logout') }}</span>
                  </button>
                </div>
              </Transition>
            </div>

            <!-- Wishlist (hidden on mobile) -->
            <!-- <router-link to="/account/wishlist" class="action-btn wishlist-action" aria-label="Wishlist">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
              <span v-if="wishlistCount > 0" class="cart-badge">{{ wishlistCount }}</span>
            </router-link> -->

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

    <!-- Login Modal -->
    <LoginModal v-model:visible="showLoginModal" @authenticated="handleAuthenticated" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, defineComponent, h, type VNode } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useSettingsStore } from '@/stores/settingsStore'
import { useCartStore } from '@/stores/cartStore'
import { useAuthStore } from '@/stores/authStore'
import { useWishlistStore } from '@/stores/wishlistStore'
import { useMenuCategories } from '@/composables/useMenuCategories'
import type { MenuItem } from '@/composables/useMenuCategories'
import MobileMenu from '@/components/common/MobileMenu.vue'
import LoginModal from '@/components/common/LoginModal.vue'
import logoImage from '@/assets/images/iEP6VGV6IrUHSpWx0M39HR3cvuGuKmQXUBAcE30B.png'

const router = useRouter()
const settings = useSettingsStore()
const cart = useCartStore()
const auth = useAuthStore()
const wishlist = useWishlistStore()
const { menuItems } = useMenuCategories()
const menuCategories = menuItems
const mobileCategories = menuItems

const cartCount = computed(() => cart.itemCount)
const wishlistCount = computed(() => wishlist.count)
const isSticky = ref(false)
const showMobileMenu = ref(false)
const showLoginModal = ref(false)
const showUserDropdown = ref(false)
const userDropdownRef = ref<HTMLElement | null>(null)
const logoSrc = settings.storeSettings.logo || logoImage
const activeDropdown = ref<string | null>(null)
// activeTrail tracks hovered items at each depth level: [level0_slug, level1_slug, ...]
const activeTrail = ref<string[]>([])

defineEmits(['open-search'])

function openDropdown(slug: string) {
  activeDropdown.value = slug
  activeTrail.value = []
}

function closeDropdown() {
  activeDropdown.value = null
  activeTrail.value = []
}

function handleHoverItem(level: number, slug: string) {
  // Set the active item at this level and clear deeper levels
  activeTrail.value = activeTrail.value.slice(0, level)
  activeTrail.value[level] = slug
}

function handleAuthenticated() {
  router.push('/account')
}

function handleUserIconClick() {
  if (auth.isAuthenticated) {
    showUserDropdown.value = !showUserDropdown.value
  } else {
    showLoginModal.value = true
  }
}

async function handleLogout() {
  showUserDropdown.value = false
  await auth.logout()
  router.push('/')
}

function handleClickOutside(e: MouseEvent) {
  if (userDropdownRef.value && !userDropdownRef.value.contains(e.target as Node)) {
    showUserDropdown.value = false
  }
}

function handleScroll() {
  isSticky.value = window.scrollY > 120
}
onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  document.addEventListener('click', handleClickOutside, true)
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside, true)
})

/**
 * Recursive CategoryPanel component for unlimited-depth flyout menus.
 * Each child panel is rendered inside the hovered <li> with absolute positioning,
 * so it aligns vertically with the hovered item (step/flyout pattern).
 */
const CategoryPanel: ReturnType<typeof defineComponent> = defineComponent({
  name: 'CategoryPanel',
  props: {
    items: { type: Array as () => MenuItem[], required: true },
    level: { type: Number, required: true },
    activeTrail: { type: Array as () => string[], required: true },
  },
  emits: ['hover-item', 'close'],
  setup(props, { emit }) {
    const activeSlugAtLevel = computed(() => props.activeTrail[props.level] || null)

    function onHover(slug: string) {
      emit('hover-item', props.level, slug)
    }

    function bubbleHover(level: number, slug: string) {
      emit('hover-item', level, slug)
    }

    return (): VNode => {
      return h('div', { class: 'mega-panel' }, [
        h('ul', props.items.map(item => {
          const hasChildren = item.children && item.children.length > 0
          const isActive = activeSlugAtLevel.value === item.slug && hasChildren

          const liChildren: VNode[] = [
            h(RouterLink, {
              to: '/category/' + item.slug,
              class: 'mega-link',
              onClick: () => emit('close'),
            }, () => [
              h('span', item.label),
              hasChildren
                ? h('svg', {
                    class: 'chevron-right',
                    xmlns: 'http://www.w3.org/2000/svg',
                    width: '14',
                    height: '14',
                    viewBox: '0 0 24 24',
                    fill: 'none',
                    stroke: 'currentColor',
                    'stroke-width': '2.5',
                    innerHTML: '<polyline points="9 18 15 12 9 6"></polyline>',
                  })
                : null,
            ]),
          ]

          // Render child panel INSIDE the hovered li (absolutely positioned)
          if (isActive && item.children) {
            liChildren.push(
              h(CategoryPanel, {
                items: item.children,
                level: props.level + 1,
                activeTrail: props.activeTrail,
                onHoverItem: bubbleHover,
                onClose: () => emit('close'),
              })
            )
          }

          return h('li', {
            key: item.slug,
            class: { 'is-active': isActive, 'has-submenu': hasChildren },
            onMouseenter: () => onHover(item.slug),
          }, liChildren)
        }))
      ])
    }
  },
})
</script>


<style>
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
.main-nav .main-nav__inner {
  border-bottom: 1px solid var(--product-border-color);
}
.main-nav .main-nav__row {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
}

/* ═══ Hamburger (mobile only) ═══ */
.main-nav .hamburger-btn {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  margin-inline-end: 0.5rem;
  cursor: pointer;
  background: none;
  border: none;
  color: var(--header-text-color);
}
@media (min-width: 1024px) { .main-nav .hamburger-btn { display: none; } }

/* ═══ Logo ═══ */
.main-nav .navbar-brand {
  display: flex;
  align-items: center;
  padding: 0.375rem 0;
  flex-shrink: 0;
  text-decoration: none;
}
.main-nav .navbar-brand img { height: 48px; width: auto; }

/* ═══ Desktop Menu Wrapper ═══ */
.main-nav .main-menu-wrap {
  display: none;
  margin-inline-start: 0.75rem;
  flex: 1;
  min-width: 0;
  overflow: visible;
}
@media (min-width: 1024px) { .main-nav .main-menu-wrap { display: flex; align-items: stretch; } }

.main-nav .main-menu {
  display: flex;
  align-items: stretch;
  flex-wrap: wrap;
  list-style: none;
  margin: 0;
  padding: 0;
}

/* ═══ Root Nav Links ═══ */
.main-nav .root-level { position: relative; }

.main-nav .nav-link {
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
.main-nav .nav-link span {
  text-decoration-color: currentColor;
}
.main-nav .nav-link:hover { color: var(--color-primary); }

.main-nav .offers-link { color: #ef4444 !important; font-weight: 600 !important; }

.main-nav .chevron-down {
  opacity: 0.5;
  transition: transform 0.2s ease;
}
.main-nav .has-children:hover .chevron-down { transform: rotate(180deg); }

/* ═══ Multi-Level Mega Dropdown ═══ */
.main-nav .mega-dropdown {
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
html[dir="rtl"] .main-nav .mega-dropdown { left: auto; right: 0; }
.main-nav .mega-dropdown--visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

/* ── Recursive panels row ── */
.main-nav .mega-panels-row {
  display: flex;
}

/* ── Each panel ── */
.main-nav .mega-panel {
  min-width: 100%;
  max-width: 280px;
  background: #fff;
}
.main-nav .mega-panel ul {
  list-style: none;
  margin: 0;
  padding: 0.375rem 0;
}

/* Items with submenu get relative positioning for flyout */
.main-nav .mega-panel li.has-submenu {
  position: relative;
}

/* Child mega-panel (nested inside li) — flyout to the right, aligned with hovered item */
.main-nav .mega-panel li > .mega-panel {
  position: absolute;
  top: 0;
  inset-inline-start: 100%;
  border-inline-start: 1px solid #e5e7eb;
  box-shadow: 4px 0 15px rgb(0 0 0 / 0.05);
  border-radius: 0 0 0.375rem 0;
  z-index: 10;
}

.main-nav .mega-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.6rem 1rem;
  font-size: 0.875rem;
  color: #374151;
  text-decoration: none;
  transition: all 0.12s ease;
  white-space: nowrap;
  gap: 1rem;
}
.main-nav .mega-link:hover,
.main-nav .mega-panel li.is-active > .mega-link {
  background: #f5f5f5;
  color: var(--color-primary);
}

.main-nav .chevron-right {
  opacity: 0.35;
  flex-shrink: 0;
  margin-inline-start: 0.5rem;
  transition: opacity 0.12s ease;
}
html[dir="rtl"] .main-nav .chevron-right { transform: rotate(180deg); }
.main-nav .mega-link:hover .chevron-right,
.main-nav .mega-panel li.is-active .chevron-right {
  opacity: 0.7;
}

/* Scrollbar */
.main-nav .mega-panel::-webkit-scrollbar { width: 4px; }
.main-nav .mega-panel::-webkit-scrollbar-track { background: transparent; }
.main-nav .mega-panel::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* ═══ Right Action Buttons ═══ */
.main-nav .main-nav__right {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.main-nav .action-btn {
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
.main-nav .action-btn:hover { color: var(--color-primary); }
.main-nav .cart-badge {
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

/* ═══ Hide wishlist on mobile ═══ */
@media (max-width: 1023px) {
  .main-nav .wishlist-action { display: none; }
}

/* ═══ User Dropdown ═══ */
.user-dropdown-wrapper {
  position: relative;
}
.user-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  min-width: 240px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  box-shadow: 0 10px 40px -5px rgba(0,0,0,0.15), 0 4px 12px -4px rgba(0,0,0,0.08);
  z-index: 200;
  padding: 0.5rem 0;
  overflow: hidden;
}
html[dir="rtl"] .user-dropdown { right: auto; left: 0; }

.user-dropdown__item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.75rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #374151;
  text-decoration: none;
  transition: all 0.15s ease;
  background: none;
  border: none;
  width: 100%;
  cursor: pointer;
  white-space: nowrap;
}
.user-dropdown__item:hover {
  background: #f3f4f6;
  color: var(--color-primary);
}
.user-dropdown__item svg {
  flex-shrink: 0;
  color: #6b7280;
  transition: color 0.15s ease;
}
.user-dropdown__item:hover svg {
  color: var(--color-primary);
}
.user-dropdown__divider {
  height: 1px;
  background: #e5e7eb;
  margin: 0.375rem 0;
}
.user-dropdown__logout {
  color: #ef4444;
}
.user-dropdown__logout:hover {
  background: #fef2f2;
  color: #dc2626;
}
.user-dropdown__logout svg {
  color: #ef4444;
}
.user-dropdown__logout:hover svg {
  color: #dc2626;
}

/* Dropdown transition */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.main-nav .sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}
</style>

