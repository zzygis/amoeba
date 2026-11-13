/// <reference types="vite/client" />

// Env variables the app reads (see .env.example). Typing them gives autocomplete
// and catches typos like import.meta.env.VITE_API_ULR at compile time.
interface ImportMetaEnv {
  readonly VITE_API_URL: string
  readonly VITE_APP_NAME: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
