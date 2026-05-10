<template>
  <div class="app-inner">
    <!-- Promotion Banner -->
    <PromotionBanner />

    <!-- Header -->
    <header class="store-header">
      <TopNavbar @open-localization="showLocalization = true" />
      <MainNavigation @toggle-mobile-menu="showMobileMenu = !showMobileMenu" @open-search="showSearch = true" />
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Footer -->
    <StoreFooter />

    <!-- Floating Elements -->
    <WhatsAppButton />
    <ScrollToTop />
    <BottomNavigation />

    <!-- Modals & Drawers -->
    <LocalizationModal :isOpen="showLocalization" @close="showLocalization = false" />
    <MobileMenu :isOpen="showMobileMenu" :menuCategories="mobileCategories" @close="showMobileMenu = false" />

    <!-- Mobile menu padding -->
    <div class="mobile-nav-spacer"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import { useMenuCategories } from '@/composables/useMenuCategories'

import PromotionBanner from '@/components/common/PromotionBanner.vue'
import TopNavbar from '@/components/common/TopNavbar.vue'
import MainNavigation from '@/components/common/MainNavigation.vue'
import StoreFooter from '@/components/common/StoreFooter.vue'
import WhatsAppButton from '@/components/common/WhatsAppButton.vue'
import ScrollToTop from '@/components/common/ScrollToTop.vue'
import BottomNavigation from '@/components/common/BottomNavigation.vue'
import LocalizationModal from '@/components/common/LocalizationModal.vue'
import MobileMenu from '@/components/common/MobileMenu.vue'

const settings = useSettingsStore()
const { menuCategories } = useMenuCategories()
const mobileCategories = menuCategories.value

const showMobileMenu = ref(false)
const showLocalization = ref(false)
const showSearch = ref(false)

onMounted(() => {
  settings.initDirection()
})
</script>

<style scoped>
.app-inner {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: var(--bg-primary);
}
.store-header {
  position: relative;
  z-index: 100;
  background: var(--header-bg);
  color: var(--header-text-color);
}
.main-content { flex: 1; }
.page-enter-active, .page-leave-active { transition: opacity 150ms ease; }
.page-enter-from, .page-leave-to { opacity: 0; }
.mobile-nav-spacer { height: 0; }
@media (max-width: 1023px) { .mobile-nav-spacer { height: 3.5rem; } }
</style>
