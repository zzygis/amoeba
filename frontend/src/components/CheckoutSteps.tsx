import styles from './CheckoutSteps.module.css'

const STEPS = ['Cart', 'Payment', 'Get your product']

// The 1-2-3 progress bar at the top of the cart, payment and order pages (as on eneba.com).
export default function CheckoutSteps({ current }: { current: 1 | 2 | 3 }) {
  return (
    <ol className={styles.steps} aria-label="Checkout progress">
      {STEPS.map((label, index) => {
        const step = index + 1
        const state = step < current ? styles.done : step === current ? styles.active : ''
        return (
          <li key={label} className={`${styles.step} ${state}`} aria-current={step === current ? 'step' : undefined}>
            <span className={styles.number}>{step < current ? '✓' : step}</span>
            <span>{label}</span>
          </li>
        )
      })}
    </ol>
  )
}
