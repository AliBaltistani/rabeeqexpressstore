<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <transition name="share-fade">
      <div v-if="isVisible" class="share-menu__backdrop" @click="closeShare"></div>
    </transition>
    <!-- Menu -->
    <transition name="share-pop">
      <div v-if="isVisible" class="share-menu" :style="menuStyle">
        <button class="share-menu__close" @click="closeShare" aria-label="Close">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <a :href="facebookUrl" target="_blank" rel="noopener" class="share-menu__item" @click="closeShare" aria-label="Share on Facebook">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a :href="twitterUrl" target="_blank" rel="noopener" class="share-menu__item" @click="closeShare" aria-label="Share on X">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <a :href="whatsappUrl" target="_blank" rel="noopener" class="share-menu__item" @click="closeShare" aria-label="Share on WhatsApp">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
        </a>
        <a :href="emailUrl" class="share-menu__item" @click="closeShare" aria-label="Share via Email">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </a>
        <button class="share-menu__item" @click="copyLink" aria-label="Copy link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          <transition name="share-fade">
            <span v-if="copied" class="share-menu__copied">✓</span>
          </transition>
        </button>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useShareMenu } from '@/composables/useShareMenu'

const { isVisible, shareData, anchorRect, closeShare, facebookUrl, twitterUrl, whatsappUrl, emailUrl } = useShareMenu()

const copied = ref(false)

const menuStyle = computed(() => {
  const rect = anchorRect.value
  if (!rect) return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }
  // Position menu directly below the button, centered horizontally
  const top = rect.bottom + 8
  const left = rect.left + rect.width / 2
  return {
    position: 'fixed' as const,
    top: `${top}px`,
    left: `${left}px`,
    transform: 'translateX(-50%)',
  }
})

function copyLink() {
  navigator.clipboard.writeText(shareData.value.url).catch(() => {})
  copied.value = true
  setTimeout(() => {
    copied.value = false
    closeShare()
  }, 1200)
}
</script>
