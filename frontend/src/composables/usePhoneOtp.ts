/**
 * Shared phone OTP utilities — single source of truth for all pages.
 * Used by: LoginModal, LoginPage, ProfilePage
 */

/**
 * Normalize a phone number to E.164 format.
 * @param countryCode  Country dial code with or without '+' (e.g. '+966' or '966')
 * @param localNumber  Local phone number (may start with 0, will be stripped)
 * @returns E.164 string (e.g. '+966501234567') or null if invalid
 */
export function normalizePhoneNumber(countryCode: string, localNumber: string): string | null {
    const cleanPhone = localNumber.trim().replace(/^0+/, '')
    if (!cleanPhone) return null

    const code = countryCode.startsWith('+') ? countryCode : '+' + countryCode
    const fullPhone = code + cleanPhone

    // Validate E.164 format: +[1-9] followed by 1–14 digits
    if (!/^\+[1-9]\d{1,14}$/.test(fullPhone)) {
        return null
    }

    return fullPhone
}
