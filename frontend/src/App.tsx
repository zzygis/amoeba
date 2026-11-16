import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AdminLayout from './admin/AdminLayout'
import { CouponsTab, HomepageTab, OffersTab, OrdersTab } from './admin/AdminSections'
import { AuthProvider } from './auth/AuthProvider'
import { CartProvider } from './cart/CartProvider'
import { WishlistProvider } from './wishlist/WishlistProvider'
import Layout from './components/Layout'
import RequireAuth from './components/RequireAuth'
import AccountPage from './pages/AccountPage'
import CartPage from './pages/CartPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import ResetPasswordPage from './pages/ResetPasswordPage'
import CheckoutPage from './pages/CheckoutPage'
import HomePage from './pages/HomePage'
import LoginPage from './pages/LoginPage'
import NotFoundPage from './pages/NotFoundPage'
import OrderPage from './pages/OrderPage'
import OrdersPage from './pages/OrdersPage'
import ProductPage from './pages/ProductPage'
import RegisterPage from './pages/RegisterPage'
import StatusPage from './pages/StatusPage'
import StorePage from './pages/StorePage'
import WishlistPage from './pages/WishlistPage'

export default function App() {
  return (
    <BrowserRouter>
      {/* Cart depends on auth (it merges the guest cart on login), so it sits inside. */}
      <AuthProvider>
        <CartProvider>
          <WishlistProvider>
          <Routes>
            {/* The shop: every page inside <Layout> gets the shop's header and footer. */}
            <Route element={<Layout />}>
              <Route path="/" element={<HomePage />} />
              <Route path="/store" element={<StorePage />} />
              <Route path="/games/:slug" element={<ProductPage />} />
              <Route path="/cart" element={<CartPage />} />
              <Route path="/login" element={<LoginPage />} />
              <Route path="/register" element={<RegisterPage />} />
              <Route path="/forgot-password" element={<ForgotPasswordPage />} />
              <Route path="/reset-password" element={<ResetPasswordPage />} />
              <Route path="/account" element={<RequireAuth><AccountPage /></RequireAuth>} />
              <Route path="/checkout" element={<RequireAuth><CheckoutPage /></RequireAuth>} />
              <Route path="/orders" element={<RequireAuth><OrdersPage /></RequireAuth>} />
              <Route path="/orders/:id" element={<RequireAuth><OrderPage /></RequireAuth>} />
              <Route path="/wishlist" element={<RequireAuth><WishlistPage /></RequireAuth>} />
              <Route path="/status" element={<StatusPage />} />
              <Route path="*" element={<NotFoundPage />} />
            </Route>

            {/* The back office: a separate area with its own layout and sign-in (see AdminLayout). */}
            <Route path="/admin" element={<AdminLayout />}>
              <Route index element={<Navigate to="offers" replace />} />
              <Route path="offers" element={<OffersTab />} />
              <Route path="homepage" element={<HomepageTab />} />
              <Route path="coupons" element={<CouponsTab />} />
              <Route path="orders" element={<OrdersTab />} />
              <Route path="*" element={<Navigate to="/admin/offers" replace />} />
            </Route>
          </Routes>
          </WishlistProvider>
        </CartProvider>
      </AuthProvider>
    </BrowserRouter>
  )
}
