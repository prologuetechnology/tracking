<script setup>
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Expand, Monitor, MousePointer2, Smartphone } from 'lucide-vue-next'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { attachStudioInspector } from '@/components/feature/designDraft/editor/useStudioInspector'
import { Button } from '@/components/ui/button'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'

const props = defineProps({
  company: { type: Object, required: true },
  form: { type: Object, required: true },
  liveDesign: { type: Object, required: true },
})
const mode = defineModel({ type: String, default: `draft` })
const emit = defineEmits([`modules`, `inspect`])
const page = usePage()
const container = ref(null)
const panel = ref(null)
const frame = ref(null)
const space = ref(0)
const mobile = ref(false)
const busy = ref(false)
const error = ref(``)
const html = ref(``)
const token = ref(null)
const capturedAt = ref(null)
const live = ref(props.liveDesign)
const renderId = ref(null)
const inspecting = ref(false)
let observer, request, timer, detachInspector
let sequence = 0
let renderedLayout = null
let scrollPosition = null
const width = computed(() => (mobile.value ? 390 : 1440))
const height = computed(() => (mobile.value ? 844 : 900))
const scale = computed(() => Math.min(1, space.value / width.value))
const canLoad = computed(() => !!props.form.tracking_number?.trim())
const lookup = computed(() => ({
  tracking_number: props.form.tracking_number?.trim() ?? ``,
  search_option: props.form.search_option,
}))
const lookupKey = computed(() => JSON.stringify(lookup.value))
const design = computed(() => {
  const data = props.form.data()
  delete data.name
  delete data.tracking_number
  delete data.search_option
  return data
})
const signature = computed(() =>
  mode.value === `live` ? `live` : JSON.stringify(design.value),
)
const capturedTime = computed(() =>
  capturedAt.value
    ? new Date(capturedAt.value).toLocaleTimeString(undefined, {
        hour: `numeric`,
        minute: `2-digit`,
      })
    : ``,
)
const disableInspector = () => {
  detachInspector?.()
  detachInspector = null
  inspecting.value = false
}
const bindInspector = () => {
  detachInspector?.()
  detachInspector = null
  const doc = frame.value?.contentDocument
  if (inspecting.value && mode.value === `draft` && doc?.body) {
    detachInspector = attachStudioInspector(
      doc,
      (field, section) => {
        disableInspector()
        emit(`inspect`, field, section)
      },
      () => {
        disableInspector()
        panel.value
          ?.querySelector(`[aria-label="Inspect preview element"]`)
          ?.focus()
      },
    )
  }
}
watch(inspecting, bindInspector)
const clearFrame = () => {
  disableInspector()
  html.value = ``
  renderedLayout = null
  scrollPosition = null
  renderId.value = null
  emit(`modules`, null)
}
const cancel = () => {
  clearTimeout(timer)
  request?.abort()
  sequence++
}
const render = async (fresh = false) => {
  if (!canLoad.value || (!fresh && !token.value)) return
  cancel()
  const current = sequence
  const submitted = signature.value
  const controller = new AbortController()
  request = controller
  if (fresh) {
    token.value = null
    capturedAt.value = null
    clearFrame()
  }
  busy.value = true
  error.value = ``
  try {
    const { data } = await axios.post(
      route(`admin.drafts.studio-preview`, props.company.uuid),
      {
        ...(mode.value === `draft`
          ? { ...design.value, draft_uuid: page.props.draft?.uuid ?? null }
          : {}),
        ...lookup.value,
        preview_mode: mode.value,
        snapshot_token: token.value,
      },
      { signal: controller.signal, headers: { Accept: `application/json` } },
    )
    if (current !== sequence) return
    if (typeof data.html !== `string` || !data.snapshot_token)
      throw new Error(`Preview unavailable`)
    token.value = data.snapshot_token
    capturedAt.value = data.captured_at
    live.value = data.live_design
    if (submitted !== signature.value) {
      schedule()
      return
    }
    const layout =
      mode.value === `draft` ? props.form.layout : live.value.layout
    const windowInFrame = frame.value?.contentDocument?.defaultView
    scrollPosition =
      windowInFrame && renderedLayout === layout ? windowInFrame.scrollY : null
    renderedLayout = layout
    renderId.value = data.render_id
    html.value = data.html
  } catch (e) {
    if (current !== sequence || axios.isCancel(e)) return
    clearFrame()
    if (e.response?.data?.errors?.snapshot_token) {
      token.value = null
      capturedAt.value = null
    }
    error.value =
      Object.values(e.response?.data?.errors ?? {}).flat()[0] ??
      (e.response?.status === 429
        ? `Too many preview requests. Try again shortly.`
        : `Could not load the preview. Check your staff session and try again.`)
  } finally {
    if (current === sequence) busy.value = false
  }
}
const schedule = () => {
  if (!token.value) return
  cancel()
  emit(`modules`, null)
  busy.value = true
  timer = setTimeout(() => render(), 350)
}
watch(signature, schedule)
watch(mode, () => {
  clearFrame()
})
watch(lookupKey, () => {
  cancel()
  clearFrame()
  token.value = null
  capturedAt.value = null
  error.value = ``
  busy.value = false
})
const frameMessage = (event) => {
  if (
    event.source !== frame.value?.contentWindow ||
    event.origin !== location.origin
  )
    return
  if (event.data?.type === `tracking-studio-refresh`) {
    render(true)
  } else if (
    event.data?.type === `tracking-studio-modules` &&
    mode.value === `draft` &&
    !busy.value &&
    event.data.renderId === renderId.value &&
    Array.isArray(event.data.modules)
  ) {
    emit(`modules`, event.data.modules)
  }
}
onMounted(() => {
  mobile.value = window.matchMedia(`(max-width: 640px)`).matches
  observer = new ResizeObserver((entries) => {
    space.value = entries[0].contentRect.width
  })
  observer.observe(container.value)
  window.addEventListener(`message`, frameMessage)
  if (page.props.draft && canLoad.value) render(true)
})
onBeforeUnmount(() => {
  cancel()
  detachInspector?.()
  observer?.disconnect()
  window.removeEventListener(`message`, frameMessage)
})
const mountFrame = (element) => {
  if (element === frame.value) return
  frame.value = element
  if (!element) return
  // A fresh document inherits the staff page URL, which Inertia needs to resolve relative routes.
  const doc = element.contentDocument
  doc.open()
  doc.write(html.value)
  doc.close()
}
const frameLoaded = () => {
  bindInspector()
  if (scrollPosition !== null)
    frame.value?.contentDocument?.defaultView?.scrollTo(0, scrollPosition)
}
const expand = () =>
  document.fullscreenElement
    ? document.exitFullscreen()
    : panel.value?.requestFullscreen?.()
const toggleInspect = async () => {
  inspecting.value = !inspecting.value
  if (inspecting.value) {
    await nextTick()
    frame.value?.contentWindow?.focus()
    frame.value?.contentDocument
      ?.querySelector(`[data-design-field], [data-section]`)
      ?.focus()
  }
}
</script>
<template>
  <section
    ref="panel"
    class="fullscreen:overflow-auto min-w-0 overflow-hidden rounded-xl bg-muted text-foreground"
    aria-labelledby="studio-preview-title"
  >
    <header
      class="flex flex-wrap items-center justify-between gap-3 bg-card px-4 py-3"
    >
      <div>
        <h2 id="studio-preview-title" class="font-semibold">
          Shipment preview
        </h2>
        <p class="text-xs text-muted-foreground">
          {{ mobile ? '390 px mobile' : '1440 px desktop' }}
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-1">
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
          v-if="html && mode === 'draft' && form.layout !== 'classic.v1'"
          type="button"
          size="icon"
          :variant="inspecting ? 'secondary' : 'ghost'"
          aria-label="Inspect preview element"
          :aria-pressed="inspecting"
          @click="toggleInspect"
          ><MousePointer2 class="size-4"
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
    <div class="grid gap-2 bg-background px-4 py-3">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <ToggleGroup
          aria-label="Preview design"
          type="single"
          size="sm"
          class="signal-segment"
          :model-value="mode"
          @update:model-value="$event && (mode = $event)"
          ><ToggleGroupItem value="draft">Draft</ToggleGroupItem
          ><ToggleGroupItem value="live">Live</ToggleGroupItem></ToggleGroup
        >
        <Button
          type="button"
          size="sm"
          variant="outline"
          :disabled="!canLoad || (busy && !token)"
          @click="render(true)"
          >{{ token ? 'Refresh shipment' : 'Load shipment' }}</Button
        >
      </div>
      <p class="text-xs text-muted-foreground">
        Currently live · {{ live.label }}
      </p>
      <p v-if="inspecting" class="text-xs">
        Select an element to edit its settings. Use Tab and Enter, or Escape to
        exit.
      </p>
      <p role="status" aria-live="polite" class="text-xs text-muted-foreground">
        {{
          busy
            ? token
              ? 'Updating preview…'
              : 'Loading shipment…'
            : capturedAt
              ? 'Shipment captured at ' + capturedTime
              : ''
        }}
      </p>
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
        :key="renderId"
        :ref="mountFrame"
        :title="
          mode === 'draft'
            ? 'Unsaved shipment preview'
            : 'Published shipment preview'
        "
        referrerpolicy="same-origin"
        class="absolute left-1/2 top-0 border-0 bg-white"
        :style="{
          width: width + 'px',
          height: height + 'px',
          maxWidth: 'none',
          transform: 'translateX(-50%) scale(' + scale + ')',
          transformOrigin: 'center top',
        }"
        @load="frameLoaded"
      />
      <div
        v-else
        class="grid min-h-52 place-content-center gap-3 p-8 text-center text-sm text-muted-foreground"
      >
        <Monitor class="mx-auto size-8" aria-hidden="true" />
        <p>
          {{
            busy
              ? 'Preparing preview…'
              : 'Load a shipment from this company to preview its design.'
          }}
        </p>
      </div>
    </div>
  </section>
</template>
