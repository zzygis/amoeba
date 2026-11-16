import type { Product } from '../types'
import { useWishlist } from '../wishlist/useWishlist'
import styles from './WishlistButton.module.css'

// Heart toggle. aria-pressed tells screen readers whether it's saved.
export default function WishlistButton({ product, className = '' }: { product: Product; className?: string }) {
  const { has, toggle } = useWishlist()
  const saved = has(product.id)

  return (
    <button
      type="button"
      className={`${styles.button} ${saved ? styles.saved : ''} ${className}`}
      aria-pressed={saved}
      aria-label={saved ? `Remove ${product.name} from wishlist` : `Add ${product.name} to wishlist`}
      title={saved ? 'Remove from wishlist' : 'Add to wishlist'}
      onClick={() => toggle(product)}
    >
      <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
        <path
          d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 8 3.3 4.5 6.8 4.5c2.1 0 3.6 1.2 5.2 3 1.6-1.8 3.1-3 5.2-3 3.5 0 5.4 3.5 4.1 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"
          strokeWidth="2"
          strokeLinejoin="round"
        />
      </svg>
    </button>
  )
}
