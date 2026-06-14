<template>
  <!-- Maintenance Mode -->
  <MaintenancePage v-if="settings.isInitialized && settings.storeSettings.maintenance.enabled" />

  <!-- Normal App -->
  <template v-else>
    <router-view />
    <QuickViewModal />
    <CartToastGlobal />
    <ShareMenu />
  </template>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import { useAuthStore } from '@/stores/authStore'
import { useCartStore } from '@/stores/cartStore'
import { useLanguage } from '@/composables/useLanguage'
import QuickViewModal from '@/components/product/QuickViewModal.vue'
import CartToastGlobal from '@/components/common/CartToastGlobal.vue'
import ShareMenu from '@/components/common/ShareMenu.vue'
import MaintenancePage from '@/pages/MaintenancePage.vue'

const settings = useSettingsStore()
const auth = useAuthStore()
const cart = useCartStore()
const { initLanguage } = useLanguage()

onMounted(async () => {
  // Initialize store settings from API
  await settings.initialize()

  // Set language direction
  initLanguage()

  // Skip loading user session and cart if in maintenance mode
  if (settings.storeSettings.maintenance.enabled) return

  // Restore auth session if token exists
  await auth.restoreSession()

  // Load cart
  try {
    await cart.loadCart()
  } catch {
    // Cart load may fail for guests without session
  }
})
</script>

