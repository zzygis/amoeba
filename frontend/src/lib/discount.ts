// Discount for a cart total. Same rounding as Coupon::discountFor() in the backend
// (always down), so the cart preview matches what checkout charges to the cent.
export function discountCents(totalCents: number, percentOff: number): number {
  return Math.floor((totalCents * percentOff) / 100)
}
