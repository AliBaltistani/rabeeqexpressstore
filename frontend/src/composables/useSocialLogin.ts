/**
 * useSocialLogin — Composable for Google, Facebook, and Apple OAuth
 *
 * Strategy: load each provider's SDK on demand (lazy), trigger the
 * popup / sign-in sheet, and return the raw access_token (or id_token
 * for Apple) that the backend SocialLoginController expects.
 *
 * The composable does NOT call the backend — callers do that via
 * authStore.socialLogin(provider, token, name?).
 */

// ─── Helpers ────────────────────────────────────────────────────────────────

function loadScript(src: string, id: string): Promise<void> {
    return new Promise((resolve, reject) => {
        if (document.getElementById(id)) { resolve(); return }
        const s = document.createElement('script')
        s.id = id
        s.src = src
        s.async = true
        s.onload = () => resolve()
        s.onerror = () => reject(new Error(`Failed to load script: ${src}`))
        document.head.appendChild(s)
    })
}

// ─── Google ─────────────────────────────────────────────────────────────────

/**
 * Trigger Google One-Tap / popup sign-in using Google Identity Services.
 * Returns the credential JWT (id_token) on success.
 * The backend uses userFromToken() which for Google accepts id_tokens.
 */
export function useGoogleLogin(clientId: string) {
    async function loginWithGoogle(): Promise<{ token: string; name?: string }> {
        await loadScript('https://accounts.google.com/gsi/client', 'google-gsi-sdk')

        return new Promise((resolve, reject) => {
            const google = (window as any).google
            if (!google?.accounts?.id) {
                reject(new Error('Google Identity Services failed to load'))
                return
            }

            let settled = false

            google.accounts.id.initialize({
                client_id: clientId,
                callback: (response: { credential: string }) => {
                    if (settled) return
                    settled = true
                    if (response.credential) {
                        // Decode name from JWT payload (no library needed)
                        try {
                            const payload = JSON.parse(atob(response.credential.split('.')[1]))
                            resolve({ token: response.credential, name: payload.name })
                        } catch {
                            resolve({ token: response.credential })
                        }
                    } else {
                        reject(new Error('Google sign-in was cancelled'))
                    }
                },
                cancel_on_tap_outside: true,
            })

            // Use popup flow instead of One Tap for reliability
            google.accounts.id.prompt((notification: any) => {
                if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                    if (!settled) {
                        settled = true
                        reject(new Error('Google sign-in popup was dismissed or blocked'))
                    }
                }
            })
        })
    }

    return { loginWithGoogle }
}

// ─── Facebook ────────────────────────────────────────────────────────────────

/**
 * Trigger Facebook Login popup.
 * Returns the FB access_token on success.
 */
export function useFacebookLogin(appId: string) {
    async function loginWithFacebook(): Promise<{ token: string }> {
        await loadScript('https://connect.facebook.net/en_US/sdk.js', 'facebook-jssdk')

        return new Promise((resolve, reject) => {
            const FB = (window as any).FB
            if (!FB) {
                reject(new Error('Facebook SDK failed to load'))
                return
            }

            // Init only once
            if (!(window as any).__fbInitialized) {
                FB.init({ appId, cookie: true, xfbml: false, version: 'v19.0' })
                    ; (window as any).__fbInitialized = true
            }

            FB.login((response: any) => {
                if (response.authResponse?.accessToken) {
                    resolve({ token: response.authResponse.accessToken })
                } else {
                    reject(new Error('Facebook login was cancelled'))
                }
            }, { scope: 'email,public_profile' })
        })
    }

    return { loginWithFacebook }
}

// ─── Apple ───────────────────────────────────────────────────────────────────

/**
 * Trigger Apple Sign-In popup.
 * Returns the id_token on success.
 */
export function useAppleLogin(clientId: string) {
    async function loginWithApple(): Promise<{ token: string; name?: string }> {
        await loadScript(
            'https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js',
            'apple-signin-sdk'
        )

        return new Promise((resolve, reject) => {
            const AppleID = (window as any).AppleID
            if (!AppleID?.auth) {
                reject(new Error('Apple Sign-In SDK failed to load'))
                return
            }

            AppleID.auth.init({
                clientId,
                scope: 'name email',
                redirectURI: window.location.origin,
                usePopup: true,
            })

            AppleID.auth.signIn()
                .then((response: any) => {
                    const token = response.authorization?.id_token
                    if (!token) { reject(new Error('No id_token from Apple')); return }
                    const firstName = response.user?.name?.firstName ?? ''
                    const lastName = response.user?.name?.lastName ?? ''
                    const name = [firstName, lastName].filter(Boolean).join(' ') || undefined
                    resolve({ token, name })
                })
                .catch(() => reject(new Error('Apple sign-in was cancelled')))
        })
    }

    return { loginWithApple }
}
