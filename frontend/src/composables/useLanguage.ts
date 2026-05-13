import { useSettingsStore } from '@/stores/settingsStore'
import i18n from '@/i18n'

export function useLanguage() {
  const settings = useSettingsStore()

  /**
   * Switch the active language.
   * - Updates the Pinia store
   * - Updates vue-i18n locale
   * - Sets <html lang> and <html dir> attributes
   * - Saves to localStorage
   */
  function switchLanguage(code: string) {
    settings.setLanguage(code)

    // Update vue-i18n locale
    const i18nGlobal = i18n.global as any
    i18nGlobal.locale.value = code

    // Set document direction
    const lang = settings.currentLanguage
    if (lang) {
      document.documentElement.lang = lang.code
      document.documentElement.dir = lang.direction === 'rtl' ? 'rtl' : 'ltr'
    }
  }

  /**
   * Initialize language direction on app load.
   */
  function initLanguage() {
    const lang = settings.currentLanguage
    if (lang) {
      document.documentElement.lang = lang.code
      document.documentElement.dir = lang.direction === 'rtl' ? 'rtl' : 'ltr'
    }

    // Sync vue-i18n locale
    const i18nGlobal = i18n.global as any
    i18nGlobal.locale.value = settings.currentLanguageCode
  }

  return { switchLanguage, initLanguage }
}
