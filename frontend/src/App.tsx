import { BrowserRouter, Route, Routes } from 'react-router-dom'
import StatusPage from './pages/StatusPage'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<StatusPage />} />
        <Route path="*" element={<main style={{ padding: '2rem' }}>Page not found</main>} />
      </Routes>
    </BrowserRouter>
  )
}
