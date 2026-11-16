import type { InputHTMLAttributes } from 'react'

type Props = InputHTMLAttributes<HTMLInputElement> & {
  name: string
  label: string
  errors?: string[] // Laravel validation messages for this field
}

// Label + input + error message, wired together for screen readers.
export default function FormField({ name, label, errors, ...inputProps }: Props) {
  const errorId = `${name}-error`
  const invalid = Boolean(errors?.length)

  return (
    <div className="field">
      <label htmlFor={name}>{label}</label>
      <input
        id={name}
        name={name}
        aria-invalid={invalid}
        aria-describedby={invalid ? errorId : undefined}
        {...inputProps}
      />
      {invalid && (
        <p id={errorId} className="field-error">
          {errors![0]}
        </p>
      )}
    </div>
  )
}
