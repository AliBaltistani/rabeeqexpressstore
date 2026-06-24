import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  fetchCart, addCartItem, updateCartItem, removeCartItem,
  clearCartApi, applyCouponApi, removeCouponApi,
} from '@/api/services'
import type { CartItem, CartData, PriceValue } from '@/types'

const emptyPrice: PriceValue = { raw: 0, formatted: '0.00 SAR' }

export const useCartStore = defineStore('cart', () => {
  // ─── State ───
  const items = ref<CartItem[]>([])
  const couponCode = ref<string | null>(null)
  const subtotalValue = ref<PriceValue>(emptyPrice)
  const discountValue = ref<PriceValue>(emptyPrice)
  const shippingValue = ref<PriceValue>(emptyPrice)
  const totalValue = ref<PriceValue>(emptyPrice)
  const currency = ref('SAR')
  const isLoading = ref(false)

  // ─── Getters ───
  const itemCount = computed(() => items.value.reduce((sum, i) => sum + i.quantity, 0))
  const subtotal = computed(() => subtotalValue.value)
  const discount = computed(() => discountValue.value)
  const total = computed(() => totalValue.value)

  // ─── Sync from API response ───
  function syncFromApi(data: CartData) {
    items.value = data.items || []
    subtotalValue.value = data.subtotal || emptyPrice
    discountValue.value = data.discountAmount || emptyPrice
    shippingValue.value = data.shippingAmount || emptyPrice
    totalValue.value = data.total || emptyPrice
    couponCode.value = data.couponCode || null
    currency.value = data.currency || 'SAR'
  }

  // ─── Actions ───
  async function loadCart() {
    isLoading.value = true
    try {
      const data = await fetchCart()
      syncFromApi(data)
    } catch (error) {
      console.error('Failed to load cart:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function addItem(productId: number, quantity = 1, variantId?: number | null, attributeValues?: number[]) {
    isLoading.value = true
    try {
      const data = await addCartItem(productId, quantity, variantId, attributeValues)
      syncFromApi(data)
    } finally {
      isLoading.value = false
    }
  }

  async function updateQuantity(id: number, quantity: number) {
    if (quantity < 1) return
    try {
      const data = await updateCartItem(id, quantity)
      syncFromApi(data)
    } catch (error) {
      console.error('Failed to update quantity:', error)
    }
  }

  async function updateItemAttributes(id: number, productId: number, quantity: number, attributeValues: number[]) {
    isLoading.value = true
    try {
      // API only supports quantity updates; to change attributes we remove + re-add
      const removed = await removeCartItem(id)
      syncFromApi(removed)
      const data = await addCartItem(productId, quantity, undefined, attributeValues)
      syncFromApi(data)
    } catch (error) {
      console.error('Failed to update item attributes:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function removeItem(id: number) {
    try {
      const data = await removeCartItem(id)
      syncFromApi(data)
    } catch (error) {
      console.error('Failed to remove item:', error)
    }
  }

  async function clearCart() {
    try {
      const data = await clearCartApi()
      syncFromApi(data)
    } catch (error) {
      items.value = []
      subtotalValue.value = emptyPrice
      discountValue.value = emptyPrice
      totalValue.value = emptyPrice
      couponCode.value = null
    }
  }

  async function applyCoupon(code: string) {
    try {
      const result = await applyCouponApi(code)
      syncFromApi(result.data)
      return { success: true, freeShipping: result.freeShipping || false, message: result.message }
    } catch (error: any) {
      return { success: false, freeShipping: false, message: error.response?.data?.message || 'Failed to apply coupon' }
    }
  }

  async function removeCoupon() {
    try {
      const data = await removeCouponApi()
      syncFromApi(data)
    } catch (error) {
      couponCode.value = null
      discountValue.value = emptyPrice
    }
  }

  return {
    items, couponCode, subtotal, discount, total, itemCount,
    currency, isLoading, shippingValue,
    loadCart, addItem, updateQuantity, updateItemAttributes, removeItem, clearCart,
    applyCoupon, removeCoupon, syncFromApi,
  }
})
