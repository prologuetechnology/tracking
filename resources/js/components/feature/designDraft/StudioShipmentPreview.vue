<script setup>
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Expand, Monitor, Smartphone } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { Button } from '@/components/ui/button'

const props = defineProps({
  company: { type: Object, required: true },
  form: { type: Object, required: true },
})
const page = usePage()
const container = ref(null)
const panel = ref(null)
const frame = ref(null)
const space = ref(0)
const mobile = ref(false)
const busy = ref(false)
const error = ref(``)
const html = ref(``)
const last = ref(``)
let observer, request
const width = computed(() => (mobile.value ? 390 : 1440))
const height = computed(() => (mobile.value ? 844 : 900))
const scale = computed(() => Math.min(1, space.value / width.value))
const input = computed(() => ({
  ...props.form.data(),
  draft_uuid: page.props.draft?.uuid ?? null,
}))
const fingerprint = computed(() => JSON.stringify(input.value))
const update = async () => {
  if (!input.value.tracking_number?.trim() || busy.value) return
  busy.value = true
  error.value = ``
  const signature = fingerprint.value
  request = new AbortController()
  try {
    const { data } = await axios.post(
      route(`admin.drafts.studio-preview`, props.company.uuid),
      input.value,
      { signal: request.signal, headers: { Accept: `application/json` } },
    )
    if (typeof data.html !== `string`) throw new Error(`Preview unavailable`)
    html.value = data.html
    last.value = signature
  } catch (e) {
    if (!axios.isCancel(e)) {
      html.value = ``
      error.value =
        Object.values(e.response?.data?.errors ?? {}).flat()[0] ??
        `Could not load the preview. Check your staff session and try again.`
    }
  } finally {
    busy.value = false
  }
}
const refreshFrame = (event) => {
  if (
    event.source === frame.value?.contentWindow &&
    event.origin === location.origin &&
    event.data?.type === `tracking-studio-refresh`
  )
    update()
}
onMounted(() => {
  observer = new ResizeObserver((entries) => {
    space.value = entries[0].contentRect.width
  })
  observer.observe(container.value)
  window.addEventListener(`message`, refreshFrame)
})
onBeforeUnmount(() => {
  observer?.disconnect()
  request?.abort()
  window.removeEventListener(`message`, refreshFrame)
})
const expand = () =>
  document.fullscreenElement
    ? document.exitFullscreen()
    : panel.value?.requestFullscreen?.()
</script>
<template>
  <section
    ref="panel"
    class="min-w-0 overflow-hidden rounded-xl border bg-muted text-foreground"
    aria-labelledby="studio-preview-title"
  >
    <header
      class="flex flex-wrap items-center justify-between gap-3 border-b bg-card px-4 py-3"
    >
      <div>
        <h2 id="studio-preview-title" class="font-semibold">
          Shipment preview
        </h2>
        <p class="text-xs text-muted-foreground">
          {{ mobile ? '390 px mobile' : '1440 px desktop' }}
        </p>
      </div>
      <div class="flex items-center gap-1">
        <Button
          type="button"
          size="icon"
          :variant="mobile ? 'ghost' : 'secondary'"
          aria-label="Desktop preview"
          :aria-pressed="!mobile"
          @click="mobile = false"
          ><Monitor class="size-4"
        /></Button>
        <Button
          type="button"
          size="icon"
          :variant="mobile ? 'secondary' : 'ghost'"
          aria-label="Mobile preview"
          :aria-pressed="mobile"
          @click="mobile = true"
          ><Smartphone class="size-4"
        /></Button>
        <Button
          v-if="html"
          type="button"
          size="icon"
          variant="ghost"
          aria-label="Expand preview"
          @click="expand"
          ><Expand class="size-4"
        /></Button>
      </div>
    </header>
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-b bg-background px-4 py-3 text-sm"
    >
      <span role="status" aria-live="polite">{{
        busy
          ? 'Loading shipment…'
          : html
            ? last === fingerprint
              ? 'Showing current draft settings'
              : 'Changes since last preview'
            : 'Use a shipment from this company.'
      }}</span>
      <Button
        type="button"
        size="sm"
        :disabled="busy || !form.tracking_number?.trim()"
        @click="update"
        >Update preview</Button
      >
    </div>
    <p
      v-if="error"
      role="alert"
      class="m-4 rounded border bg-card p-3 text-sm text-destructive"
    >
      {{ error }}
    </p>
    <div
      ref="container"
      class="relative w-full overflow-hidden"
      :style="html ? { height: height * scale + 'px' } : {}"
    >
      <iframe
        v-if="html"
        ref="frame"
        :srcdoc="html"
        title="Unsaved shipment preview"
        referrerpolicy="same-origin"
        class="absolute left-1/2 top-0 border-0 bg-white"
        :style="{
          width: width + 'px',
          height: height + 'px',
          maxWidth: 'none',
          transform: 'translateX(-50%) scale(' + scale + ')',
          transformOrigin: 'center top',
        }"
      />
      <div
        v-else
        class="grid min-h-52 place-content-center gap-3 p-8 text-center text-sm text-muted-foreground"
      >
        <Monitor class="mx-auto size-8" aria-hidden="true" />
        <p>Load a shipment to see this design.</p>
      </div>
    </div>
  </section>
</template>
