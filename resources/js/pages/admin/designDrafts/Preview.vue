<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

import DraftSelect from '@/components/feature/designDraft/DraftSelect.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  company: { type: Object, required: true },
  draft: { type: Object, required: true },
  revision: { type: Object, required: true },
})
const mobile = ref(false)
const number = ref(props.revision.tracking_number ?? ``)
const option = ref(props.revision.search_option)
const frame = ref(null)
const lookupAttempt = ref(0)
const load = () => {
  if (!number.value.trim()) return
  lookupAttempt.value += 1
  frame.value = route(`admin.drafts.render`, {
    company: props.company.uuid,
    draft: props.draft.uuid,
    revision: props.revision.version,
    _query: { trackingNumber: number.value.trim(), searchOption: option.value },
  })
}
load()
</script>

<template>
  <Head :title="`${draft.name} · Staff preview`" />
  <main class="flex min-h-screen flex-col bg-muted">
    <header class="grid gap-4 border-b bg-background px-4 py-4 md:px-8">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="grid gap-1">
          <Link
            :href="route('admin.drafts.show', [company.uuid, draft.uuid])"
            class="text-sm underline"
          >
            Back to draft
          </Link>
          <h1 class="text-lg font-semibold">
            {{ draft.name }} · Revision {{ revision.version }}
          </h1>
          <p class="text-sm text-muted-foreground">
            {{ company.name }} · Staff preview
          </p>
        </div>
        <div class="flex gap-1" role="group" aria-label="Preview width">
          <Button
            size="sm"
            :variant="mobile ? 'outline' : 'default'"
            :aria-pressed="!mobile"
            @click="mobile = false"
          >
            Desktop
          </Button>
          <Button
            size="sm"
            :variant="mobile ? 'default' : 'outline'"
            :aria-pressed="mobile"
            @click="mobile = true"
          >
            Mobile
          </Button>
        </div>
      </div>
      <form class="flex flex-wrap items-end gap-3" @submit.prevent="load">
        <DraftSelect
          id="preview-by"
          v-model="option"
          label="Find shipment by"
          :choices="[
            { id: 'bol', name: 'Bill of lading' },
            { id: 'carrierPro', name: 'Carrier PRO' },
          ]"
        />
        <div class="grid flex-1 gap-2 sm:max-w-xs">
          <Label for="preview-number">Shipment number</Label>
          <Input
            id="preview-number"
            v-model="number"
            maxlength="128"
            required
          />
        </div>
        <Button type="submit" variant="outline">Load shipment</Button>
      </form>
    </header>
    <div class="mx-auto w-full flex-1" :class="mobile ? 'max-w-[390px]' : ''">
      <iframe
        v-if="frame"
        :key="lookupAttempt"
        :src="frame"
        title="Classic shipment preview"
        referrerpolicy="same-origin"
        class="block h-[80vh] w-full border-x bg-background"
      />
      <p v-else class="p-6 text-center text-sm text-muted-foreground">
        Enter a shipment number for {{ company.name }} to preview this revision.
      </p>
    </div>
  </main>
</template>
