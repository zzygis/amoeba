import { createContext, useContext } from 'react'
import type { User } from '../types'

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthContextValue {
  user: User | null
  ready: boolean // false while we check a remembered token on page load
  login: (email: string, password: string) => Promise<void>
  register: (data: RegisterData) => Promise<void>
  logout: () => Promise<void>
  updateUser: (user: User) => void // after editing the profile
  forgetUser: () => void // after the account was deleted (its token is already gone)
}

// Filled in by <AuthProvider> (AuthProvider.tsx).
export const AuthContext = createContext<AuthContextValue | null>(null)

// Who is logged in, plus login/register/logout.
export function useAuth(): AuthContextValue {
  const value = useContext(AuthContext)
  if (!value) throw new Error('useAuth must be used inside <AuthProvider>')
  return value
}
