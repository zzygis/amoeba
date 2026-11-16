import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { apiSend } from '../api/client'
import { useAuth } from '../auth/useAuth'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { useForm } from '../hooks/useForm'
import type { User, Wrapped } from '../types'
import styles from './AccountPage.module.css'

// "/account": profile, password and account deletion (login required, see App.tsx).
export default function AccountPage() {
  useDocumentTitle('My account')
  const { user } = useAuth()
  if (!user) return null

  return (
    <div className={styles.page}>
      <h1>My account</h1>
      <nav className={styles.links} aria-label="Account">
        <Link to="/orders">My orders</Link>
        <Link to="/wishlist">Wishlist</Link>
        {user.is_admin && <Link to="/admin">Admin panel</Link>}
      </nav>
      <ProfileForm user={user} />
      <PasswordForm />
      <DeleteAccount />
    </div>
  )
}

function ProfileForm({ user }: { user: User }) {
  const { updateUser } = useAuth()
  const [name, setName] = useState(user.name)
  const [email, setEmail] = useState(user.email)
  const form = useForm()

  return (
    <form
      className={styles.card}
      noValidate
      onSubmit={form.submit(async () => {
        const updated = await apiSend<Wrapped<User>>('PUT', '/user', { name, email })
        updateUser(updated.data)
        return 'Your details have been saved.'
      })}
    >
      <h2>Your details</h2>
      <FormField name="name" label="Name" autoComplete="name" value={name} onChange={(e) => setName(e.target.value)} errors={form.errors.name} />
      <FormField name="email" label="Email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} errors={form.errors.email} />
      <Feedback message={form.message} success={form.success} />
      <button type="submit" className="btn btn-primary" disabled={form.busy}>
        Save
      </button>
    </form>
  )
}

function PasswordForm() {
  const [fields, setFields] = useState({ current_password: '', password: '', password_confirmation: '' })
  const form = useForm()
  const set = (key: keyof typeof fields) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setFields((previous) => ({ ...previous, [key]: e.target.value }))

  return (
    <form
      className={styles.card}
      noValidate
      onSubmit={form.submit(async () => {
        await apiSend('PUT', '/user/password', fields)
        setFields({ current_password: '', password: '', password_confirmation: '' })
        return 'Password changed. Other devices have been logged out.'
      })}
    >
      <h2>Change password</h2>
      <FormField name="current_password" label="Current password" type="password" autoComplete="current-password" value={fields.current_password} onChange={set('current_password')} errors={form.errors.current_password} />
      <FormField name="new_password" label="New password (at least 8 characters)" type="password" autoComplete="new-password" value={fields.password} onChange={set('password')} errors={form.errors.password} />
      <FormField name="new_password_confirmation" label="Repeat new password" type="password" autoComplete="new-password" value={fields.password_confirmation} onChange={set('password_confirmation')} />
      <Feedback message={form.message} success={form.success} />
      <button type="submit" className="btn btn-primary" disabled={form.busy}>
        Change password
      </button>
    </form>
  )
}

function DeleteAccount() {
  const { forgetUser } = useAuth()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const [password, setPassword] = useState('')
  const form = useForm()

  if (!open) {
    return (
      <section className={styles.card}>
        <h2>Delete account</h2>
        <p className={styles.muted}>Deletes your account, orders, wishlist and reviews. This can't be undone.</p>
        <button type="button" className="btn btn-outline" onClick={() => setOpen(true)}>
          Delete my account…
        </button>
      </section>
    )
  }

  return (
    <form
      className={`${styles.card} ${styles.danger}`}
      noValidate
      onSubmit={form.submit(async () => {
        await apiSend('DELETE', '/user', { password })
        forgetUser()
        navigate('/', { replace: true })
      })}
    >
      <h2>Delete account</h2>
      <p>
        This permanently deletes your account, your order history (including the keys shown there), your wishlist and
        your reviews. Copy any keys you still need first. Enter your password to confirm.
      </p>
      <FormField name="delete_password" label="Password" type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} errors={form.errors.password} />
      <Feedback message={form.message} success={null} />
      <div className={styles.row}>
        <button type="submit" className="btn btn-primary" disabled={form.busy || password === ''}>
          Permanently delete
        </button>
        <button type="button" className="btn btn-outline" onClick={() => setOpen(false)}>
          Cancel
        </button>
      </div>
    </form>
  )
}

function Feedback({ message, success }: { message: string | null; success: string | null }) {
  if (message) {
    return (
      <p className="alert" role="alert">
        {message}
      </p>
    )
  }
  if (success) {
    return (
      <p className={styles.success} role="status">
        {success}
      </p>
    )
  }
  return null
}
