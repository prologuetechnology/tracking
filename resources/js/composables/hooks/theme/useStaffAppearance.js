import { usePage } from '@inertiajs/vue3'
import { onScopeDispose, watchEffect } from 'vue'

// The document root also owns the portaled menus, sheets and toast region.
export function useStaffAppearance() {
  const page = usePage()
  const system = window.matchMedia(`(prefers-color-scheme: dark)`)
  const root = document.documentElement
  function sync() {
    if (!page.component) return
    const preference = page.props.staffAppearance
    if (!preference) {
      if (root.hasAttribute(`data-staff-appearance`)) {
        delete root.dataset.staffAppearance
        root.classList.remove(`dark`)
      }
      return
    }
    root.dataset.staffAppearance = preference
    root.classList.toggle(
      `dark`,
      preference === `dark` || (preference === `system` && system.matches),
    )
  }
  watchEffect(sync)
  system.addEventListener(`change`, sync)
  onScopeDispose(() => system.removeEventListener(`change`, sync))
}
