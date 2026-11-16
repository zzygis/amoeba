import { useState, type FormEvent } from 'react'
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { safeRedirect } from '../lib/redirect'
import styles from './Auth.module.css'

export default function LoginPage() {
  useDocumentTitle('Log in')
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const redirect = safeRedirect(params.get('redirect'))

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  // Already logged in (e.g. pressed Back after logging in): skip the form.
  if (user && !submitting) return <Navigate to={redirect} replace />

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setSubmitting(true)
    setErrors({})
    setMessage(null)
    try {
      await login(email, password)
      navigate(redirect, { replace: true })
    } catch (e) {
      const error = e instanceof ApiError ? e : new ApiError(0, 'Something went wrong.')
      setErrors(error.errors)
      if (Object.keys(error.errors).length === 0) setMessage(error.message)
      setSubmitting(false)
    }
  }

  return (
    <div className={styles.card}>
      <h1>Log in</h1>
      {redirect === '/checkout' && <p className="field-error" style={{ color: 'var(--color-text-muted)' }}>Log in to finish your purchase. Your cart is kept.</p>}
      {message && (
        <p className="alert" role="alert">
          {message}
        </p>
      )}
      <form onSubmit={handleSubmit} noValidate>
        <FormField
          name="email"
          label="Email"
          type="email"
          autoComplete="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          errors={errors.email}
        />
        <FormField
          name="password"
          label="Password"
          type="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          errors={errors.password}
        />
        <button type="submit" className="btn btn-primary btn-block" disabled={submitting}>
          {submitting ? 'Logging in…' : 'Log in'}
        </button>
      </form>
      <p className={styles.switch}>
        <Link to="/forgot-password">Forgot password?</Link>
      </p>
      <p className={styles.switch}>
        New here? <Link to={`/register?redirect=${encodeURIComponent(redirect)}`}>Create an account</Link>
      </p>
    </div>
  )
}
