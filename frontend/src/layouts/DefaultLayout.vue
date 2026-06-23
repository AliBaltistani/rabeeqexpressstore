<template>
  <div class="app-inner">
    <!-- Promotion Banner -->
    <PromotionBanner />

    <!-- Top Bar (scrolls away) -->
    <div class="store-topbar">
      <TopNavbar @open-localization="showLocalization = true" />
    </div>

    <!-- Main Navigation (sticky) -->
    <header class="store-header">
      <MainNavigation @open-search="showSearch = true" />
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

    <!-- Modals -->
    <LocalizationModal :isOpen="showLocalization" @close="showLocalization = false" />
    <SearchModal :isOpen="showSearch" @close="showSearch = false" />

    <!-- Mobile menu padding -->
    <div class="mobile-nav-spacer"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

import PromotionBanner from '@/components/common/PromotionBanner.vue'
import TopNavbar from '@/components/common/TopNavbar.vue'
import MainNavigation from '@/components/common/MainNavigation.vue'
import StoreFooter from '@/components/common/StoreFooter.vue'
import WhatsAppButton from '@/components/common/WhatsAppButton.vue'
import ScrollToTop from '@/components/common/ScrollToTop.vue'
import BottomNavigation from '@/components/common/BottomNavigation.vue'
import LocalizationModal from '@/components/common/LocalizationModal.vue'
import SearchModal from '@/components/common/SearchModal.vue'

const settings = useSettingsStore()

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
.store-topbar {
  background: var(--header-bg);
  color: var(--header-text-color);
}
.store-header {
  position: sticky;
  top: 0;
  z-index: 200;
  background: var(--header-bg);
  color: var(--header-text-color);
}
.main-content { flex: 1; }
.page-enter-active, .page-leave-active { transition: opacity 150ms ease; }
.page-enter-from, .page-leave-to { opacity: 0; }
.mobile-nav-spacer { height: 0; }
@media (max-width: 1023px) { .mobile-nav-spacer { height: 3.5rem; } }
</style>
