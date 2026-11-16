import { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiSend } from '../api/client'
import FormField from '../components/FormField'
import { useDocumentTitle } from '../hooks/useDocumentTitle'
import { useForm } from '../hooks/useForm'
import styles from './Auth.module.css'

// "/forgot-password": asks the API to email a reset link.
export default function ForgotPasswordPage() {
  useDocumentTitle('Forgot password')
  const [email, setEmail] = useState('')
  const form = useForm()

  return (
    <div className={styles.card}>
      <h1>Forgot password?</h1>
      {form.success ? (
        <p role="status">{form.success}</p>
      ) : (
        <form
          noValidate
          onSubmit={form.submit(async () => {
            const response = await apiSend<{ message: string }>('POST', '/forgot-password', { email })
            return response.message
          })}
        >
          <p className={styles.switch} style={{ textAlign: 'left' }}>
            Enter your account's email and we'll send you a link to choose a new password.
          </p>
          {form.message && (
            <p className="alert" role="alert">
              {form.message}
            </p>
          )}
          <FormField name="email" label="Email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} errors={form.errors.email} />
          <button type="submit" className="btn btn-primary btn-block" disabled={form.busy}>
            Send reset link
          </button>
        </form>
      )}
      <p className={styles.switch}>
        <Link to="/login">Back to log in</Link>
      </p>
    </div>
  )
}
