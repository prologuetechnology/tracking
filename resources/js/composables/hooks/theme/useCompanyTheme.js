import { usePage } from '@inertiajs/vue3'
import { onMounted, watchEffect } from 'vue'

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
  const page = usePage()
  onMounted(() => {
    const style = document.documentElement.style
    watchEffect((onCleanup) => {
      const colors = page.props.initialCompany?.theme?.colors?.root
      if (!colors) return

      const previous = new Map()
      for (const [token, property] of Object.entries(colorProperties)) {
        if (colors[token]) {
          previous.set(property, [
            style.getPropertyValue(property),
            style.getPropertyPriority(property),
          ])
          style.setProperty(property, colors[token])
        }
      }
      onCleanup(() => {
        for (const [property, [value, priority]] of previous) {
          if (value) style.setProperty(property, value, priority)
          else style.removeProperty(property)
        }
      })
    })
  })
}

export default useCompanyTheme
