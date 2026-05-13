import { computed } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'
import type { Category } from '@/types'

export interface MenuItem {
  label: string
  slug: string
  to?: string
  image?: string | null
  children?: MenuItem[]
}

/**
 * Provides navigation menu items from API-fetched categories.
 * Categories are loaded in settingsStore.initialize().
 */
export function useMenuCategories() {
  const settings = useSettingsStore()

  const menuItems = computed<MenuItem[]>(() => {
    const categories = settings.navCategories || []
    return categories.map(mapCategory)
  })

  function mapCategory(cat: Category): MenuItem {
    const item: MenuItem = {
      label: cat.name,
      slug: cat.slug,
      to: `/category/${cat.slug}`,
      image: cat.image || null,
    }
    if (cat.children && cat.children.length > 0) {
      item.children = cat.children.map(mapCategory)
    }
    return item
  }

  return { menuItems }
}
