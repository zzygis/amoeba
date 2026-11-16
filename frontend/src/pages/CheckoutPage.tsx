import { useState, type FormEvent } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { ApiError, apiSend } from '../api/client'
import { useCart } from '../cart/useCart'
import CheckoutSteps from '../components/CheckoutSteps'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { formatPrice } from '../lib/format'
import type { Order, PaymentMethod, Wrapped } from '../types'
import styles from './Checkout.module.css'

// Step 2 of checkout: pick a (demo) payment method and pay. Only for logged-in users (see App.tsx).
export default function CheckoutPage() {
  useDocumentTitle('Payment')
  const { cart, loading, refresh, coupon, discountCents, totalCents, removeCoupon } = useCart()
  const navigate = useNavigate()
  const [method, setMethod] = useState<PaymentMethod>('card')
  const [paying, setPaying] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function pay(event: FormEvent) {
    event.preventDefault()
    setPaying(true)
    setError(null)
    try {
      const order = (
        await apiSend<Wrapped<Order>>('POST', '/checkout', { payment_method: method, coupon_code: coupon?.code ?? null })
      ).data
      removeCoupon()
      await refresh() // the server emptied the cart
      navigate(`/orders/${order.id}`, { state: { justPaid: true } })
    } catch (e) {
      // e.g. "Only 1 key left for Elden Ring Steam Key GLOBAL. Please lower the quantity."
      setError(e instanceof ApiError ? e.message : 'Payment failed. Please try again.')
      // The code stopped working since it was applied (expired/used up): drop it so the total is right.
      if (e instanceof ApiError && e.errors.coupon_code) removeCoupon()
      await refresh().catch(() => {})
      setPaying(false)
    }
  }

  if (loading) return <p>Loading…</p>
  // Nothing to pay for (e.g. opened /checkout directly): back to the cart.
  if (cart.items.length === 0 && !paying) return <Navigate to="/cart" replace />

  return (
    <>
      <CheckoutSteps current={2} />
      <form className={styles.layout} onSubmit={pay}>
        <section className={styles.panel}>
          <h1>Payment method</h1>
          <fieldset className={styles.methods}>
            <legend className="visually-hidden">Payment method</legend>
            <label className={styles.method}>
              <input type="radio" name="method" value="card" checked={method === 'card'} onChange={() => setMethod('card')} />
              Card
            </label>
            <label className={styles.method}>
              <input
                type="radio"
                name="method"
                value="paypal"
                checked={method === 'paypal'}
                onChange={() => setMethod('paypal')}
              />
              PayPal
            </label>
          </fieldset>
          <p className={styles.note}>
            This is a demo store: no real payment is taken and no payment details are asked for. Pressing “Pay” places
            the order straight away.
          </p>

          <h2 style={{ marginTop: 'var(--space-6)' }}>Your order</h2>
          <ul className={styles.lines}>
            {cart.items.map((line) => (
              <li key={line.offer.id} className={styles.line}>
                <img src={line.product.cover_url} alt="" width={64} height={96} />
                <div className={styles.lineInfo}>
                  <strong>{line.product.name}</strong>
                  <span className={styles.muted}>
                    {line.quantity} × {formatPrice(line.offer.price_cents)} · sold by {line.offer.seller.name}
                  </span>
                </div>
                <div className={styles.lineEnd}>
                  <span className={styles.lineTotal}>{formatPrice(line.line_total_cents)}</span>
                </div>
              </li>
            ))}
          </ul>
        </section>

        <aside className={`${styles.panel} ${styles.summary}`} aria-label="Summary">
          <h2>Summary</h2>
          <div className={styles.summaryRow}>
            <span>Subtotal</span>
            <span>{formatPrice(cart.total_cents)}</span>
          </div>
          {coupon && (
            <div className={styles.summaryRow}>
              <span>Discount ({coupon.code})</span>
              <span className={styles.good}>-{formatPrice(discountCents)}</span>
            </div>
          )}
          <div className={styles.totalRow}>
            <span>Total:</span>
            <strong>{formatPrice(totalCents)}</strong>
          </div>
          {error && (
            <p className="alert" role="alert">
              {error}
            </p>
          )}
          <button type="submit" className="btn btn-primary btn-block" disabled={paying}>
            {paying ? 'Processing…' : `Pay ${formatPrice(totalCents)}`}
          </button>
          <Link to="/cart" className={styles.muted}>
            ← Back to cart
          </Link>
        </aside>
      </form>
    </>
  )
}
