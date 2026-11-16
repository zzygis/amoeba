import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import { MAX_QUANTITY, useCart } from '../cart/useCart'
import CheckoutSteps from '../components/CheckoutSteps'
import SellerAvatar from '../components/SellerAvatar'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { formatPrice, plural } from '../lib/format'
import type { CartLine } from '../types'
import styles from './Checkout.module.css'

// Step 1 of checkout: review the cart.
export default function CartPage() {
  useDocumentTitle('Your cart')
  const { cart, loading, coupon, discountCents, totalCents } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()

  // Lines asking for more keys than are left can't be checked out.
  const hasStockProblem = cart.items.some((line) => line.quantity > line.offer.stock)

  function proceed() {
    // Guests log in (or register) first; the login page brings them back to /checkout.
    navigate(user ? '/checkout' : `/login?redirect=${encodeURIComponent('/checkout')}`)
  }

  if (loading) return <p>Loading your cart…</p>

  if (cart.items.length === 0) {
    return (
      <>
        <CheckoutSteps current={1} />
        <div className={styles.empty}>
          <h1>Your cart is empty</h1>
          <p className={styles.muted}>Find something to play and it will show up here.</p>
          <Link to="/" className="btn btn-primary">
            Browse games
          </Link>
        </div>
      </>
    )
  }

  return (
    <>
      <CheckoutSteps current={1} />
      <div className={styles.layout}>
        <section className={styles.panel}>
          <h1>My cart</h1>
          <ul className={styles.lines}>
            {cart.items.map((line) => (
              <CartLineRow key={line.offer.id} line={line} />
            ))}
          </ul>
        </section>

        <aside className={`${styles.panel} ${styles.summary}`} aria-label="Summary">
          <h2>Summary</h2>
          <div className={styles.summaryRow}>
            <span>{plural(cart.count, 'product')}</span>
            <span>{formatPrice(cart.total_cents)}</span>
          </div>
          {coupon && (
            <div className={styles.summaryRow}>
              <span>Discount ({coupon.code}, -{coupon.percent_off}%)</span>
              <span className={styles.good}>-{formatPrice(discountCents)}</span>
            </div>
          )}
          <div className={styles.totalRow}>
            <span>Total:</span>
            <strong>{formatPrice(totalCents)}</strong>
          </div>
          <button type="button" className="btn btn-primary btn-block" disabled={hasStockProblem} onClick={proceed}>
            Proceed to checkout
          </button>
          {hasStockProblem && <p className={styles.warning}>Lower the quantities marked in red to continue.</p>}
          {!user && <p className={styles.muted}>You'll log in or create an account next. Your cart is kept.</p>}
          <CouponBox />
        </aside>
      </div>
    </>
  )
}

// "Got discount code?" (try WELCOME10 or AMOEBA20 from the README).
function CouponBox() {
  const { coupon, applyCoupon, removeCoupon } = useCart()
  const [code, setCode] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function apply(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    try {
      await applyCoupon(code)
      setCode('')
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not check the code.')
    } finally {
      setBusy(false)
    }
  }

  if (coupon) {
    return (
      <p className={styles.couponApplied}>
        Code <strong>{coupon.code}</strong> applied.{' '}
        <button type="button" className="link-button" onClick={removeCoupon}>
          Remove
        </button>
      </p>
    )
  }

  return (
    <details className={styles.coupon}>
      <summary>Got discount code?</summary>
      <form onSubmit={apply} className={styles.couponForm}>
        <label htmlFor="coupon" className="visually-hidden">
          Discount code
        </label>
        <input
          id="coupon"
          value={code}
          maxLength={32}
          autoComplete="off"
          placeholder="Enter code"
          onChange={(e) => setCode(e.target.value)}
          aria-invalid={Boolean(error)}
          aria-describedby={error ? 'coupon-error' : undefined}
        />
        <button type="submit" className="btn btn-outline" disabled={busy || code.trim() === ''}>
          Apply
        </button>
      </form>
      {error && (
        <p id="coupon-error" className="field-error">
          {error}
        </p>
      )}
    </details>
  )
}

function CartLineRow({ line }: { line: CartLine }) {
  const { setQuantity, remove } = useCart()
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { product, offer, quantity } = line
  const maxAllowed = Math.min(offer.stock, MAX_QUANTITY)

  async function run(action: () => Promise<void>) {
    setBusy(true)
    setError(null)
    try {
      await action()
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Something went wrong.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <li className={styles.line}>
      <img src={product.cover_url} alt="" width={64} height={96} />
      <div className={styles.lineInfo}>
        <Link to={`/games/${product.slug}`}>{product.name}</Link>
        <span className={`${styles.muted} ${styles.sellerLine}`}>
          <SellerAvatar name={offer.seller.name} imageUrl={offer.seller.avatar_url} size={20} />
          Sold by <strong>{offer.seller.name}</strong> · {formatPrice(offer.price_cents)} each
        </span>
        {offer.stock === 0 ? (
          <span className={styles.warning}>This seller has sold out. Remove it or pick another seller.</span>
        ) : (
          quantity > offer.stock && (
            <span className={styles.warning}>This seller has only {plural(offer.stock, 'key')} left.</span>
          )
        )}
        {error && (
          <span className={styles.warning} role="alert">
            {error}
          </span>
        )}
      </div>
      <div className={styles.lineEnd}>
        <span className={styles.lineTotal}>{formatPrice(line.line_total_cents)}</span>
        <div className={styles.stepper}>
          <button
            type="button"
            aria-label={`One less ${product.name}`}
            disabled={busy || quantity <= 1}
            onClick={() => run(() => setQuantity(offer.id, quantity - 1))}
          >
            −
          </button>
          <span aria-label="Quantity">{quantity}</span>
          <button
            type="button"
            aria-label={`One more ${product.name}`}
            disabled={busy || quantity >= maxAllowed}
            onClick={() => run(() => setQuantity(offer.id, quantity + 1))}
          >
            +
          </button>
        </div>
        <button type="button" className="link-button" disabled={busy} onClick={() => run(() => remove(offer.id))}>
          Remove
        </button>
      </div>
    </li>
  )
}
