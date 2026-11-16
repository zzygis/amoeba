import { useState, type FormEvent } from 'react'
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import styles from './AdminLayout.module.css'

// The admin's sections. Each is its own page: /admin/offers, /admin/homepage, ...
const ADMIN_SECTIONS = [
  { path: 'offers', label: 'Offers' },
  { path: 'homepage', label: 'Homepage' },
  { path: 'coupons', label: 'Discount codes' },
  { path: 'orders', label: 'Orders' },
] as const

// "/admin/*": the back office, separate from the shop: its own layout (no shop header,
// cart or footer), its own sign-in, and a sidebar with the sections.
// Security is on the API: every /api/admin request checks is_admin (403 otherwise).
// This page only decides what to show.
export default function AdminLayout() {
  const { user, ready, logout } = useAuth()
  const { pathname } = useLocation()

  if (!ready) return <div className={styles.center}>Loading…</div>
  if (!user) return <AdminSignIn />
  if (!user.is_admin) {
    return (
      <div className={styles.center}>
        <div className={styles.card}>
          <h1>No access</h1>
          <p>
            You're signed in as <strong>{user.email}</strong>, which isn't an admin account.
          </p>
          <div className={styles.row}>
            <Link to="/" className="btn btn-primary">
              Go to the store
            </Link>
            <button type="button" className="btn btn-outline" onClick={logout}>
              Sign out
            </button>
          </div>
        </div>
      </div>
    )
  }

  const current = ADMIN_SECTIONS.find((section) => pathname.startsWith(`/admin/${section.path}`))

  return (
    <div className={styles.shell}>
      <aside className={styles.sidebar}>
        <Link to="/admin" className={styles.brand}>
          {import.meta.env.VITE_APP_NAME.toLowerCase()} <span>admin</span>
        </Link>
        <nav aria-label="Admin sections">
          {ADMIN_SECTIONS.map((section) => (
            <NavLink
              key={section.path}
              to={section.path}
              className={({ isActive }) => `${styles.navLink} ${isActive ? styles.active : ''}`}
            >
              {section.label}
            </NavLink>
          ))}
        </nav>
        <div className={styles.sidebarFooter}>
          <span className={styles.who} title={user.email}>
            {user.email}
          </span>
          <Link to="/" className={styles.footerLink}>
            ← View store
          </Link>
          <button type="button" className={`link-button ${styles.footerLink}`} onClick={logout}>
            Sign out
          </button>
        </div>
      </aside>

      <main className={styles.main}>
        <h1 className={styles.title}>{current?.label ?? 'Admin'}</h1>
        <Outlet />
      </main>
    </div>
  )
}

// Sign-in for /admin. Uses the same accounts and API as the shop's login; whether the
// account is an admin is decided by the server (is_admin), not by this form.
function AdminSignIn() {
  useDocumentTitle('Admin sign in')
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage(null)
    try {
      await login(email, password)
    } catch (e) {
      const error = e instanceof ApiError ? e : new ApiError(0, 'Something went wrong.')
      setErrors(error.errors)
      if (Object.keys(error.errors).length === 0) setMessage(error.message)
      setBusy(false)
    }
  }

  return (
    <div className={styles.center}>
      <form className={styles.card} onSubmit={submit} noValidate>
        <p className={styles.brand}>
          {import.meta.env.VITE_APP_NAME.toLowerCase()} <span>admin</span>
        </p>
        <h1>Sign in</h1>
        {message && (
          <p className="alert" role="alert">
            {message}
          </p>
        )}
        <FormField
          name="email"
          label="Email"
          type="email"
          autoComplete="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          errors={errors.email}
        />
        <FormField
          name="password"
          label="Password"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          errors={errors.password}
        />
        <button type="submit" className="btn btn-primary btn-block" disabled={busy}>
          {busy ? 'Signing in…' : 'Sign in'}
        </button>
        <Link to="/" className={styles.footerLink}>
          ← Back to the store
        </Link>
      </form>
    </div>
  )
}
