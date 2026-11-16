import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  build: {
    // hls.js (trailer player) is ~580 kB, but it's its own chunk, downloaded only when
    // someone presses "Watch trailer" (dynamic import in TrailerPlayer.tsx). The main
    // bundle stays ~340 kB, so the default 500 kB warning would be misleading here.
    chunkSizeWarningLimit: 600,
  },
})
