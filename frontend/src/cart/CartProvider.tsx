import { useCallback, useEffect, useState, type ReactNode } from 'react'
import { ApiError, apiRequest, apiSend } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { discountCents } from '../lib/discount'
import type { Cart, Coupon, Wrapped } from '../types'
import { CartContext, MAX_QUANTITY, type AddTarget } from './useCart'

const GUEST_CART_KEY = 'guest_cart'
const COUPON_KEY = 'coupon'
const EMPTY_CART: Cart = { items: [], count: 0, total_cents: 0 }

// A guest's cart line: a seller's offer and how many keys.
interface GuestItem {
  offer_id: number
  quantity: number
}

// Guests: only [{offer_id, quantity}] lives in localStorage. Prices, names and
// stock always come from the API (/cart/preview), so they're never stale or faked.
function readGuestCart(): GuestItem[] {
  try {
    const parsed = JSON.parse(localStorage.getItem(GUEST_CART_KEY) ?? '[]')
    // Keep only well-formed lines (e.g. drops carts saved by an older version of the site).
    return Array.isArray(parsed)
      ? parsed.filter((item) => Number.isInteger(item?.offer_id) && Number.isInteger(item?.quantity))
      : []
  } catch {
    return []
  }
}

function writeGuestCart(items: GuestItem[]) {
  try {
    if (items.length) localStorage.setItem(GUEST_CART_KEY, JSON.stringify(items))
    else localStorage.removeItem(GUEST_CART_KEY)
  } catch {
    // Storage blocked (private mode): the guest cart just won't survive a reload.
  }
}

function readCoupon(): Coupon | null {
  try {
    return JSON.parse(localStorage.getItem(COUPON_KEY) ?? 'null')
  } catch {
    return null
  }
}

function writeCoupon(coupon: Coupon | null) {
  try {
    if (coupon) localStorage.setItem(COUPON_KEY, JSON.stringify(coupon))
    else localStorage.removeItem(COUPON_KEY)
  } catch {
    // ignore: the code just won't survive a reload
  }
}

async function previewGuestCart(items: GuestItem[]): Promise<Cart> {
  if (items.length === 0) return EMPTY_CART
  return (await apiSend<Wrapped<Cart>>('POST', '/cart/preview', { items })).data
}

// Logged in: the cart lives in the database (cart_items), via the /cart endpoints.
// Guest: localStorage. On login, the guest cart is merged into the account's cart.
export function CartProvider({ children }: { children: ReactNode }) {
  const { user, ready } = useAuth()
  const [cart, setCart] = useState<Cart>(EMPTY_CART)
  const [loading, setLoading] = useState(true)
  const [justAdded, setJustAdded] = useState<AddTarget | null>(null)
  const [coupon, setCoupon] = useState<Coupon | null>(readCoupon)
  const userId = user?.id ?? null

  const load = useCallback(async (): Promise<Cart> => {
    if (userId === null) return previewGuestCart(readGuestCart())

    const guestItems = readGuestCart()
    if (guestItems.length === 0) return (await apiRequest<Wrapped<Cart>>('/cart')).data

    // Just logged in with things in the guest cart: move them into the account.
    const merged = (await apiSend<Wrapped<Cart>>('POST', '/cart/merge', { items: guestItems })).data
    writeGuestCart([])
    return merged
  }, [userId])

  // (Re)load whenever the logged-in user changes: page load, login, logout.
  useEffect(() => {
    if (!ready) return
    let cancelled = false
    load()
      .then((loaded) => !cancelled && setCart(loaded))
      .catch(() => !cancelled && setCart(EMPTY_CART))
      .finally(() => !cancelled && setLoading(false))
    return () => {
      cancelled = true
    }
  }, [ready, load])

  const refresh = useCallback(async () => setCart(await load()), [load])

  function quantityOf(offerId: number): number {
    return cart.items.find((line) => line.offer.id === offerId)?.quantity ?? 0
  }

  async function setQuantity(offerId: number, quantity: number) {
    if (userId !== null) {
      // The server checks this seller's stock and answers 422 with a message if it's too much.
      const response = await apiSend<Wrapped<Cart>>('PUT', `/cart/items/${offerId}`, { quantity })
      setCart(response.data)
      return
    }

    // Guest: save, re-price on the server, and undo if that seller doesn't have enough keys.
    // (The server checks again at checkout, so this is for a friendly message, not security.)
    const before = readGuestCart()
    const items = before.map((item) => ({ ...item }))
    const existing = items.find((item) => item.offer_id === offerId)
    if (existing) existing.quantity = quantity // in place, so the line keeps its position
    else items.push({ offer_id: offerId, quantity })

    const priced = await previewGuestCart(items)
    const line = priced.items.find((l) => l.offer.id === offerId)
    if (!line || quantity > line.offer.stock) {
      const left = line?.offer.stock ?? 0
      throw new ApiError(422, left === 0 ? 'This offer is out of stock.' : `Only ${left} keys left from this seller.`)
    }
    writeGuestCart(items)
    setCart(priced)
  }

  // Adds one key of an offer. "Buy now" passes showDialog: false because it goes straight to the cart.
  async function add(target: AddTarget, { showDialog = true }: { showDialog?: boolean } = {}) {
    const wanted = quantityOf(target.offerId) + 1
    if (wanted > MAX_QUANTITY) {
      throw new ApiError(422, `You can buy at most ${MAX_QUANTITY} keys from one offer.`)
    }
    await setQuantity(target.offerId, wanted)
    if (showDialog) setJustAdded(target)
  }

  async function applyCoupon(code: string) {
    const checked = (await apiSend<Wrapped<Coupon>>('POST', '/coupons/check', { code })).data
    setCoupon(checked)
    writeCoupon(checked)
  }

  function removeCoupon() {
    setCoupon(null)
    writeCoupon(null)
  }

  const discount = coupon ? discountCents(cart.total_cents, coupon.percent_off) : 0

  async function remove(offerId: number) {
    if (userId !== null) {
      const response = await apiSend<Wrapped<Cart>>('DELETE', `/cart/items/${offerId}`)
      setCart(response.data)
      return
    }
    const items = readGuestCart().filter((item) => item.offer_id !== offerId)
    writeGuestCart(items)
    setCart(await previewGuestCart(items))
  }

  return (
    <CartContext.Provider
      value={{
        cart,
        loading,
        quantityOf,
        add,
        setQuantity,
        remove,
        refresh,
        coupon,
        discountCents: discount,
        totalCents: cart.total_cents - discount,
        applyCoupon,
        removeCoupon,
        justAdded,
        closeAddedDialog: () => setJustAdded(null),
      }}
    >
      {children}
    </CartContext.Provider>
  )
}
