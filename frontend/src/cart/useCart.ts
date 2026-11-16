import { createContext, useContext } from 'react'
import type { Cart, Coupon } from '../types'

// Must match CartItem::MAX_QUANTITY in the backend.
export const MAX_QUANTITY = 10

// What's being added: a seller's offer, plus names for the "Added to cart" dialog.
export interface AddTarget {
  offerId: number
  name: string // "Elden Ring Steam Key GLOBAL"
  seller?: string
}

export interface CartContextValue {
  cart: Cart
  loading: boolean
  quantityOf: (offerId: number) => number
  add: (target: AddTarget, options?: { showDialog?: boolean }) => Promise<void>
  setQuantity: (offerId: number, quantity: number) => Promise<void>
  remove: (offerId: number) => Promise<void>
  refresh: () => Promise<void>
  // Discount code applied in the cart. Only a preview: the server re-checks it at checkout.
  coupon: Coupon | null
  discountCents: number
  totalCents: number // cart total minus the discount
  applyCoupon: (code: string) => Promise<void>
  removeCoupon: () => void
  // What was just added, for the "Added to cart" dialog (null = dialog closed).
  justAdded: AddTarget | null
  closeAddedDialog: () => void
}

// Filled in by <CartProvider> (CartProvider.tsx).
export const CartContext = createContext<CartContextValue | null>(null)

export function useCart(): CartContextValue {
  const value = useContext(CartContext)
  if (!value) throw new Error('useCart must be used inside <CartProvider>')
  return value
}
