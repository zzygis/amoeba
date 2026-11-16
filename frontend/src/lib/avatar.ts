// Colours for generated avatars: all dark enough for white text to be readable.
const COLORS = ['#7b2ff7', '#d6336c', '#0c8599', '#2b8a3e', '#e8590c', '#1c7ed6', '#9c36b5', '#5c940d']

// Same name -> same colour, every time (a tiny string hash picks one from the list).
export function avatarColor(name: string): string {
  let hash = 0
  for (const char of name) hash = (hash * 31 + char.charCodeAt(0)) >>> 0
  return COLORS[hash % COLORS.length]
}
