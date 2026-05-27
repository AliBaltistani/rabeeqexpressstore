/**
 * Global action animations composable.
 * Provides reusable fly-to-cart and pulse animations for any component.
 */

/**
 * Fly a clone of `sourceEl` to the cart icon in the header, then bounce the cart icon.
 */
export function flyToCart(sourceEl: HTMLElement | null): void {
  if (!sourceEl) return

  // Locate cart icon in the header
  const cartIcon = (
    document.querySelector('.action-btn[aria-label="Cart"]') ||
    document.querySelector('[aria-label="Cart"]') ||
    document.querySelector('.cart-badge')?.parentElement
  ) as HTMLElement | null
  if (!cartIcon) return

  const srcRect = sourceEl.getBoundingClientRect()
  const cartRect = cartIcon.getBoundingClientRect()

  // Create flying clone
  const clone = sourceEl.cloneNode(true) as HTMLElement
  clone.style.cssText = `
    position: fixed;
    top: ${srcRect.top}px;
    left: ${srcRect.left}px;
    width: ${srcRect.width}px;
    height: ${srcRect.height}px;
    object-fit: contain;
    z-index: 10000;
    pointer-events: none;
    border-radius: 8px;
    transition: all 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  `
  document.body.appendChild(clone)

  // Force reflow then animate to cart
  clone.getBoundingClientRect()
  requestAnimationFrame(() => {
    clone.style.top = `${cartRect.top + cartRect.height / 2 - 10}px`
    clone.style.left = `${cartRect.left + cartRect.width / 2 - 10}px`
    clone.style.width = '20px'
    clone.style.height = '20px'
    clone.style.opacity = '0.3'
    clone.style.transform = 'scale(0.2)'
  })

  // Bounce the cart icon when clone arrives, then clean up
  setTimeout(() => {
    clone.remove()
    cartIcon.style.transition = 'transform 0.3s ease'
    cartIcon.style.transform = 'scale(1.3)'
    setTimeout(() => {
      cartIcon.style.transform = 'scale(1)'
    }, 300)
  }, 700)
}

/**
 * Quick scale-bounce micro-animation on any element (icon buttons, etc.).
 */
export function pulseElement(el: HTMLElement | null): void {
  if (!el) return
  el.style.transition = 'transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1)'
  el.style.transform = 'scale(1.35)'
  setTimeout(() => {
    el.style.transform = 'scale(1)'
  }, 250)
}
