/**
 * Global cart-toast state (reactive singleton).
 * Any component can call showToast() and the single CartToastGlobal
 * mounted in App.vue will react to it.
 */
import { ref, computed } from 'vue'

export interface CartToastProduct {
  name: string
  image: string
  price: string
}

// ─── Module-level reactive state (shared across all consumers) ───
const isVisible = ref(false)
const progressActive = ref(false)
const toastProduct = ref<CartToastProduct>({ name: '', image: '', price: '' })
let dismissTimer: ReturnType<typeof setTimeout> | null = null

const positionClass = computed(() => {
  const dir = document.documentElement.dir || 'ltr'
  return dir === 'rtl' ? 'cart-toast--left' : 'cart-toast--right'
})

export function useCartToast() {
  function showToast(product: CartToastProduct) {
    // Reset any existing timer
    if (dismissTimer) clearTimeout(dismissTimer)
    progressActive.value = false

    toastProduct.value = product
    isVisible.value = true

    // Trigger progress bar animation after next repaint
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        progressActive.value = true
      })
    })

    // Auto-dismiss after 5 seconds
    dismissTimer = setTimeout(() => {
      isVisible.value = false
      progressActive.value = false
    }, 5000)
  }

  function hideToast() {
    if (dismissTimer) clearTimeout(dismissTimer)
    isVisible.value = false
    progressActive.value = false
  }

  return {
    isVisible,
    progressActive,
    toastProduct,
    positionClass,
    showToast,
    hideToast,
  }
}
