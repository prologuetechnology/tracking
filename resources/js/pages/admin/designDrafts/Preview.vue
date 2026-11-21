<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CopyPlus } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import DraftSelect from '@/components/feature/designDraft/DraftSelect.vue'
import ProofArchive from '@/components/feature/designDraft/ProofArchive.vue'
import TrackingStudioLayout from '@/components/layout/page/TrackingStudioLayout.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  proofs: { type: Array, required: true },
  publication: { type: Number, required: true },
  activeProof: { type: String, default: null },
  company: { type: Object, required: true },
  draft: { type: Object, required: true },
  revision: { type: Object, required: true },
})
const copy = useForm({ name: `` })
const fork = () =>
  copy.post(
    route(`admin.drafts.fork`, [
      props.company.uuid,
      props.draft.uuid,
      props.revision.version,
    ]),
  )
const mobile = ref(false)
const number = ref(props.revision.tracking_number ?? ``)
const option = ref(props.revision.search_option)
const stage = ref(null)
const availableWidth = ref(0)
const viewportWidth = computed(() => (mobile.value ? 390 : 1440))
const viewportHeight = computed(() => (mobile.value ? 844 : 900))
const scale = computed(() =>
  Math.min(1, availableWidth.value / viewportWidth.value),
)
let observer
onMounted(() => {
  observer = new ResizeObserver(([entry]) => {
    availableWidth.value = entry.contentRect.width
  })
  observer.observe(stage.value)
})
onBeforeUnmount(() => {
  observer?.disconnect()
  clearTimeout(expiry)
  window.removeEventListener(`message`, handleFrameRefresh)
})
const frame = ref(null)
const capture = ref(null)
const frameElement = ref(null)
const message = ref(``)
let expiry
const clearCapture = (reason = ``) => {
  clearTimeout(expiry)
  capture.value = null
  frame.value = null
  message.value = reason
}
const expire = (token, reason) => {
  if (capture.value?.token === token) clearCapture(reason)
}
const frameLoaded = (event) => {
  const element = event.currentTarget
  if (element !== frameElement.value) return
  try {
    const doc = element.contentDocument
    if (doc?.URL === `about:blank`) return
    const raw =
      doc?.querySelector(`script[data-page]`)?.textContent ??
      doc?.getElementById(`app`)?.dataset.page
    const state = raw ? JSON.parse(raw) : null
    const pinned = state?.props?.proofCapture
    if (pinned?.token) {
      capture.value = pinned
      clearTimeout(expiry)
      expiry = setTimeout(
        () =>
          expire(
            pinned.token,
            `The shipment preview expired. Load the shipment again before exporting.`,
          ),
        Math.max(0, Date.parse(pinned.expiresAt) - Date.now()),
      )
    } else if (state?.component === `admin/designDrafts/Unavailable`) {
      capture.value = null
    } else {
      clearCapture(
        `Could not open the shipment preview. Check your staff session and load the shipment again.`,
      )
    }
  } catch {
    clearCapture(
      `Could not open the shipment preview. Check your staff session and load the shipment again.`,
    )
  }
}
const handleFrameRefresh = (event) => {
  if (
    event.source === frameElement.value?.contentWindow &&
    event.origin === location.origin &&
    event.data?.type === `tracking-studio-refresh`
  )
    load()
}
onMounted(() => window.addEventListener(`message`, handleFrameRefresh))
watch([number, option], () => clearCapture(), { flush: `sync` })
const lookupAttempt = ref(0)
const load = () => {
  if (!number.value.trim()) return
  clearCapture()
  lookupAttempt.value += 1
  frame.value = route(`admin.drafts.render`, {
    company: props.company.uuid,
    draft: props.draft.uuid,
    revision: props.revision.version,
    _query: {
      trackingNumber: number.value.trim(),
      searchOption: option.value,
      capture: 1,
    },
  })
}
load()
</script>

<template>
  <TrackingStudioLayout :company="company">
    <Head :title="`${draft.name} · Staff preview`" />
    <header class="grid gap-4 rounded-lg border bg-card p-4 sm:p-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="grid gap-1">
          <Link
            :href="route('admin.drafts.show', [company.uuid, draft.uuid])"
            class="text-sm underline"
          >
            Back to draft
          </Link>
          <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
            {{ draft.name }} · Revision {{ revision.version }}
          </h1>
          <p class="text-sm text-muted-foreground">
            {{ company.name }} · Staff preview
          </p>
        </div>
        <Dialog>
          <DialogTrigger as-child>
            <Button variant="outline" size="sm">
              <CopyPlus class="mr-2 size-4" aria-hidden="true" />
              Use revision as new draft
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create a named draft</DialogTitle>
              <DialogDescription
                >Start with revision {{ revision.version }} of
                {{ draft.name }}.</DialogDescription
              >
            </DialogHeader>
            <form class="grid gap-3" @submit.prevent="fork">
              <Label for="fork-name">New draft name</Label>
              <Input
                id="fork-name"
                v-model="copy.name"
                required
                maxlength="120"
                :aria-invalid="!!copy.errors.name"
              />
              <p
                v-if="copy.errors.name"
                role="alert"
                class="text-sm text-destructive"
              >
                {{ copy.errors.name }}
              </p>
              <Button
                type="submit"
                class="justify-self-end"
                :disabled="copy.processing"
                >Create draft</Button
              >
            </form>
          </DialogContent>
        </Dialog>
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
        <Button type="submit" variant="outline">{{
          capture ? 'Refresh shipment' : 'Load shipment'
        }}</Button>
        <p v-if="capture" class="text-sm text-muted-foreground" role="status">
          Shipment captured
          <time :datetime="capture.at">{{
            new Date(capture.at).toLocaleString()
          }}</time>
        </p>
      </form>
      <ProofArchive
        :company="company"
        :draft="draft"
        :revision="revision"
        :proofs="proofs"
        :publication="publication"
        :active-proof="activeProof"
        :lookup="
          capture
            ? {
                trackingNumber: number.trim(),
                searchOption: option,
                snapshot_token: capture.token,
              }
            : null
        "
        @snapshot-expired="expire"
      />
    </header>
    <section
      class="mt-5 overflow-hidden rounded-lg border bg-white"
      aria-labelledby="saved-shipment-preview"
    >
      <h2 id="saved-shipment-preview" class="border-b px-4 py-3 font-semibold">
        Live shipment preview
      </h2>
      <div
        ref="stage"
        class="relative mx-auto w-full overflow-hidden bg-muted"
        :style="frame ? { height: viewportHeight * scale + 'px' } : {}"
      >
        <iframe
          v-if="frame"
          :key="lookupAttempt"
          ref="frameElement"
          :src="frame"
          title="Shipment preview"
          referrerpolicy="same-origin"
          class="absolute left-1/2 top-0 block border-0 bg-background"
          :style="{
            width: viewportWidth + 'px',
            height: viewportHeight + 'px',
            maxWidth: 'none',
            transform: 'translateX(-50%) scale(' + scale + ')',
            transformOrigin: 'center top',
          }"
          @load="frameLoaded"
        />
        <p
          v-else-if="message"
          role="alert"
          class="p-6 text-center text-sm text-destructive"
        >
          {{ message }}
        </p>
        <p v-else class="p-6 text-center text-sm text-muted-foreground">
          Enter a shipment number for {{ company.name }} to preview this
          revision.
        </p>
      </div>
    </section>
  </TrackingStudioLayout>
</template>
