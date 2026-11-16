import { Link } from 'react-router-dom'
import { useAddToCart } from '../hooks/useAddToCart'
import type { Product } from '../types'
import Price from './Price'
import WishlistButton from './WishlistButton'
import styles from './ProductCard.module.css'

// One game in the results grid, styled like eneba.com's search results.
// The title link covers the whole card; the "Add to cart" button sits on top of it.
export default function ProductCard({ product }: { product: Product }) {
  const { addToCart, adding, error } = useAddToCart()
  // The best offer (cheapest seller with keys). None left = sold out.
  const offerId = product.offer_id
  const price = product.price_cents

  return (
    <article className={styles.card}>
      <div className={`${styles.cover} ${product.cover_portrait ? '' : styles.wide}`}>
        {/* A wide image (some DLC) is shown whole, over a blurred copy that fills the frame. */}
        {!product.cover_portrait && <img src={product.cover_url} alt="" aria-hidden="true" className={styles.backdrop} />}
        <img src={product.cover_url} alt="" loading="lazy" width={600} height={900} />
        <WishlistButton product={product} className={styles.heart} />
      </div>

      <div className={styles.body}>
        <p className={styles.platform}>
          {product.platform}
          {product.type !== 'game' && <span className={styles.typeTag}>{product.type === 'dlc' ? 'DLC' : 'Software'}</span>}
        </p>
        <h3 className={styles.title}>
          <Link to={`/games/${product.slug}`} className={styles.link}>
            {product.name}
          </Link>
        </h3>
        <p className={styles.region}>{product.region}</p>

        <div className={styles.bottom}>
          {offerId === null || price === null ? (
            <p className={styles.soldOut}>Out of stock</p>
          ) : (
            <>
              <span className={styles.from}>From</span>
              <Price {...product} price_cents={price} />
            </>
          )}
        </div>

        {offerId !== null && (
          <div className={styles.hoverActions}>
            <button
              type="button"
              className="btn btn-primary btn-block"
              disabled={adding}
              onClick={() => addToCart({ offerId, name: product.name })}
            >
              {adding ? 'Adding…' : 'Add to cart'}
            </button>
            {error && (
              <p className={styles.error} role="alert">
                {error}
              </p>
            )}
          </div>
        )}
      </div>
    </article>
  )
}

// Placeholder shown while the first page of results loads.
export function ProductCardSkeleton() {
  return (
    <div className={`${styles.card} ${styles.skeleton}`} aria-hidden="true">
      <div className={styles.cover} />
      <div className={styles.body}>
        <div className={styles.skeletonLine} />
        <div className={styles.skeletonLine} style={{ width: '40%' }} />
      </div>
    </div>
  )
}
