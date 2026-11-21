<script setup>
import { onKeyStroke, useEventListener } from '@vueuse/core'
import {
  ChevronDown,
  ChevronUp,
  GripVertical,
  RotateCcw,
} from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'

const props = defineProps({
  layout: { type: Object, required: true },
  modules: { type: Object, required: true },
  features: { type: [Object, Array], required: true },
  error: { type: String, default: null },
  previewModules: { type: Array, default: null },
  panel: { type: String, default: `both` },
})
// Vue's compiler requires string literals for named models.
/* eslint-disable quotes */
const orders = defineModel('orders', { type: Object, required: true })
const hidden = defineModel('hidden', { type: Object, required: true })
const visibleFeatures = defineModel('featuresEnabled', {
  type: Array,
  required: true,
})
/* eslint-enable quotes */
const announcement = ref(``)
const rows = ref(null)
const dragged = ref(null)
const dropTarget = ref(null)
const gates = {
  map: `enable_map`,
  documents: `enable_documents`,
  instructions: `special_instructions`,
}
const classic = computed(() => props.layout.id === `classic.v1`)
const order = computed(
  () => orders.value[props.layout.id] ?? props.layout.order,
)
const eligible = computed(() =>
  order.value.filter(
    (key) => !gates[key] || Object.hasOwn(props.features, gates[key]),
  ),
)
const hiddenKeys = computed(() => hidden.value[props.layout.id] ?? [])
const visible = computed(() =>
  eligible.value.filter(
    (key) =>
      !hiddenKeys.value.includes(key) &&
      (!gates[key] || visibleFeatures.value.includes(gates[key])),
  ),
)
const setVisible = (key, enabled) => {
  hidden.value = {
    ...hidden.value,
    [props.layout.id]: enabled
      ? hiddenKeys.value.filter((section) => section !== key)
      : [...new Set([...hiddenKeys.value, key])],
  }
}
const stateLabel = (key) => {
  if (!visible.value.includes(key)) return `Hidden`
  if (props.previewModules === null)
    return key === visible.value[0] ? `First in order` : ``
  if (!props.previewModules.includes(key)) return `No data on this shipment`
  return visible.value.find((section) =>
    props.previewModules.includes(section),
  ) === key
    ? `Featured`
    : ``
}
const featureSwitches = computed(() => Object.entries(props.features))
const setFeature = (key, enabled) => {
  visibleFeatures.value = enabled
    ? [...new Set([...visibleFeatures.value, key])]
    : visibleFeatures.value.filter((item) => item !== key)
}
const setOrder = (next) => {
  orders.value = { ...orders.value, [props.layout.id]: next }
}
const moveTo = (key, target) => {
  const available = [...eligible.value]
  const source = available.indexOf(key)
  if (
    source < 0 ||
    target < 0 ||
    target >= available.length ||
    source === target
  )
    return
  available.splice(source, 1)
  available.splice(target, 0, key)

  // Only reorder the slots staff can see; keep unlicensed sections in place.
  let index = 0
  setOrder(
    order.value.map((section) =>
      eligible.value.includes(section) ? available[index++] : section,
    ),
  )
  announcement.value =
    props.modules[key] +
    ` moved to position ` +
    (target + 1) +
    ` of ` +
    available.length +
    `.`
}
const move = (key, direction) =>
  moveTo(key, eligible.value.indexOf(key) + direction)

let dragPointer = null
const finishDrag = () => {
  dragPointer = null
  dragged.value = null
  dropTarget.value = null
}
watch(() => props.layout.id, finishDrag)
onKeyStroke(`Escape`, finishDrag)
const startDrag = (event, key) => {
  if (
    !event.isPrimary ||
    event.button !== 0 ||
    event.pointerType === `touch` ||
    event.currentTarget.closest(`fieldset:disabled`)
  )
    return
  event.preventDefault()
  event.currentTarget.setPointerCapture(event.pointerId)
  dragPointer = event.pointerId
  dragged.value = key
}
const dragOver = (event) => {
  if (event.pointerId !== dragPointer) return
  // Capture keeps receiving events outside the handle; hit-test the actual row under the pointer.
  const hit = document
    .elementFromPoint(event.clientX, event.clientY)
    ?.closest(`[data-order-key]`)
  const target = hit && rows.value?.contains(hit) ? hit.dataset.orderKey : null
  dropTarget.value = target === dragged.value ? null : target
}
const drop = (event) => {
  if (event.pointerId !== dragPointer) return
  dragOver(event)
  if (dropTarget.value && !rows.value?.closest(`fieldset:disabled`)) {
    moveTo(dragged.value, eligible.value.indexOf(dropTarget.value))
  }
  finishDrag()
}
useEventListener(`pointermove`, dragOver)
useEventListener(`pointerup`, drop)
useEventListener(`pointercancel`, finishDrag)
useEventListener(`blur`, finishDrag)
const reset = () => {
  setOrder([...props.layout.order])
  announcement.value = `Default order restored.`
}
</script>
<template>
  <div class="grid gap-5">
    <div v-if="!classic && panel !== 'features'" class="grid gap-2">
      <p class="text-sm leading-relaxed text-muted-foreground">
        The first section with shipment data appears
        {{ layout.placement.toLowerCase() }}. Visibility here applies to the
        supporting section. The layout keeps its shipment summary.
      </p>
      <ol ref="rows" class="divide-y rounded-md border">
        <li
          v-for="key in eligible"
          :key="key"
          :data-order-key="key"
          class="section-order-row relative flex min-h-12 items-center gap-2 px-3 py-1"
          :data-drop-edge="
            dropTarget === key
              ? eligible.indexOf(dragged) > eligible.indexOf(key)
                ? 'before'
                : 'after'
              : undefined
          "
          :class="{ 'bg-muted/50': dragged === key }"
        >
          <span
            v-if="eligible.length > 1"
            class="section-drag-handle"
            :data-drag-section="key"
            aria-hidden="true"
            :title="'Drag ' + modules[key] + ' to reorder'"
            @pointerdown="startDrag($event, key)"
          >
            <GripVertical class="pointer-events-none size-4" />
          </span>
          <div class="mr-auto min-w-0">
            <span
              class="text-sm font-medium"
              :class="visible.includes(key) ? '' : 'text-muted-foreground'"
              >{{ modules[key] }}</span
            >
            <span class="block text-xs text-muted-foreground">{{
              stateLabel(key)
            }}</span>
          </div>
          <Switch
            :id="'section-visible-' + key"
            :aria-label="'Show ' + modules[key] + ' section'"
            :checked="!hiddenKeys.includes(key)"
            @update:checked="setVisible(key, $event)"
          />
          <Button
            type="button"
            size="icon"
            variant="ghost"
            class="size-8 shrink-0"
            :aria-label="'Move ' + modules[key] + ' up'"
            :disabled="eligible.indexOf(key) <= 0"
            @click="move(key, -1)"
          >
            <ChevronUp class="size-4" aria-hidden="true" />
          </Button>
          <Button
            type="button"
            size="icon"
            variant="ghost"
            class="size-8 shrink-0"
            :aria-label="'Move ' + modules[key] + ' down'"
            :disabled="eligible.at(-1) === key"
            @click="move(key, 1)"
          >
            <ChevronDown class="size-4" aria-hidden="true" />
          </Button>
        </li>
      </ol>
      <Button
        type="button"
        size="sm"
        variant="ghost"
        class="w-fit justify-start px-1 text-muted-foreground"
        :disabled="order.join(',') === layout.order.join(',')"
        @click="reset"
        ><RotateCcw class="mr-2 size-3.5" aria-hidden="true" />Restore layout
        order</Button
      >
      <span role="status" class="sr-only">{{ announcement }}</span>
    </div>
    <p
      v-else-if="classic && panel !== 'features'"
      class="text-sm text-muted-foreground"
    >
      Classic keeps its original section order.
    </p>
    <fieldset
      v-if="featureSwitches.length && panel !== 'sections'"
      class="min-w-0"
    >
      <legend class="mb-2 text-sm font-medium">Visible shipment details</legend>
      <div class="divide-y">
        <div
          v-for="[key, label] in featureSwitches"
          :key="key"
          class="flex min-h-10 items-center justify-between gap-3 py-1"
        >
          <label :for="'feature-' + key" class="cursor-pointer text-sm">{{
            label
          }}</label>
          <Switch
            :id="'feature-' + key"
            :checked="visibleFeatures.includes(key)"
            @update:checked="setFeature(key, $event)"
          />
        </div>
      </div>
    </fieldset>
    <p v-else-if="panel === 'features'" class="text-sm text-muted-foreground">
      There are no optional shipment details enabled for this company.
    </p>
    <p v-if="error" role="alert" class="text-sm text-destructive">
      {{ error }}
    </p>
  </div>
</template>

<style scoped>
.section-drag-handle {
  display: none;
}
@media (min-width: 640px) and (any-pointer: fine) {
  .section-drag-handle {
    @apply -ml-1 flex h-8 w-5 shrink-0 cursor-grab select-none items-center justify-center rounded-sm text-muted-foreground;
  }
  .section-drag-handle:hover {
    @apply bg-muted text-foreground;
  }
  .section-drag-handle:active {
    cursor: grabbing;
  }
  fieldset:disabled .section-drag-handle {
    cursor: default;
    opacity: 0.5;
  }
}
.section-order-row[data-drop-edge]::after {
  content: '';
  position: absolute;
  z-index: 1;
  inset-inline: 0;
  height: 2px;
  background: hsl(var(--primary));
  pointer-events: none;
}
.section-order-row[data-drop-edge='before']::after {
  top: 0;
}
.section-order-row[data-drop-edge='after']::after {
  bottom: 0;
}
</style>
