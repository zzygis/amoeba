import { createContext, useContext } from 'react'
import type { Product } from '../types'

export interface WishlistContextValue {
  products: Product[] // saved products, newest first (empty for guests)
  has: (productId: number) => boolean
  // Saves or un-saves. Guests are sent to the login page instead.
  toggle: (product: Product) => Promise<void>
}

// Filled in by <WishlistProvider> (WishlistProvider.tsx).
export const WishlistContext = createContext<WishlistContextValue | null>(null)

export function useWishlist(): WishlistContextValue {
  const value = useContext(WishlistContext)
  if (!value) throw new Error('useWishlist must be used inside <WishlistProvider>')
  return value
}
