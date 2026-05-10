import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useWishlistStore = defineStore('wishlist', () => {
  const items = ref<number[]>(JSON.parse(localStorage.getItem('wishlist') || '[]'))

  function addItem(productId: number) {
    if (!items.value.includes(productId)) {
      items.value.push(productId)
      localStorage.setItem('wishlist', JSON.stringify(items.value))
    }
  }

  function removeItem(productId: number) {
    items.value = items.value.filter((id) => id !== productId)
    localStorage.setItem('wishlist', JSON.stringify(items.value))
  }

  function isInWishlist(productId: number) {
    return items.value.includes(productId)
  }

  function toggleItem(productId: number) {
    if (isInWishlist(productId)) {
      removeItem(productId)
    } else {
      addItem(productId)
    }
  }

  return { items, addItem, removeItem, isInWishlist, toggleItem }
})
