import { useEffect, useRef, useState } from 'react'
import type { Trailer } from '../types'
import styles from './TrailerPlayer.module.css'

// Plays a Steam trailer. Steam only offers HLS streams (.m3u8): Safari plays them
// natively, other browsers need hls.js. hls.js is ~400 KB, so it's only downloaded
// (dynamic import) when someone actually presses play.
export default function TrailerPlayer({ trailer, poster }: { trailer: Trailer; poster: string }) {
  const videoRef = useRef<HTMLVideoElement>(null)
  const [started, setStarted] = useState(false)
  const [failed, setFailed] = useState(false)

  useEffect(() => {
    const video = videoRef.current
    if (!started || !video) return

    let destroy: (() => void) | undefined

    if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = trailer.hls_url
      video.play().catch(() => {})
    } else {
      import('hls.js')
        .then(({ default: Hls }) => {
          if (!Hls.isSupported()) throw new Error('HLS not supported')
          const hls = new Hls()
          hls.loadSource(trailer.hls_url)
          hls.attachMedia(video)
          hls.on(Hls.Events.MANIFEST_PARSED, () => video.play().catch(() => {}))
          hls.on(Hls.Events.ERROR, (_event, data) => data.fatal && setFailed(true))
          destroy = () => hls.destroy()
        })
        .catch(() => setFailed(true))
    }

    return () => destroy?.()
  }, [started, trailer.hls_url])

  if (failed) {
    return <p className={styles.error}>This trailer can't be played in your browser.</p>
  }

  return (
    <div className={styles.player}>
      <video ref={videoRef} controls={started} poster={poster} playsInline aria-label={trailer.title} />
      {!started && (
        <button type="button" className={styles.play} onClick={() => setStarted(true)}>
          <span className={styles.playIcon} aria-hidden="true">
            ▶
          </span>
          <span>Watch trailer</span>
        </button>
      )}
    </div>
  )
}
