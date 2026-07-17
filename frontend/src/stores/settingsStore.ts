import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { fetchInit, fetchCategories } from '@/api/services'
import type { ApiCurrency, ApiLanguage, InitData, Category } from '@/types'

export interface PromoBarConfig {
  enabled: boolean
  mode: 'marquee' | 'static' | 'rotate'
  style: 'filled' | 'gradient' | 'outline'
  bgColor: string
  textColor: string
  gradientFrom: string
  gradientTo: string
  message: string
  items: string[] | null
  icon: string
  linkUrl: string
  linkTarget: '_self' | '_blank'
  marqueeSpeed: 'slow' | 'medium' | 'fast'
  fontSize: 'xs' | 'sm' | 'md' | 'lg'
  fontWeight: 'normal' | 'medium' | 'semibold' | 'bold'
  barHeight: 'compact' | 'normal' | 'tall'
  dismissible: boolean
  dismissHours: number
  showCountdown: boolean
  countdownEnd: string | null
  showOnMobile: boolean
}

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
    storeName: '',
    storeTagline: '',
    storeDescription: '',
    whatsappNumber: '',
    email: '',
    phone: '',
    logo: null as string | null,
    favicon: null as string | null,
    announcementText: '',
    announcementLink: '',
    social: {
      instagram: '',
      snapchat: '',
      tiktok: '',
      youtube: '',
      facebook: '',
      twitter: '',
    },
    apps: {
      appstore: '',
      googleplay: '',
    },
    paymentMethods: [] as { id: string; name: string; fee?: number; logo?: string }[],
    features: {
      guestCheckout: true,
      wishlist: true,
      reviews: true,
      reviewsRequireApproval: true,
      otpMode: 'email' as 'email' | 'phone' | 'both',
      emailOtpEnabled: true,
      phoneOtpEnabled: false,
      socialLogin: {
        google: { enabled: false, clientId: null as string | null },
        facebook: { enabled: false, clientId: null as string | null },
        apple: { enabled: false, clientId: null as string | null },
      },
    },
    googleMapsApiKey: '' as string,
    footerPages: [] as { slug: string; title: string }[],
    headerPages: [] as { slug: string; title: string }[],
    maintenance: {
      enabled: false,
      message: '',
    },
    promoBar: {
      enabled: false,
      mode: 'marquee',
      style: 'filled',
      bgColor: '#cc0000',
      textColor: '#ffffff',
      gradientFrom: '#cc0000',
      gradientTo: '#ff6600',
      message: '',
      items: null,
      icon: '',
      linkUrl: '',
      linkTarget: '_self',
      marqueeSpeed: 'medium',
      fontSize: 'sm',
      fontWeight: 'semibold',
      barHeight: 'normal',
      dismissible: true,
      dismissHours: 24,
      showCountdown: false,
      countdownEnd: null,
      showOnMobile: true,
    } as PromoBarConfig,
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

        // Store settings — use admin values directly, no hardcoded fallbacks
        storeSettings.value = {
          storeName: data.storeName ?? '',
          storeTagline: data.storeTagline ?? '',
          storeDescription: data.storeDescription ?? '',
          whatsappNumber: data.whatsappNumber ?? '',
          email: data.storeEmail ?? '',
          phone: data.storePhone ?? '',
          logo: data.logo ?? null,
          favicon: data.favicon ?? null,
          announcementText: data.announcementText ?? '',
          announcementLink: data.announcementLink ?? '',
          social: {
            instagram: data.socialLinks?.instagram ?? '',
            snapchat: data.socialLinks?.snapchat ?? '',
            tiktok: data.socialLinks?.tiktok ?? '',
            youtube: data.socialLinks?.youtube ?? '',
            facebook: data.socialLinks?.facebook ?? '',
            twitter: data.socialLinks?.twitter ?? '',
          },
          apps: {
            appstore: data.appLinks?.appstore ?? '',
            googleplay: data.appLinks?.googleplay ?? '',
          },
          paymentMethods: data.paymentMethods ?? [],
          features: {
            ...(data.features ?? storeSettings.value.features),
            otpMode: data.features?.otpMode ?? 'email',
            emailOtpEnabled: data.features?.emailOtpEnabled ?? true,
            phoneOtpEnabled: data.features?.phoneOtpEnabled ?? false,
            socialLogin: {
              google: {
                enabled: data.features?.socialLogin?.google?.enabled ?? false,
                clientId: data.features?.socialLogin?.google?.clientId ?? null,
              },
              facebook: {
                enabled: data.features?.socialLogin?.facebook?.enabled ?? false,
                clientId: data.features?.socialLogin?.facebook?.clientId ?? null,
              },
              apple: {
                enabled: data.features?.socialLogin?.apple?.enabled ?? false,
                clientId: data.features?.socialLogin?.apple?.clientId ?? null,
              },
            },
          },
          googleMapsApiKey: (data as any).googleMapsApiKey ?? '',
          footerPages: data.footerPages ?? [],
          headerPages: data.headerPages ?? [],
          maintenance: {
            enabled: data.maintenance?.enabled ?? false,
            message: data.maintenance?.message ?? '',
          },
          promoBar: (data as any).promoBar ?? storeSettings.value.promoBar,
        }

        // Dynamically set favicon
        if (storeSettings.value.favicon) {
          const link = document.querySelector("link[rel~='icon']") as HTMLLinkElement
            || document.createElement('link')
          link.rel = 'icon'
          link.href = storeSettings.value.favicon
          document.head.appendChild(link)
        }

        // Currency resolution:
        // 1. If user has a stored preference AND it still exists in the active list → keep it.
        // 2. If user has a stored preference BUT it no longer exists (admin deactivated it) → clear & apply admin default.
        // 3. If no preference stored (first visit) → apply admin default.
        const storedCurrency = localStorage.getItem('currency')
        if (storedCurrency && currencies.value[storedCurrency]) {
          // Valid stored preference — keep it
          currentCurrencyCode.value = storedCurrency
        } else {
          // Stored currency gone or first visit — apply admin default
          const adminDefault = data.defaultCurrency ?? 'SAR'
          setCurrency(adminDefault)
        }

        // Language resolution (same pattern)
        const storedLanguage = localStorage.getItem('language')
        if (!storedLanguage && data.defaultLanguage) {
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
