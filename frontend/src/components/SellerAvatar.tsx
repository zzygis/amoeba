import { useState } from 'react'
import { avatarColor } from '../lib/avatar'
import styles from './SellerAvatar.module.css'

interface Props {
  name: string
  imageUrl?: string | null // the seller's profile picture (demo sellers: a game's cover art)
  size?: number
}

// A seller's round profile picture. Without a picture, or if it fails to load, it shows
// the seller's initial on a colour derived from the name (always the same per seller).
export default function SellerAvatar({ name, imageUrl, size = 28 }: Props) {
  const [failed, setFailed] = useState(false)

  if (imageUrl && !failed) {
    return (
      <img
        src={imageUrl}
        alt=""
        width={size}
        height={size}
        loading="lazy"
        className={`${styles.avatar} ${styles.image}`}
        style={{ width: size, height: size }}
        onError={() => setFailed(true)}
      />
    )
  }

  return (
    <span
      className={styles.avatar}
      style={{ width: size, height: size, fontSize: size * 0.45, background: avatarColor(name) }}
      aria-hidden="true"
    >
      {name.trim().charAt(0).toUpperCase()}
    </span>
  )
}
