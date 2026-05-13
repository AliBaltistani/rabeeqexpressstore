<template>
  <Teleport to="body">
    <!-- Overlay -->
    <transition name="overlay-fade">
      <div v-if="isOpen" class="mobile-menu-overlay" @click="close"></div>
    </transition>

    <!-- Drawer -->
    <transition name="drawer-slide">
      <div v-if="isOpen" class="mobile-menu">
        <!-- Header -->
        <div class="mobile-menu__header">
          <template v-if="currentLevel === 0">
            <span class="mobile-menu__title">Main Menu</span>
          </template>
          <template v-else>
            <button class="mobile-menu__back" @click="goBack" aria-label="Go back">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <span class="mobile-menu__title">{{ currentTitle }}</span>
          </template>
          <button class="mobile-menu__close" @click="close" aria-label="Close menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

        <!-- Menu Content -->
        <div class="mobile-menu__content">
          <transition :name="slideDirection" mode="out-in">
            <!-- Level 0: Root categories -->
            <ul v-if="currentLevel === 0" key="root" class="mobile-menu__list">
              <!-- Offers -->
              <li class="mobile-menu__item">
                <router-link to="/products?offers=true" class="mobile-menu__link mobile-menu__link--offers" @click="close">
                  <span>{{ $t('nav.offers') }}</span>
                </router-link>
              </li>
              <!-- Categories -->
              <li v-for="cat in menuCategories" :key="cat.slug" class="mobile-menu__item">
                <template v-if="cat.children && cat.children.length">
                  <button class="mobile-menu__link" @click="drillDown(cat)">
                    <span>{{ cat.name }}</span>
                    <svg class="mobile-menu__chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                  </button>
                </template>
                <template v-else>
                  <router-link :to="'/category/' + cat.slug" class="mobile-menu__link" @click="close">
                    <span>{{ cat.name }}</span>
                  </router-link>
                </template>
              </li>
            </ul>

            <!-- Level 1: Sub-categories -->
            <ul v-else-if="currentLevel === 1" :key="'l1-' + currentParent?.slug" class="mobile-menu__list">
              <!-- View all -->
              <li class="mobile-menu__item">
                <router-link :to="'/category/' + currentParent?.slug" class="mobile-menu__link mobile-menu__link--viewall" @click="close">
                  <span>{{ $t('common.viewAll') }}</span>
                </router-link>
              </li>
              <li v-for="child in currentParent?.children" :key="child.slug" class="mobile-menu__item">
                <template v-if="child.children && child.children.length">
                  <button class="mobile-menu__link" @click="drillDownLevel2(child)">
                    <span>{{ child.name }}</span>
                    <svg class="mobile-menu__chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                  </button>
                </template>
                <template v-else>
                  <router-link :to="'/category/' + child.slug" class="mobile-menu__link" @click="close">
                    <span>{{ child.name }}</span>
                  </router-link>
                </template>
              </li>
            </ul>

            <!-- Level 2: Sub-sub-categories -->
            <ul v-else-if="currentLevel === 2" :key="'l2-' + currentChild?.slug" class="mobile-menu__list">
              <!-- View all -->
              <li class="mobile-menu__item">
                <router-link :to="'/category/' + currentChild?.slug" class="mobile-menu__link mobile-menu__link--viewall" @click="close">
                  <span>{{ $t('common.viewAll') }}</span>
                </router-link>
              </li>
              <li v-for="sub in currentChild?.children" :key="sub.slug" class="mobile-menu__item">
                <router-link :to="'/category/' + sub.slug" class="mobile-menu__link" @click="close">
                  <span>{{ sub.name }}</span>
                </router-link>
              </li>
            </ul>
          </transition>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

interface Category {
  name: string
  slug: string
  children: Category[]
}

const props = defineProps<{ isOpen: boolean; menuCategories: Category[] }>()
const emit = defineEmits(['close'])

const settings = useSettingsStore()


const currentLevel = ref(0)
const currentParent = ref<Category | null>(null)
const currentChild = ref<Category | null>(null)
const currentTitle = ref('')
const slideDirection = ref('slide-left')

// Reset when menu opens
watch(() => props.isOpen, (val) => {
  if (val) {
    currentLevel.value = 0
    currentParent.value = null
    currentChild.value = null
    currentTitle.value = ''
  }
})

function close() {
  emit('close')
}

function drillDown(cat: Category) {
  slideDirection.value = 'slide-left'
  currentParent.value = cat
  currentTitle.value = cat.name
  currentLevel.value = 1
}

function drillDownLevel2(child: Category) {
  slideDirection.value = 'slide-left'
  currentChild.value = child
  currentTitle.value = child.name
  currentLevel.value = 2
}

function goBack() {
  slideDirection.value = 'slide-right'
  if (currentLevel.value === 2) {
    currentLevel.value = 1
    currentTitle.value = currentParent.value?.name || ''
    currentChild.value = null
  } else if (currentLevel.value === 1) {
    currentLevel.value = 0
    currentParent.value = null
    currentTitle.value = ''
  }
}
</script>

<style scoped>
/* ═══ Overlay ═══ */
.mobile-menu-overlay {
  position: fixed;
  inset: 0;
  z-index: 998;
  background: rgba(0, 0, 0, 0.5);
}

/* ═══ Drawer ═══ */
.mobile-menu {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 999;
  width: 300px;
  max-width: 85vw;
  background: #fff;
  display: flex;
  flex-direction: column;
  box-shadow: 4px 0 15px rgb(0 0 0 / 0.15);
}
html[dir="rtl"] .mobile-menu {
  left: auto;
  right: 0;
  box-shadow: -4px 0 15px rgb(0 0 0 / 0.15);
}

/* ═══ Header ═══ */
.mobile-menu__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.mobile-menu__title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111827;
  flex: 1;
}

.mobile-menu__back {
  background: none;
  border: none;
  padding: 0;
  margin-inline-end: 0.5rem;
  cursor: pointer;
  color: #374151;
  display: flex;
  align-items: center;
}
html[dir="rtl"] .mobile-menu__back svg {
  transform: rotate(180deg);
}

.mobile-menu__close {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: #ef4444;
  display: flex;
  align-items: center;
}

/* ═══ Content ═══ */
.mobile-menu__content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
}

.mobile-menu__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.mobile-menu__item {
  border-bottom: 1px solid #f3f4f6;
}

.mobile-menu__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
  text-align: start;
  transition: background 0.1s ease;
}
.mobile-menu__link:hover {
  background: #fafafa;
}

.mobile-menu__link--offers {
  color: #10b981;
  font-weight: 600;
}

.mobile-menu__link--viewall {
  color: #10b981;
  font-weight: 500;
}

.mobile-menu__chevron {
  color: #9ca3af;
  flex-shrink: 0;
}
html[dir="rtl"] .mobile-menu__chevron {
  transform: rotate(180deg);
}

/* ═══ Transitions ═══ */

/* Overlay */
.overlay-fade-enter-active,
.overlay-fade-leave-active { transition: opacity 0.25s ease; }
.overlay-fade-enter-from,
.overlay-fade-leave-to { opacity: 0; }

/* Drawer slide */
.drawer-slide-enter-active,
.drawer-slide-leave-active { transition: transform 0.3s ease; }
.drawer-slide-enter-from { transform: translateX(-100%); }
.drawer-slide-leave-to { transform: translateX(-100%); }
html[dir="rtl"] .drawer-slide-enter-from { transform: translateX(100%); }
html[dir="rtl"] .drawer-slide-leave-to { transform: translateX(100%); }

/* Content slide transitions */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.slide-left-enter-from { transform: translateX(30px); opacity: 0; }
.slide-left-leave-to { transform: translateX(-30px); opacity: 0; }
.slide-right-enter-from { transform: translateX(-30px); opacity: 0; }
.slide-right-leave-to { transform: translateX(30px); opacity: 0; }
</style>
