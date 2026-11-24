export const advanced = [
  [`foreground`, `Page text`, `background`],
  [`card`, `Card surface`, `cardForeground`],
  [`cardForeground`, `Card text`, `card`],
  [`border`, `Border`, null],
  [`primary`, `Action color`, `primaryForeground`],
  [`primaryForeground`, `Action text`, `primary`],
  [`ring`, `Focus ring`, `background`],
]
export function tokenHex(hsl) {
  if (!hsl) return null
  const [h, s, l] = hsl.split(` `).map(parseFloat)
  const channel = (n) => {
    const k = (n + h / 30) % 12
    const value =
      l / 100 -
      (s / 100) *
        Math.min(l / 100, 1 - l / 100) *
        Math.max(-1, Math.min(k - 3, 9 - k, 1))
    return Math.round(value * 255)
      .toString(16)
      .padStart(2, `0`)
  }
  return (`#` + channel(0) + channel(8) + channel(4)).toUpperCase()
}
export function contrast(a, b) {
  const luminance = (color) => {
    const [red, green, blue] = color.match(/[a-f0-9]{2}/gi).map((pair) => {
      const value = parseInt(pair, 16) / 255
      return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
    })
    return red * 0.2126 + green * 0.7152 + blue * 0.0722
  }
  const values = [luminance(a), luminance(b)].sort((x, y) => y - x)
  return (values[0] + 0.05) / (values[1] + 0.05)
}
