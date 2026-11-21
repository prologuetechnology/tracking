import { usePage } from '@inertiajs/vue3'

const imageAssetUrl = ({ filePath = null } = {}) => {
  const { app, frozenAt, brandImageUrls } = usePage().props
  if (frozenAt) return `/proof-brand/${encodeURIComponent(filePath)}`
  if (brandImageUrls) return brandImageUrls[filePath] ?? null
  const { spaces } = app.disks

  return `https://${spaces.bucket}.${spaces.region}.cdn.digitaloceanspaces.com/${filePath}`
}

export default imageAssetUrl
