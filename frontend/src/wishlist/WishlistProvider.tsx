import { useCallback, useEffect, useState, type ReactNode } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { apiRequest, apiSend } from '../api/client'
import { useAuth } from '../auth/useAuth'
import type { Product } from '../types'
import { WishlistContext } from './useWishlist'

// The logged-in user's wishlist (GET /wishlist), shared by every heart button.
export function WishlistProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [products, setProducts] = useState<Product[]>([])
  const userId = user?.id ?? null

  const load = useCallback(async (): Promise<Product[]> => {
    if (userId === null) return []
    return (await apiRequest<{ data: Product[] }>('/wishlist')).data
  }, [userId])

  // Reload on login/logout.
  useEffect(() => {
    let cancelled = false
    load()
      .then((loaded) => !cancelled && setProducts(loaded))
      .catch(() => !cancelled && setProducts([]))
    return () => {
      cancelled = true
    }
  }, [load])

  const has = (productId: number) => products.some((product) => product.id === productId)

  async function toggle(product: Product) {
    if (userId === null) {
      navigate(`/login?redirect=${encodeURIComponent(location.pathname + location.search)}`)
      return
    }
    // Update the heart straight away, then confirm with the server (and undo if it fails).
    const saved = has(product.id)
    setProducts((current) => (saved ? current.filter((p) => p.id !== product.id) : [product, ...current]))
    try {
      await apiSend(saved ? 'DELETE' : 'PUT', `/wishlist/${product.id}`)
    } catch {
      setProducts(await load().catch(() => products))
    }
  }

  return <WishlistContext.Provider value={{ products, has, toggle }}>{children}</WishlistContext.Provider>
}
