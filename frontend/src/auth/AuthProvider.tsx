import { useEffect, useState, type ReactNode } from 'react'
import { apiRequest, apiSend, getToken, onUnauthorized, setToken } from '../api/client'
import type { AuthResponse, User, Wrapped } from '../types'
import { AuthContext, type RegisterData } from './useAuth'


// Holds who is logged in. Wrap the app in <AuthProvider> and read it with useAuth().
export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  // With no saved token there's nothing to check, so we're ready straight away.
  const [ready, setReady] = useState(() => getToken() === null)

  useEffect(() => {
    // If any request comes back 401 (token expired/revoked), treat it as logged out.
    onUnauthorized(() => setUser(null))

    if (getToken() === null) return
    // A token was saved by an earlier visit: ask the API who it belongs to.
    apiRequest<Wrapped<User>>('/user')
      .then((response) => setUser(response.data))
      .catch(() => setToken(null))
      .finally(() => setReady(true))
  }, [])

  async function login(email: string, password: string) {
    const response = await apiSend<AuthResponse>('POST', '/login', { email, password })
    setToken(response.token)
    setUser(response.user)
  }

  async function register(data: RegisterData) {
    const response = await apiSend<AuthResponse>('POST', '/register', data)
    setToken(response.token)
    setUser(response.user)
  }

  async function logout() {
    try {
      await apiSend('POST', '/logout')
    } catch {
      // Even if the server can't be reached, forget the token locally.
    }
    setToken(null)
    setUser(null)
  }

  function forgetUser() {
    setToken(null)
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, ready, login, register, logout, updateUser: setUser, forgetUser }}>
      {children}
    </AuthContext.Provider>
  )
}
