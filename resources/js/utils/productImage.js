/**
 * Returns the correct display URL for a product image.
 * - If the path starts with http/https, it is an external URL (e.g. Unsplash demo images) → return as-is.
 * - Otherwise it's a local Laravel storage path → prepend /storage/
 */
export function productImageUrl(path) {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//')) {
    return path
  }
  return `/storage/${path}`
}
