<template>
  <router-view />
  <QuickViewModal />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import { useAuthStore } from '@/stores/authStore'
import { useCartStore } from '@/stores/cartStore'
import { useLanguage } from '@/composables/useLanguage'
import QuickViewModal from '@/components/product/QuickViewModal.vue'

const settings = useSettingsStore()
const auth = useAuthStore()
const cart = useCartStore()
const { initLanguage } = useLanguage()

onMounted(async () => {
  // Initialize store settings from API
  await settings.initialize()

  // Set language direction
  initLanguage()

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
