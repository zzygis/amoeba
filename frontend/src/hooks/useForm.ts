import { useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'

// Shared plumbing for the account forms: busy flag, Laravel field errors,
// a general error message and a success message.
export function useForm() {
  const [busy, setBusy] = useState(false)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)

  function submit(action: () => Promise<string | void>) {
    return async (event: FormEvent) => {
      event.preventDefault()
      setBusy(true)
      setErrors({})
      setMessage(null)
      setSuccess(null)
      try {
        const done = await action()
        if (done) setSuccess(done)
      } catch (e) {
        const error = e instanceof ApiError ? e : new ApiError(0, 'Something went wrong.')
        setErrors(error.errors)
        if (Object.keys(error.errors).length === 0) setMessage(error.message)
      } finally {
        setBusy(false)
      }
    }
  }

  return { busy, errors, message, success, submit }
}
