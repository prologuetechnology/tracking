<script setup>
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

const props = defineProps({
  params: { type: Array, required: true },
  proof: { type: Object, required: true },
})
const viewport = ref(`desktop`)
const actualSize = ref(false)
const failed = ref(false)
const source = computed(
  () =>
    route(`admin.drafts.proofs.download`, [
      ...props.params,
      props.proof.uuid,
      viewport.value,
    ]) + `?inline=1`,
)
const select = (view) => {
  viewport.value = view
  failed.value = false
}
</script>

<template>
  <Dialog>
    <DialogTrigger as-child>
      <Button size="sm" variant="outline">Review images</Button>
    </DialogTrigger>
    <DialogContent
      class="flex max-h-[94dvh] w-[96vw] max-w-6xl flex-col gap-3 overflow-hidden p-4 sm:p-6"
    >
      <DialogHeader class="pr-6">
        <DialogTitle>Archived proof</DialogTitle>
        <DialogDescription
          >{{ proof.shipment_reference }} · Revision {{ params[2] }} · Proof
          {{ proof.uuid.slice(-8) }}</DialogDescription
        >
      </DialogHeader>
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div role="group" aria-label="Archived image" class="flex gap-1">
          <Button
            v-for="view in ['desktop', 'mobile']"
            :key="view"
            type="button"
            size="sm"
            :variant="viewport === view ? 'default' : 'outline'"
            :aria-pressed="viewport === view"
            @click="select(view)"
          >
            {{ view === 'desktop' ? 'Desktop' : 'Mobile' }}
          </Button>
        </div>
        <Button
          type="button"
          size="sm"
          variant="ghost"
          :aria-pressed="actualSize"
          @click="actualSize = !actualSize"
          >Actual size</Button
        >
      </div>
      <div v-if="failed" role="alert" class="rounded border p-4 text-sm">
        This archived image could not be loaded.
      </div>
      <div
        v-else
        role="region"
        aria-label="Archived image detail"
        tabindex="0"
        class="min-h-0 overflow-auto rounded border bg-muted p-1 focus-visible:outline-ring"
      >
        <img
          :key="source"
          :src="source"
          :alt="
            (viewport === 'desktop' ? 'Desktop' : 'Mobile') +
            ' tracking proof for ' +
            proof.shipment_reference
          "
          class="mx-auto block h-auto"
          :class="actualSize ? 'max-w-none' : 'max-w-full'"
          @error="failed = true"
        />
      </div>
    </DialogContent>
  </Dialog>
</template>
