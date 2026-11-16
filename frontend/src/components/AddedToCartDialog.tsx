import { useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { useCart } from '../cart/useCart'
import { plural } from '../lib/format'
import styles from './AddedToCartDialog.module.css'

// "Added to cart" popup shown after adding a product, like on eneba.com.
// Uses the native <dialog> element: it traps focus and closes on Escape for free.
export default function AddedToCartDialog() {
  const { justAdded, closeAddedDialog, cart } = useCart()
  const dialogRef = useRef<HTMLDialogElement>(null)
  const navigate = useNavigate()

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (justAdded && !dialog.open) dialog.showModal()
    if (!justAdded && dialog.open) dialog.close()
  }, [justAdded])

  function viewCart() {
    closeAddedDialog()
    navigate('/cart')
  }

  return (
    <dialog
      ref={dialogRef}
      className={styles.dialog}
      aria-labelledby="added-title"
      onClose={closeAddedDialog}
      // Clicking the dark backdrop (outside the box) closes it.
      onClick={(event) => event.target === dialogRef.current && closeAddedDialog()}
    >
      <div className={styles.box}>
        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
          <path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.76L20.5 8H6" strokeLinecap="round" strokeLinejoin="round" />
          <circle cx="9" cy="20" r="1.4" />
          <circle cx="17" cy="20" r="1.4" />
          <path d="M10 10.5l2 2 3.5-3.5" stroke="var(--color-success)" strokeWidth="2" strokeLinecap="round" />
        </svg>
        <h2 id="added-title" className={styles.title}>
          Added to cart
        </h2>
        {justAdded && <p className={styles.product}>{justAdded.name}</p>}
        {justAdded?.seller && <p className={styles.count}>Sold by {justAdded.seller}</p>}
        <p className={styles.count}>You have {plural(cart.count, 'item')} in your cart</p>
        <div className={styles.buttons}>
          <button type="button" className="btn btn-primary" onClick={closeAddedDialog}>
            Continue shopping
          </button>
          <button type="button" className="btn btn-outline" onClick={viewCart}>
            View cart
          </button>
        </div>
      </div>
    </dialog>
  )
}
