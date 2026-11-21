<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'

const props = defineProps({
  company: { type: Object, required: true },
  publications: { type: Array, required: true },
})
const current = computed(() => props.publications[0])
const form = useForm({ publication: 0, restore: 0 })
const title = (entry) =>
  entry?.version
    ? entry.name + ` · Revision ` + entry.version
    : `Original Classic branding`
const restore = (entry) => {
  if (
    !window.confirm(
      `Restore ` +
        title(entry) +
        ` on the tracking links for ` +
        props.company.name +
        `?`,
    )
  )
    return
  form.publication = current.value.id
  form.restore = entry?.id ?? 0
  form.post(route(`admin.drafts.rollback`, props.company.uuid), {
    preserveScroll: true,
  })
}
</script>
<template>
  <section class="my-6 grid gap-2 rounded-lg border bg-card p-5">
    <h2 class="text-sm font-semibold text-muted-foreground">
      Live tracking design
    </h2>
    <p class="font-semibold">{{ title(current) }}</p>
    <details v-if="current" class="mt-2">
      <summary class="cursor-pointer text-sm underline">
        Publication history
      </summary>
      <ul class="mt-3 divide-y">
        <li
          v-for="entry in publications"
          :key="entry.id"
          class="flex flex-wrap items-center gap-3 py-2 text-sm"
        >
          <span>{{ title(entry) }}</span
          ><span
            >{{ entry.action === 'rollback' ? 'Restored' : 'Published' }}
            {{ new Date(entry.at).toLocaleString() }}</span
          >
          <Button
            v-if="entry.id !== current.id"
            size="sm"
            variant="outline"
            :disabled="form.processing"
            @click="restore(entry)"
            >Restore this version</Button
          >
        </li>
      </ul>
      <Button
        v-if="current.version"
        class="mt-3"
        size="sm"
        variant="outline"
        :disabled="form.processing"
        @click="restore(null)"
        >Restore original Classic branding</Button
      >
      <p v-if="form.hasErrors" role="alert" class="text-sm text-destructive">
        {{ Object.values(form.errors)[0] }}
      </p>
    </details>
  </section>
</template>
