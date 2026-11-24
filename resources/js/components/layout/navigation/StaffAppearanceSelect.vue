<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { Monitor } from 'lucide-vue-next'
import { ref } from 'vue'

const page = usePage()
const saving = ref(false)
const error = ref(``)
function change(event) {
  const input = event.target
  const appearance = input.value
  input.value = page.props.staffAppearance
  error.value = ``
  saving.value = true
  router.patch(
    route(`admin.preferences.appearance`),
    { appearance },
    {
      preserveState: true,
      preserveScroll: true,
      only: [`staffAppearance`],
      onError: () => {
        error.value = `Could not save appearance.`
      },
      onFinish: () => {
        saving.value = false
      },
    },
  )
}
</script>
<template>
  <div class="signal-appearance">
    <label
      ><Monitor :size="16" aria-hidden="true" /><span class="sr-only"
        >Staff appearance</span
      >
      <select
        aria-label="Staff appearance"
        :value="page.props.staffAppearance"
        :disabled="saving || page.props.is_impersonating"
        @change="change"
      >
        <option value="system">System</option>
        <option value="light">Light</option>
        <option value="dark">Dark</option>
      </select>
    </label>
    <small v-if="error" role="alert">{{ error }}</small>
  </div>
</template>
