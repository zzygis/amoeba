import { useState, type FormEvent } from 'react'
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/useAuth'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { safeRedirect } from '../lib/redirect'
import styles from './Auth.module.css'

export default function RegisterPage() {
  useDocumentTitle('Create an account')
  const { user, register } = useAuth()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const redirect = safeRedirect(params.get('redirect'))

  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  if (user && !submitting) return <Navigate to={redirect} replace />

  function update(field: keyof typeof form, value: string) {
    setForm((previous) => ({ ...previous, [field]: value }))
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setSubmitting(true)
    setErrors({})
    setMessage(null)
    try {
      await register(form)
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
      <h1>Create an account</h1>
      {message && (
        <p className="alert" role="alert">
          {message}
        </p>
      )}
      <form onSubmit={handleSubmit} noValidate>
        <FormField
          name="name"
          label="Name"
          autoComplete="name"
          required
          value={form.name}
          onChange={(e) => update('name', e.target.value)}
          errors={errors.name}
        />
        <FormField
          name="email"
          label="Email"
          type="email"
          autoComplete="email"
          required
          value={form.email}
          onChange={(e) => update('email', e.target.value)}
          errors={errors.email}
        />
        <FormField
          name="password"
          label="Password (at least 8 characters)"
          type="password"
          autoComplete="new-password"
          required
          value={form.password}
          onChange={(e) => update('password', e.target.value)}
          errors={errors.password}
        />
        <FormField
          name="password_confirmation"
          label="Repeat password"
          type="password"
          autoComplete="new-password"
          required
          value={form.password_confirmation}
          onChange={(e) => update('password_confirmation', e.target.value)}
        />
        <button type="submit" className="btn btn-primary btn-block" disabled={submitting}>
          {submitting ? 'Creating account…' : 'Create account'}
        </button>
      </form>
      <p className={styles.switch}>
        Already have an account? <Link to={`/login?redirect=${encodeURIComponent(redirect)}`}>Log in</Link>
      </p>
    </div>
  )
}
