import { useState } from 'react'
import { ApiError } from '../api/client'
import { useCart, type AddTarget } from '../cart/useCart'

// Wraps cart.add() with a busy flag and a readable error ("Only 2 keys left from this seller.").
export function useAddToCart() {
  const { add } = useCart()
  const [adding, setAdding] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function addToCart(target: AddTarget, options?: { showDialog?: boolean }): Promise<boolean> {
    setAdding(true)
    setError(null)
    try {
      await add(target, options)
      return true
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not add to cart.')
      return false
    } finally {
      setAdding(false)
    }
  }

  return { addToCart, adding, error }
}
