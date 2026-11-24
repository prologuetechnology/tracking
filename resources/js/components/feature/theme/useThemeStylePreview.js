import axios from 'axios'
import { ref, watch } from 'vue'

export function useThemeStylePreview(style, savedAppearance) {
  const appearance = ref(null)
  const derived = ref({})
  const loading = ref(false)
  const error = ref(false)
  const attempts = ref(0)
  watch(
    [style, savedAppearance, attempts],
    ([value, saved], _, onCleanup) => {
      error.value = false
      if (saved) {
        appearance.value = saved
        derived.value = saved.colors.root
        loading.value = false
        return
      }
      loading.value = true
      const controller = new AbortController()
      const timer = setTimeout(async () => {
        try {
          const { data } = await axios.post(
            route(`api.themes.preview`),
            { style: value },
            { signal: controller.signal },
          )
          if (controller.signal.aborted) return
          appearance.value = data.appearance
          derived.value = data.derived
        } catch {
          if (!controller.signal.aborted) error.value = true
        } finally {
          if (!controller.signal.aborted) loading.value = false
        }
      }, 140)
      onCleanup(() => {
        clearTimeout(timer)
        controller.abort()
      })
    },
    { immediate: true },
  )
  return { appearance, derived, loading, error, retry: () => attempts.value++ }
}
