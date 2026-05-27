/**
 * Global share menu state (reactive singleton).
 * Any component can call openShare() and the single ShareMenu
 * mounted in App.vue will display social sharing options.
 */
import { ref, computed } from 'vue'

export interface ShareData {
  title: string
  url: string
}

// ─── Module-level reactive state ───
const isVisible = ref(false)
const shareData = ref<ShareData>({ title: '', url: '' })
const anchorRect = ref<DOMRect | null>(null)

export function useShareMenu() {
  function openShare(data: ShareData, anchorEl?: HTMLElement | null) {
    shareData.value = data
    anchorRect.value = anchorEl?.getBoundingClientRect() ?? null
    isVisible.value = true
  }

  function closeShare() {
    isVisible.value = false
  }

  const facebookUrl = computed(() =>
    `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareData.value.url)}`
  )
  const twitterUrl = computed(() =>
    `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareData.value.title)}&url=${encodeURIComponent(shareData.value.url)}`
  )
  const whatsappUrl = computed(() =>
    `https://wa.me/?text=${encodeURIComponent(shareData.value.title + ' ' + shareData.value.url)}`
  )
  const emailUrl = computed(() =>
    `mailto:?subject=${encodeURIComponent(shareData.value.title)}&body=${encodeURIComponent(shareData.value.url)}`
  )

  return {
    isVisible,
    shareData,
    anchorRect,
    openShare,
    closeShare,
    facebookUrl,
    twitterUrl,
    whatsappUrl,
    emailUrl,
  }
}
