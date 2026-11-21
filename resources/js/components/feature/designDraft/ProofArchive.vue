<script setup>
import { router, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  company: { type: Object, required: true },
  draft: { type: Object, required: true },
  revision: { type: Object, required: true },
  proofs: { type: Array, required: true },
  publication: { type: Number, required: true },
  activeProof: { type: String, default: null },
  lookup: { type: Object, default: null },
})
const params = computed(() => [
  props.company.uuid,
  props.draft.uuid,
  props.revision.version,
])
const capture = useForm({})
const retry = useForm({})
const approval = useForm({ customer_approver: `` })
const release = useForm({ publication: 0 })
const selected = ref(null)
const startApproval = (proof) => {
  selected.value = proof.uuid
  approval.reset()
  approval.clearErrors()
}
const exporting = () =>
  capture
    .transform(() => props.lookup)
    .post(route(`admin.drafts.proofs.store`, params.value), {
      preserveScroll: true,
    })
const approve = (proof) =>
  approval.post(
    route(`admin.drafts.proofs.approve`, [...params.value, proof.uuid]),
    {
      preserveScroll: true,
      onSuccess: () => {
        selected.value = null
        approval.reset()
      },
    },
  )
const publish = (proof) => {
  if (
    !window.confirm(
      `Publish revision ` +
        props.revision.version +
        ` to the existing tracking links for ` +
        props.company.name +
        `?`,
    )
  )
    return
  release.publication = props.publication
  release.post(
    route(`admin.drafts.proofs.publish`, [...params.value, proof.uuid]),
    { preserveScroll: true },
  )
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
        () => router.reload({ only: [`proofs`, `publication`, `activeProof`] }),
        5000,
      )
  },
  { immediate: true },
)
onBeforeUnmount(() => clearInterval(timer))
</script>
<template>
  <section aria-labelledby="saved-proofs" class="grid gap-3 border-t pt-4">
    <div class="flex flex-wrap items-center gap-3">
      <h2 id="saved-proofs" class="mr-auto font-semibold">Revision proofs</h2>
      <Button
        size="sm"
        :disabled="!lookup || capture.processing"
        @click="exporting"
        >Export desktop and mobile PNGs</Button
      >
    </div>
    <p
      v-if="capture.hasErrors || release.hasErrors"
      role="alert"
      class="text-sm text-destructive"
    >
      {{ Object.values(capture.errors)[0] || Object.values(release.errors)[0] }}
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
        class="grid scroll-mt-4 gap-2 py-3 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-ring"
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
            <a
              v-for="view in ['desktop', 'mobile']"
              :key="view"
              class="font-medium underline"
              :href="
                route('admin.drafts.proofs.download', [
                  ...params,
                  proof.uuid,
                  view,
                ])
              "
              >{{ view === 'desktop' ? 'Desktop PNG' : 'Mobile PNG' }}</a
            >
            <span
              v-if="proof.uuid === activeProof"
              class="rounded bg-muted px-2 py-1 text-xs font-semibold"
              >Live design</span
            >
          </template>
          <span v-else role="status">{{
            proof.status === 'failed'
              ? 'Export failed.'
              : 'Preparing both images…'
          }}</span>
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
          >Retry this snapshot</Button
        >
        <div
          v-if="proof.status === 'ready'"
          class="flex flex-wrap items-center gap-3 text-sm"
        >
          <template v-if="proof.approved_at">
            <span
              >Approved by {{ proof.customer_approver }} ·
              {{ new Date(proof.approved_at).toLocaleDateString() }}</span
            >
            <Button
              v-if="proof.uuid !== activeProof"
              size="sm"
              :disabled="release.processing"
              @click="publish(proof)"
              >Publish approved revision</Button
            >
          </template>
          <form
            v-else-if="selected === proof.uuid"
            class="flex flex-wrap items-end gap-2"
            @submit.prevent="approve(proof)"
          >
            <div class="grid gap-1">
              <Label :for="'approver-' + proof.uuid"
                >Who approved this proof?</Label
              ><Input
                :id="'approver-' + proof.uuid"
                v-model="approval.customer_approver"
                maxlength="120"
                required
              />
            </div>
            <Button
              type="submit"
              variant="default"
              size="sm"
              :disabled="approval.processing"
              >Save customer approval</Button
            >
            <Button
              type="button"
              size="sm"
              variant="ghost"
              @click="selected = null"
              >Cancel</Button
            >
            <p v-if="approval.hasErrors" role="alert">
              {{ approval.errors.customer_approver }}
            </p>
          </form>
          <Button
            v-else
            size="sm"
            variant="outline"
            @click="startApproval(proof)"
            >Record customer approval</Button
          >
        </div>
      </li>
    </ul>
  </section>
</template>
