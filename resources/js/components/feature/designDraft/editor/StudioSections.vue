<script setup>
import { ChevronDown, ChevronUp, RotateCcw } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'

const props = defineProps({
  layout: { type: Object, required: true },
  modules: { type: Object, required: true },
  features: { type: [Object, Array], required: true },
  error: { type: String, default: null },
})
// Vue's compiler requires string literals for named models.
/* eslint-disable quotes */
const orders = defineModel('orders', { type: Object, required: true })
const visibleFeatures = defineModel('featuresEnabled', {
  type: Array,
  required: true,
})
/* eslint-enable quotes */
const announcement = ref(``)
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
const visible = computed(() =>
  eligible.value.filter(
    (key) => !gates[key] || visibleFeatures.value.includes(gates[key]),
  ),
)
const featureSwitches = computed(() => Object.entries(props.features))
const setFeature = (key, enabled) => {
  visibleFeatures.value = enabled
    ? [...new Set([...visibleFeatures.value, key])]
    : visibleFeatures.value.filter((item) => item !== key)
}
const setOrder = (next) => {
  orders.value = { ...orders.value, [props.layout.id]: next }
}
const move = (key, direction) => {
  const other = visible.value[visible.value.indexOf(key) + direction]
  if (!other || !visible.value.includes(key)) return
  const nextOrder = [...order.value]
  const source = nextOrder.indexOf(key)
  const target = nextOrder.indexOf(other)
  nextOrder[source] = other
  nextOrder[target] = key
  setOrder(nextOrder)
  announcement.value =
    props.modules[key] + ` moved ` + (direction < 0 ? `up.` : `down.`)
}
const reset = () => {
  setOrder([...props.layout.order])
  announcement.value = `Default order restored.`
}
</script>
<template>
  <div class="grid gap-5">
    <div v-if="!classic" class="grid gap-2">
      <p class="text-sm leading-relaxed text-muted-foreground">
        The first section with shipment data appears
        {{ layout.placement.toLowerCase() }}.
      </p>
      <ol class="divide-y rounded-md border">
        <li
          v-for="key in eligible"
          :key="key"
          :data-order-key="key"
          class="flex min-h-12 items-center gap-2 px-3 py-1"
        >
          <div class="mr-auto min-w-0">
            <span
              class="text-sm font-medium"
              :class="visible.includes(key) ? '' : 'text-muted-foreground'"
              >{{ modules[key] }}</span
            >
            <span
              v-if="visible[0] === key"
              class="ml-1 text-xs text-muted-foreground"
              >Featured</span
            >
            <span
              v-else-if="!visible.includes(key)"
              class="ml-1 text-xs text-muted-foreground"
              >Hidden</span
            >
          </div>
          <Button
            type="button"
            size="icon"
            variant="ghost"
            class="size-8 shrink-0"
            :aria-label="'Move ' + modules[key] + ' up'"
            :disabled="visible.indexOf(key) <= 0"
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
            :disabled="!visible.includes(key) || visible.at(-1) === key"
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
    <p v-else class="text-sm text-muted-foreground">
      Classic keeps its original section order.
    </p>
    <fieldset v-if="featureSwitches.length" class="min-w-0">
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
    <p v-if="error" role="alert" class="text-sm text-destructive">
      {{ error }}
    </p>
  </div>
</template>
