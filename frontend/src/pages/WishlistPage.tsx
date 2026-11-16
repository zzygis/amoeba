import { Link } from 'react-router-dom'
import ProductCard from '../components/ProductCard'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { useWishlist } from '../wishlist/useWishlist'
import styles from './StorePage.module.css'

// "/wishlist": products saved with the heart button (login required, see App.tsx).
export default function WishlistPage() {
  useDocumentTitle('Wishlist')
  const { products } = useWishlist()

  return (
    <section>
      <h1>Wishlist</h1>
      {products.length === 0 ? (
        <div className={styles.empty}>
          <p>Nothing saved yet. Tap the heart on any game to keep it here.</p>
          <Link to="/store" className="btn btn-primary">
            Browse games
          </Link>
        </div>
      ) : (
        <ul className={styles.grid}>
          {products.map((product) => (
            <li key={product.id}>
              <ProductCard product={product} />
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
