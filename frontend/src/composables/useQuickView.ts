import { ref } from 'vue'

export interface QuickViewProduct {
  id: number
  slug: string
  name: string
  subtitle?: string
  image: string
  price: number
  oldPrice?: number
  discount?: number
  currency?: string
}

const isOpen = ref(false)
const product = ref<QuickViewProduct | null>(null)

export function useQuickView() {
  function open(p: QuickViewProduct) {
    product.value = p
    isOpen.value = true
    document.documentElement.classList.add('quickview-opened')
  }

  function close() {
    isOpen.value = false
    document.documentElement.classList.remove('quickview-opened')
    setTimeout(() => {
      product.value = null
    }, 500)
  }

  return { isOpen, product, open, close }
}
