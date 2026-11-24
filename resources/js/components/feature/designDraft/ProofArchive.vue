<script setup>
import { router, useForm } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'

import ArchivedProofViewer from '@/components/feature/designDraft/ArchivedProofViewer.vue'
import { Button } from '@/components/ui/button'

const emit = defineEmits([`snapshot-expired`])
const props = defineProps({
  company: { type: Object, required: true },
  draft: { type: Object, required: true },
  revision: { type: Object, required: true },
  proofs: { type: Array, required: true },
  lookup: { type: Object, default: null },
})
const params = computed(() => [
  props.company.uuid,
  props.draft.uuid,
  props.revision.version,
])
const capture = useForm({})
const retry = useForm({})
watch(
  () => props.lookup?.snapshot_token,
  () => capture.clearErrors(),
)
const exporting = () => {
  const lookup = props.lookup
  if (!lookup?.snapshot_token) return
  capture
    .transform(() => lookup)
    .post(route(`admin.drafts.proofs.store`, params.value), {
      preserveScroll: true,
      onError: (errors) => {
        if (errors.snapshot_token)
          emit(`snapshot-expired`, lookup.snapshot_token, errors.snapshot_token)
      },
    })
}
onMounted(() => {
  const anchor = window.location.hash.slice(1)
  if (props.proofs.some((proof) => anchor === `proof-` + proof.uuid)) {
    document.getElementById(anchor)?.focus()
  }
})
let timer
watch(
  () => props.proofs.some((p) => p.status === `pending`),
  (pending) => {
    clearInterval(timer)
    if (pending)
      timer = setInterval(
        () =>
          router.reload({
            only: [`proofs`, `publication`, `revision`, `isLive`],
          }),
        5000,
      )
  },
  { immediate: true },
)
onBeforeUnmount(() => clearInterval(timer))
</script>
<template>
  <section aria-labelledby="saved-proofs" class="grid gap-3 pt-5">
    <div class="flex flex-wrap items-center gap-3">
      <h2 id="saved-proofs" class="mr-auto font-semibold">Revision proofs</h2>
      <Button
        size="sm"
        :disabled="!lookup || capture.processing"
        @click="exporting"
      >
        Export desktop and mobile PNGs
      </Button>
    </div>
    <p v-if="capture.hasErrors" role="alert" class="text-sm text-destructive">
      {{ Object.values(capture.errors)[0] }}
    </p>
    <p v-if="!proofs.length" class="text-sm text-muted-foreground">
      Load a shipment to save both views for customer review.
    </p>
    <ul v-else class="divide-y">
      <li
        v-for="proof in proofs"
        :id="'proof-' + proof.uuid"
        :key="proof.uuid"
        tabindex="-1"
        class="grid scroll-mt-4 gap-2 py-3 focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
      >
        <div class="flex flex-wrap items-center gap-3 text-sm">
          <strong>{{ proof.shipment_reference }}</strong>
          <span class="font-mono text-xs"
            >Proof {{ proof.uuid.slice(-8) }}</span
          >
          <time :datetime="proof.created_at">{{
            new Date(proof.created_at).toLocaleString()
          }}</time>
          <template v-if="proof.status === 'ready'">
            <ArchivedProofViewer :params="params" :proof="proof" />
            <div
              role="group"
              aria-label="Download proof images"
              class="flex flex-wrap gap-2"
            >
              <Button
                v-for="view in ['desktop', 'mobile']"
                :key="view"
                variant="secondary"
                size="sm"
                as-child
              >
                <a
                  :href="
                    route('admin.drafts.proofs.download', [
                      ...params,
                      proof.uuid,
                      view,
                    ])
                  "
                  class="gap-2"
                >
                  <Download class="size-4" aria-hidden="true" />
                  {{ view === 'desktop' ? 'Desktop PNG' : 'Mobile PNG' }}
                </a>
              </Button>
            </div>
          </template>
          <span v-else role="status" class="text-muted-foreground">
            <template v-if="proof.status === 'failed'">Export failed.</template>
            <template v-else-if="proof.can_retry"
              >Export delayed. You can retry this shipment snapshot.</template
            >
            <template v-else
              >Preparing both images. Download links will appear here.</template
            >
          </span>
        </div>
        <Button
          v-if="proof.can_retry"
          size="sm"
          variant="outline"
          :disabled="retry.processing"
          @click="
            retry.post(
              route('admin.drafts.proofs.retry', [...params, proof.uuid]),
              { preserveScroll: true },
            )
          "
        >
          Retry this snapshot
        </Button>
      </li>
    </ul>
  </section>
</template>
