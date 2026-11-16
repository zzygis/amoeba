import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { apiSend } from '../api/client'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { useForm } from '../hooks/useForm'
import styles from './Auth.module.css'

// "/reset-password?token=...&email=...": the page the reset email links to.
export default function ResetPasswordPage() {
  useDocumentTitle('Choose a new password')
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const form = useForm()

  if (!token || !email) {
    return (
      <div className={styles.card}>
        <h1>Invalid link</h1>
        <p>This reset link is incomplete. Please request a new one.</p>
        <Link to="/forgot-password" className="btn btn-primary">
          Request a new link
        </Link>
      </div>
    )
  }

  return (
    <div className={styles.card}>
      <h1>Choose a new password</h1>
      {form.success ? (
        <>
          <p role="status">{form.success}</p>
          <Link to="/login" className="btn btn-primary btn-block">
            Log in
          </Link>
        </>
      ) : (
        <form
          noValidate
          onSubmit={form.submit(async () => {
            const response = await apiSend<{ message: string }>('POST', '/reset-password', {
              token,
              email,
              password,
              password_confirmation: confirmation,
            })
            return response.message
          })}
        >
          <p className={styles.switch} style={{ textAlign: 'left' }}>
            For {email}
          </p>
          {(form.message || form.errors.email) && (
            <p className="alert" role="alert">
              {form.message ?? form.errors.email?.[0]}{' '}
              <Link to="/forgot-password">Request a new link</Link>
            </p>
          )}
          <FormField name="password" label="New password (at least 8 characters)" type="password" autoComplete="new-password" value={password} onChange={(e) => setPassword(e.target.value)} errors={form.errors.password} />
          <FormField name="password_confirmation" label="Repeat new password" type="password" autoComplete="new-password" value={confirmation} onChange={(e) => setConfirmation(e.target.value)} />
          <button type="submit" className="btn btn-primary btn-block" disabled={form.busy}>
            Save new password
          </button>
        </form>
      )}
    </div>
  )
}
