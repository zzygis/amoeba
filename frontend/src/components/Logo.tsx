import styles from './Logo.module.css'

// Header logo: Eneba's swirl mark (public/favicon.svg) + our name from VITE_APP_NAME,
// in lowercase like Eneba's own wordmark.
export default function Logo() {
  return (
    <span className={styles.logo}>
      <img src="/favicon.svg" alt="" width={36} height={36} className={styles.mark} />
      <span className={styles.name}>{import.meta.env.VITE_APP_NAME.toLowerCase()}</span>
    </span>
  )
}
