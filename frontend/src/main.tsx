import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
// Metropolis is the typeface eneba.com uses (SIL Open Font License), bundled from npm
// so no external font service is needed. Only the weights the UI uses are loaded.
import '@fontsource/metropolis/400.css'
import '@fontsource/metropolis/600.css'
import '@fontsource/metropolis/700.css'
import '@fontsource/metropolis/800.css'
import './index.css'
import App from './App.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
