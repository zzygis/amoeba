import { useEffect } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { useCart } from '../cart/useCart'
import { useWishlist } from '../wishlist/useWishlist'
import AddedToCartDialog from './AddedToCartDialog'
import Logo from './Logo'
import SearchBar from './SearchBar'
import styles from './Layout.module.css'

// Page frame shared by every route: header, the current page (<Outlet />), footer.
export default function Layout() {
  const { pathname, search } = useLocation()
  const { user, logout } = useAuth()
  const { cart } = useCart()
  const { products: wishlist } = useWishlist()

  // A single-page app keeps the scroll position between "pages"; reset it like a normal site would.
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [pathname, search])

  return (
    <div className={styles.shell}>
      <header className={styles.header}>
        <div className={styles.headerInner}>
          <Link to="/" className={styles.logoLink} aria-label="Home">
            <Logo />
          </Link>

          <SearchBar />

          <nav className={styles.actions} aria-label="Account">
            <Link to="/wishlist" className={styles.cartLink} aria-label={`Wishlist, ${wishlist.length} items`}>
              <HeartIcon />
              {wishlist.length > 0 && <span className={styles.badge}>{wishlist.length}</span>}
            </Link>
            <Link to="/cart" className={styles.cartLink} aria-label={`Cart, ${cart.count} items`}>
              <CartIcon />
              {cart.count > 0 && <span className={styles.badge}>{cart.count}</span>}
            </Link>

            {user ? (
              <>
                <Link to="/account" className={styles.accountLink}>
                  My account
                </Link>
                <span className={styles.divider} aria-hidden="true">
                  |
                </span>
                <button type="button" className={`link-button ${styles.accountLink}`} onClick={logout}>
                  Log out
                </button>
              </>
            ) : (
              <>
                <Link to="/login" className={styles.accountLink}>
                  Log in
                </Link>
                <span className={styles.divider} aria-hidden="true">
                  |
                </span>
                <Link to="/register" className={styles.accountLink}>
                  Register
                </Link>
              </>
            )}
          </nav>
        </div>
      </header>

      <main className={styles.main}>
        <Outlet />
      </main>

      <footer className={styles.footer}>
        <p>
          {import.meta.env.VITE_APP_NAME} is a demo marketplace built for an internship assignment. No real payments are
          taken and the game keys are not real. Game names and images belong to their publishers; images are loaded
          from Steam.
        </p>
      </footer>

      <AddedToCartDialog />
    </div>
  )
}

function HeartIcon() {
  return (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
      <path
        d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 8 3.3 4.5 6.8 4.5c2.1 0 3.6 1.2 5.2 3 1.6-1.8 3.1-3 5.2-3 3.5 0 5.4 3.5 4.1 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"
        strokeLinejoin="round"
      />
    </svg>
  )
}

function CartIcon() {
  return (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
      <path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.76L20.5 8H6" strokeLinecap="round" strokeLinejoin="round" />
      <circle cx="9" cy="20" r="1.4" />
      <circle cx="17" cy="20" r="1.4" />
    </svg>
  )
}
