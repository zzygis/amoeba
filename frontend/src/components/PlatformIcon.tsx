import { siEa, siPlaystation, siRockstargames, siSteam } from 'simple-icons'

// Platform logos from Simple Icons (CC0 icon data; the logos are their owners' trademarks,
// used only to label the platform). Simple Icons has no Xbox logo (Microsoft asked for its
// removal), so Xbox gets our own simple game-controller icon instead.
const ICON_PATHS: Record<string, string> = {
  Steam: siSteam.path,
  PlayStation: siPlaystation.path,
  'EA App': siEa.path,
  Rockstar: siRockstargames.path,
}

// Original controller outline (not a brand logo), drawn for the 24x24 grid the others use.
const CONTROLLER =
  'M7 6h10a5 5 0 0 1 4.9 4l1 5.2a2.9 2.9 0 0 1-5 2.4L16.2 16H7.8l-1.7 1.6a2.9 2.9 0 0 1-5-2.4l1-5.2A5 5 0 0 1 7 6zm0 3v1.5H5.5v1.5H7v1.5h1.5V12H10v-1.5H8.5V9H7zm9.5.25a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2z'

export default function PlatformIcon({ platform, size = 18 }: { platform: string; size?: number }) {
  const path = ICON_PATHS[platform] ?? CONTROLLER

  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
      <path d={path} fillRule="evenodd" />
    </svg>
  )
}
