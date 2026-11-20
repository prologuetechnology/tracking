import { usePage } from '@inertiajs/vue3'
import { onBeforeUnmount, onMounted } from 'vue'

const colorProperties = {
  background: `--background`,
  foreground: `--foreground`,
  muted: `--muted`,
  mutedForeground: `--muted-foreground`,
  popover: `--popover`,
  popoverForeground: `--popover-foreground`,
  card: `--card`,
  cardForeground: `--card-foreground`,
  border: `--border`,
  input: `--input`,
  primary: `--primary`,
  primaryForeground: `--primary-foreground`,
  secondary: `--secondary`,
  secondaryForeground: `--secondary-foreground`,
  accent: `--accent`,
  accentForeground: `--accent-foreground`,
  destructive: `--destructive`,
  destructiveForeground: `--destructive-foreground`,
  ring: `--ring`,
}

const useCompanyTheme = () => {
  const colors = usePage().props.initialCompany?.theme?.colors?.root
  if (!colors) return

  const previous = new Map()

  onMounted(() => {
    const style = document.documentElement.style

    for (const [token, property] of Object.entries(colorProperties)) {
      if (colors[token]) {
        previous.set(property, style.getPropertyValue(property))
        style.setProperty(property, colors[token])
      }
    }
  })

  onBeforeUnmount(() => {
    for (const [property, value] of previous) {
      document.documentElement.style.setProperty(property, value)
    }
  })
}

export default useCompanyTheme
