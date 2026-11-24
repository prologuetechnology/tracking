<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

import ConfirmPublication from './ConfirmPublication.vue'

const props = defineProps({
  company: { type: Object, required: true },
  draft: { type: Object, required: true },
  version: { type: Number, required: true },
  approved: Boolean,
  live: Boolean,
  publication: { type: Number, required: true },
})
const params = computed(() => [
  props.company.uuid,
  props.draft.uuid,
  props.version,
])
const form = useForm({})
function approve() {
  form
    .transform(() => ({}))
    .post(route(`admin.drafts.approve`, params.value), { preserveScroll: true })
}
function publish() {
  form
    .transform(() => ({ publication: props.publication }))
    .post(route(`admin.drafts.publish`, params.value), { preserveScroll: true })
}
</script>
<template>
  <div
    class="flex flex-wrap items-center gap-2"
    role="group"
    :aria-label="draft.name + ', revision ' + version"
  >
    <Badge v-if="live" variant="secondary">Live design</Badge>
    <template v-else-if="approved">
      <span class="text-sm text-muted-foreground">Approved</span>
      <ConfirmPublication
        title="Publish this revision?"
        :description="
          'Revision ' +
          version +
          ' of ' +
          draft.name +
          ' will appear on existing tracking links for ' +
          company.name +
          '.'
        "
        action="Publish revision"
        :busy="form.processing"
        @confirm="publish"
      >
        <Button size="sm" :disabled="form.processing">Publish revision</Button>
      </ConfirmPublication>
    </template>
    <Button
      v-else
      size="sm"
      variant="secondary"
      :disabled="form.processing"
      @click="approve"
      >Approve revision</Button
    >
    <p v-if="form.hasErrors" role="alert" class="text-sm text-destructive">
      {{ Object.values(form.errors)[0] }}
    </p>
  </div>
</template>
