export const styles = [
  {
    key: `lake`,
    name: `Lake blue`,
    colors: { brand: `#185E86`, accent: `#B7D9E8`, paper: `#F5F8FA` },
    headline: `layout`,
    corners: `soft`,
  },
  {
    key: `trail`,
    name: `Trailhead`,
    colors: { brand: `#305E4A`, accent: `#D3BB86`, paper: `#F7F7F0` },
    headline: `serif`,
    corners: `round`,
  },
  {
    key: `slate`,
    name: `Graphite`,
    colors: { brand: `#37384D`, accent: `#B7AED4`, paper: `#F8F7FA` },
    headline: `sans`,
    corners: `square`,
  },
].map((style) => ({ ...style, version: 1, overrides: {} }))

export const assets = [
  { key: `jayco-overlook`, name: `Canyon campsite`, type: `Banner` },
  { key: `canyon`, name: `Canyon landscape`, type: `Banner` },
  { key: `jayco-logo`, name: `Jayco wordmark`, type: `Logo` },
]
export const clone = (value) => JSON.parse(JSON.stringify(value))
export const assetUrl = (key) => route(`admin.portal-review.asset`, key)
export const previewInput = (style, banner) => ({
  colors: style.colors,
  overrides: style.overrides,
  headline: style.headline,
  corners: style.corners,
  banner,
})
