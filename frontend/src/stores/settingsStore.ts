import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { fetchInit, fetchCategories } from '@/api/services'
import type { ApiCurrency, ApiLanguage, InitData, Category } from '@/types'

export interface CurrencyLocal {
  code: string
  name: string
  symbol: string
  exchangeRate: number
  decimalPlaces: number
  isDefault: boolean
}

export interface LanguageLocal {
  code: string
  name: string
  direction: 'ltr' | 'rtl'
  isDefault: boolean
}

export const useSettingsStore = defineStore('settings', () => {
  // ─── State ───
  const isInitialized = ref(false)
  const isLoading = ref(false)

  const currencies = ref<Record<string, CurrencyLocal>>({
    SAR: { code: 'SAR', name: 'Saudi Riyal', symbol: 'SAR', exchangeRate: 1, decimalPlaces: 2, isDefault: true },
  })

  const languages = ref<Record<string, LanguageLocal>>({
    EN: { code: 'en', name: 'English', direction: 'ltr', isDefault: true },
    AR: { code: 'ar', name: 'العربية', direction: 'rtl', isDefault: false },
  })

  const currentCurrencyCode = ref(localStorage.getItem('currency') || 'SAR')
  const currentLanguageCode = ref(localStorage.getItem('language') || 'en')

  // Store settings (populated from /init API)
  const storeSettings = ref({
    storeName: 'E-SEVEN STORE',
    storeTagline: '',
    storeDescription: "E7seven Store is the largest shoe store in Saudi Arabia 👟",
    whatsappNumber: '+966566229730',
    email: 'eseven.store@gmail.com',
    phone: '',
    logo: null as string | null,
    favicon: null as string | null,
    announcementText: '',
    announcementLink: '',
    social: {
      instagram: 'https://instagram.com/eseven.store',
      snapchat: 'https://www.snapchat.com/add/eseven-store',
      tiktok: 'https://www.tiktok.com/@essven.store',
      youtube: 'https://www.youtube.com/@eseven-store/featured',
      facebook: '',
      twitter: '',
    },
    apps: {
      appstore: 'https://apps.apple.com/sa/app/eseven-store/id6453605018',
      googleplay: 'https://play.google.com/store/apps/details?id=com.salla.esevenstore&pli=1',
    },
    paymentMethods: [] as { id: string; name: string; fee?: number; logo?: string }[],
    features: {
      guestCheckout: true,
      wishlist: true,
      reviews: true,
      reviewsRequireApproval: true,
    },
  })

  // Navigation categories (populated from /categories API)
  const navCategories = ref<Category[]>([])

  // ─── Getters ───
  const currentCurrency = computed<CurrencyLocal | undefined>(() => currencies.value[currentCurrencyCode.value])
  const currentLanguage = computed<LanguageLocal | undefined>(() => {
    return languages.value[currentLanguageCode.value.toUpperCase()]
  })
  const isRtl = computed(() => currentLanguage.value?.direction === 'rtl')

  const currencyList = computed(() => Object.values(currencies.value))
  const languageList = computed(() => Object.values(languages.value))

  // ─── Actions ───
  function setCurrency(code: string) {
    currentCurrencyCode.value = code
    localStorage.setItem('currency', code)
  }

  function setLanguage(code: string) {
    currentLanguageCode.value = code
    localStorage.setItem('language', code)
    const lang = languages.value[code.toUpperCase()]
    if (lang) {
      document.documentElement.lang = lang.code
      document.documentElement.dir = lang.direction === 'rtl' ? 'rtl' : 'ltr'
    }
  }

  function initDirection() {
    const lang = currentLanguage.value
    if (lang) {
      document.documentElement.lang = lang.code
      document.documentElement.dir = lang.direction === 'rtl' ? 'rtl' : 'ltr'
    }
  }

  /**
   * Fetch all store bootstrap data from /api/v1/init + /api/v1/categories.
   * Called once on app startup.
   */
  async function initialize() {
    if (isInitialized.value) return
    isLoading.value = true

    try {
      // Fetch init data and categories in parallel
      const [initData, categoriesData] = await Promise.allSettled([
        fetchInit(),
        fetchCategories(),
      ])

      // Process init data
      if (initData.status === 'fulfilled') {
        const data = initData.value

        // Populate currencies
        if (data.currencies && data.currencies.length > 0) {
          const currMap: Record<string, CurrencyLocal> = {}
          data.currencies.forEach((c: ApiCurrency) => {
            currMap[c.code] = {
              code: c.code,
              name: c.name,
              symbol: c.symbol,
              exchangeRate: c.exchangeRate,
              decimalPlaces: c.decimalPlaces,
              isDefault: c.isDefault,
            }
          })
          currencies.value = currMap
        }

        // Populate languages
        if (data.languages && data.languages.length > 0) {
          const langMap: Record<string, LanguageLocal> = {}
          data.languages.forEach((l: ApiLanguage) => {
            langMap[l.code.toUpperCase()] = {
              code: l.code,
              name: l.name,
              direction: l.direction,
              isDefault: l.isDefault,
            }
          })
          languages.value = langMap
        }

        // Store settings
        storeSettings.value = {
          storeName: data.storeName || 'E-SEVEN STORE',
          storeTagline: data.storeTagline || '',
          storeDescription: data.storeTagline || storeSettings.value.storeDescription,
          whatsappNumber: data.whatsappNumber || storeSettings.value.whatsappNumber,
          email: data.storeEmail || storeSettings.value.email,
          phone: data.storePhone || '',
          logo: data.logo || null,
          favicon: data.favicon || null,
          announcementText: data.announcementText || storeSettings.value.announcementText,
          announcementLink: data.announcementLink || '',
          social: {
            instagram: data.socialLinks?.instagram || storeSettings.value.social.instagram,
            snapchat: data.socialLinks?.snapchat || storeSettings.value.social.snapchat,
            tiktok: data.socialLinks?.tiktok || storeSettings.value.social.tiktok,
            youtube: data.socialLinks?.youtube || storeSettings.value.social.youtube,
            facebook: data.socialLinks?.facebook || '',
            twitter: data.socialLinks?.twitter || '',
          },
          apps: storeSettings.value.apps,
          paymentMethods: data.paymentMethods || [],
          features: data.features || storeSettings.value.features,
        }

        // Set default currency/language if no user preference saved
        if (!localStorage.getItem('currency') && data.defaultCurrency) {
          setCurrency(data.defaultCurrency)
        }
        if (!localStorage.getItem('language') && data.defaultLanguage) {
          setLanguage(data.defaultLanguage)
        }
      }

      // Process categories
      if (categoriesData.status === 'fulfilled') {
        navCategories.value = categoriesData.value
      }

      isInitialized.value = true
    } catch (error) {
      console.error('Failed to initialize store settings:', error)
      // App will work with hardcoded defaults
      isInitialized.value = true
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    isInitialized, isLoading,
    currencies, languages, currentCurrencyCode, currentLanguageCode,
    storeSettings, navCategories,
    // Getters
    currentCurrency, currentLanguage, isRtl, currencyList, languageList,
    // Actions
    setCurrency, setLanguage, initDirection, initialize,
  }
})
