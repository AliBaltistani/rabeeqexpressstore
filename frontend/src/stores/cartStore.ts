import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface CartItem {
  id: number
  productId: number
  name: string
  image: string
  price: number
  comparePrice?: number
  quantity: number
  variant?: { size?: string; color?: string }
  maxQuantity: number
}

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([])
  const coupon = ref<string | null>(null)
  const discount = ref(0)

  const itemCount = computed(() => items.value.reduce((sum, i) => sum + i.quantity, 0))
  const subtotal = computed(() => items.value.reduce((sum, i) => sum + i.price * i.quantity, 0))
  const total = computed(() => Math.max(0, subtotal.value - discount.value))

  function addItem(item: Omit<CartItem, 'quantity'>, qty = 1) {
    const existing = items.value.find(
      (i) => i.productId === item.productId && JSON.stringify(i.variant) === JSON.stringify(item.variant)
    )
    if (existing) {
      existing.quantity = Math.min(existing.quantity + qty, existing.maxQuantity)
    } else {
      items.value.push({ ...item, quantity: qty })
    }
  }

  function removeItem(id: number) {
    items.value = items.value.filter((i) => i.id !== id)
  }

  function updateQuantity(id: number, quantity: number) {
    const item = items.value.find((i) => i.id === id)
    if (item) item.quantity = Math.max(1, Math.min(quantity, item.maxQuantity))
  }

  function clearCart() {
    items.value = []
    coupon.value = null
    discount.value = 0
  }

  function applyCoupon(code: string) {
    coupon.value = code
    // discount will be calculated from API response
  }

  function removeCoupon() {
    coupon.value = null
    discount.value = 0
  }

  return {
    items, coupon, discount, itemCount, subtotal, total,
    addItem, removeItem, updateQuantity, clearCart, applyCoupon, removeCoupon,
  }
})
