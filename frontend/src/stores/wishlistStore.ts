import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { fetchWishlist, addWishlistItem, removeWishlistItem } from '@/api/services'
import { useAuthStore } from './authStore'

export const useWishlistStore = defineStore('wishlist', () => {
  // Local storage items for guests, API items for authenticated users
  const items = ref<number[]>(JSON.parse(localStorage.getItem('wishlist') || '[]'))
  const isLoaded = ref(false)

  const count = computed(() => items.value.length)

  function saveLocal() {
    localStorage.setItem('wishlist', JSON.stringify(items.value))
  }

  async function addItem(productId: number) {
    if (items.value.includes(productId)) return

    items.value.push(productId)
    saveLocal()

    const auth = useAuthStore()
    if (auth.isAuthenticated) {
      try {
        await addWishlistItem(productId)
      } catch (error) {
        console.error('Failed to add wishlist item:', error)
      }
    }
  }

  async function removeItem(productId: number) {
    items.value = items.value.filter((id) => id !== productId)
    saveLocal()

    const auth = useAuthStore()
    if (auth.isAuthenticated) {
      try {
        await removeWishlistItem(productId)
      } catch (error) {
        console.error('Failed to remove wishlist item:', error)
      }
    }
  }

  function isInWishlist(productId: number) {
    return items.value.includes(productId)
  }

  async function toggleItem(productId: number) {
    if (isInWishlist(productId)) {
      await removeItem(productId)
    } else {
      await addItem(productId)
    }
  }

  /**
   * Load wishlist from API for authenticated users.
   * Merges with any guest localStorage wishlist.
   */
  async function loadWishlist() {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) return

    try {
      const apiItems = await fetchWishlist()
      const apiIds = apiItems.map((p) => p.id)
      // Merge local + API (deduplicate)
      const merged = [...new Set([...items.value, ...apiIds])]
      items.value = merged
      saveLocal()
      isLoaded.value = true
    } catch (error) {
      console.error('Failed to load wishlist:', error)
    }
  }

  return { items, count, addItem, removeItem, isInWishlist, toggleItem, loadWishlist }
})
