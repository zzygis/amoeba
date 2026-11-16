// Prices come from the API as integer cents; format only at the edge, for display.
const euro = new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' })

export function formatPrice(cents: number): string {
  return euro.format(cents / 100)
}

const longDate = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })

// "2022-02-24" or an ISO timestamp -> "24 February 2022"
export function formatDate(value: string): string {
  return longDate.format(new Date(value))
}

export function plural(count: number, word: string): string {
  return `${count} ${word}${count === 1 ? '' : 's'}`
}
