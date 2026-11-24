<script setup>
import { Monitor, Smartphone } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { Button } from '@/components/ui/button'

import { previewInput } from './reviewStyles'
const props = defineProps({
  styleValue: { type: Object, required: true },
  banner: { type: String, required: true },
})
const emit = defineEmits([`derived`])
const container = ref(null),
  viewport = ref(`desktop`),
  available = ref(0),
  frame = ref(null)
const url = ref(``),
  loading = ref(false),
  failed = ref(false)
const width = computed(() => (viewport.value === `mobile` ? 390 : 1440))
const scale = computed(() => Math.min(1, available.value / width.value))
const height = ref(1040)
let timer, observer, contentObserver
const input = computed(() => previewInput(props.styleValue, props.banner))
watch(
  input,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      const next = route(`admin.portal-review.preview`, input.value)
      if (next !== url.value) {
        loading.value = true
        failed.value = false
        url.value = next
      }
    }, 180)
  },
  { deep: true, immediate: true },
)
function loaded() {
  const doc = frame.value?.contentDocument
  if (!doc || !url.value || doc.URL === `about:blank`) return
  contentObserver?.disconnect()
  try {
    const raw =
      doc.querySelector(`script[data-page]`)?.textContent ??
      doc.getElementById(`app`)?.dataset.page
    const page = JSON.parse(raw)
    if (!page.props.reviewDerivedTokens) throw new Error()
    emit(`derived`, page.props.reviewDerivedTokens)
    const measure = () => {
      height.value = Math.max(
        850,
        doc.querySelector(`[data-tracking-layout]`)?.scrollHeight ?? 900,
      )
    }
    measure()
    contentObserver = new ResizeObserver(measure)
    contentObserver.observe(doc.querySelector(`main`) ?? doc.body)
    failed.value = false
  } catch {
    failed.value = true
  }
  loading.value = false
}
onMounted(() => {
  observer = new ResizeObserver(
    ([entry]) => (available.value = entry.contentRect.width),
  )
  observer.observe(container.value)
})
onBeforeUnmount(() => {
  clearTimeout(timer)
  observer?.disconnect()
  contentObserver?.disconnect()
})
</script>
<template>
  <section class="canvas" aria-label="Shipment preview">
    <header>
      <div>
        <strong>Shipment preview</strong>
        <span>Jayco · Follow the journey</span>
      </div>
      <div class="device-tools">
        <Button
          size="sm"
          variant="ghost"
          aria-label="Desktop preview"
          :aria-pressed="viewport === 'desktop'"
          @click="viewport = 'desktop'"
        >
          <Monitor class="size-4" />
        </Button>
        <Button
          size="sm"
          variant="ghost"
          aria-label="Mobile preview"
          :aria-pressed="viewport === 'mobile'"
          @click="viewport = 'mobile'"
        >
          <Smartphone class="size-4" />
        </Button>
      </div>
    </header>
    <div ref="container" class="canvas-body" :aria-busy="loading">
      <p v-if="failed" role="alert">
        Preview unavailable. Refresh the review and try again.
      </p>
      <div
        v-if="url"
        class="canvas-window"
        :style="{ width: width * scale + 'px', height: height * scale + 'px' }"
      >
        <iframe
          ref="frame"
          :src="url"
          title="Jayco shipment preview"
          :style="{
            width: width + 'px',
            height: height + 'px',
            transform: 'scale(' + scale + ')',
          }"
          @load="loaded"
        />
      </div>
    </div>
  </section>
</template>
<style scoped>
.canvas {
  overflow: hidden;
  border: 1px solid var(--line);
  border-radius: 15px;
  background: #e8e9e4;
  min-width: 0;
  align-self: start;
}
.canvas header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid var(--line);
}
.canvas header strong,
.canvas header span {
  display: block;
}
.canvas header span {
  font-size: 12px;
  color: var(--sub);
}
.device-tools {
  display: flex;
}
.device-tools button[aria-pressed='true'] {
  background: #e6e9df;
}
.canvas-body {
  margin: 18px;
  min-height: 300px;
}
.canvas-window {
  margin: auto;
  position: relative;
  overflow: hidden;
  border-radius: 5px;
  background: white;
  box-shadow: 0 2px 11px #17291a16;
}
.canvas iframe {
  border: 0;
  position: absolute;
  top: 0;
  left: 0;
  transform-origin: top left;
}
</style>
