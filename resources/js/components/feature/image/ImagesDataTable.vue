<script setup>
import { useMediaQuery } from '@vueuse/core'
import { Check, ImageOff, Search } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from '@/components/ui/dialog'
import { SelectField } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useImagesQuery } from '@/composables/queries/image'

import AssetDetails from './AssetDetails.vue'
import AssetPreview from './AssetPreview.vue'
const props = defineProps({
  initialImages: { type: Array, required: true },
  initialImageTypes: { type: Array, required: true },
})
const query = ref(``),
  kind = ref(null),
  activeId = ref(null)
const { data, isError } = useImagesQuery({
  config: { initialData: props.initialImages },
})
const images = computed(() =>
  (data.value || []).filter(
    (image) =>
      (!kind.value || image.image_type_id === Number(kind.value)) &&
      image.name.toLowerCase().includes(query.value.toLowerCase().trim()),
  ),
)
const selected = computed(
  () => images.value.find((i) => i.id === activeId.value) ?? images.value[0],
)
const wide = useMediaQuery(`(min-width: 768px)`)
const inspectorOpen = ref(false)
const lastTrigger = ref(null)
watch(wide, () => {
  inspectorOpen.value = false
})
function inspect(asset, event) {
  activeId.value = asset.id
  if (!wide.value) {
    lastTrigger.value = event.currentTarget
    inspectorOpen.value = true
  }
}
function returnFocus(event) {
  if (lastTrigger.value?.isConnected) {
    event.preventDefault()
    lastTrigger.value.focus()
  }
}
function clearFilters() {
  query.value = ``
  kind.value = null
}
</script>
<template>
  <section aria-label="Image Library">
    <div class="mb-6 flex flex-wrap items-end gap-x-5 gap-y-3">
      <div class="grid min-w-40 max-w-md grow gap-2">
        <Label for="asset-search"> Find an asset </Label>
        <div class="relative">
          <Search
            class="absolute left-3 top-3 size-4 text-muted-foreground"
            aria-hidden="true"
          />
          <Input
            id="asset-search"
            v-model="query"
            type="search"
            class="pl-9"
            placeholder="Search by name"
          />
        </div>
      </div>
      <SelectField
        id="asset-kind"
        v-model="kind"
        label="Image type"
        empty-label="All image types"
        :choices="initialImageTypes"
        class="min-w-40"
      />
      <p role="status" class="ml-auto pb-2 text-sm text-muted-foreground">
        {{ images.length }} {{ images.length === 1 ? 'asset' : 'assets' }}
      </p>
    </div>
    <p v-if="isError" role="alert">The asset library could not be loaded.</p>
    <div
      v-else-if="selected"
      class="grid items-start gap-8 md:grid-cols-[minmax(0,1fr)_minmax(15rem,.42fr)]"
    >
      <ul
        aria-label="Assets"
        class="grid grid-cols-2 gap-x-4 gap-y-6 xl:grid-cols-3"
      >
        <li v-for="asset in images" :key="asset.id" class="min-w-0">
          <Button
            type="button"
            variant="ghost"
            class="asset-tile"
            :aria-pressed="selected.id === asset.id"
            :aria-label="'Inspect ' + asset.name"
            @click="inspect(asset, $event)"
          >
            <AssetPreview
              :asset="asset"
              class="asset-thumbnail aspect-[16/10] w-full rounded-md"
            />
            <span
              v-if="selected.id === asset.id"
              class="absolute right-2 top-2 grid size-5 place-items-center rounded-full bg-primary text-primary-foreground"
            >
              <Check class="size-3" aria-hidden="true" />
            </span>
            <span class="mt-2 block break-words">
              {{ asset.name }}
            </span>
            <span class="text-xs font-normal capitalize text-muted-foreground">
              {{ asset.image_type?.name }}
            </span>
          </Button>
        </li>
      </ul>
      <aside
        v-if="wide"
        aria-label="Asset inspector"
        class="sticky top-5 grid min-w-0 gap-4 rounded-lg bg-card p-5"
      >
        <h2 class="break-words text-xl font-semibold">
          {{ selected.name }}
        </h2>
        <AssetDetails :asset="selected" />
      </aside>
      <Dialog v-else v-model:open="inspectorOpen">
        <DialogContent
          class="max-h-[90dvh] overflow-y-auto"
          @close-auto-focus="returnFocus"
        >
          <DialogTitle class="break-words pr-6">
            {{ selected.name }}
          </DialogTitle>
          <DialogDescription class="sr-only">
            Asset preview, usage, and actions
          </DialogDescription>
          <AssetDetails :asset="selected" />
        </DialogContent>
      </Dialog>
    </div>
    <div v-else class="grid justify-items-center gap-3 py-12">
      <ImageOff class="size-6 text-muted-foreground" aria-hidden="true" />
      <p>
        {{ query || kind ? 'No matching assets.' : 'No assets available.' }}
      </p>
      <Button
        v-if="query || kind"
        size="sm"
        variant="outline"
        @click="clearFilters"
      >
        Clear filters
      </Button>
    </div>
  </section>
</template>
<style scoped>
.asset-tile {
  position: relative;
  display: block;
  width: 100%;
  height: auto;
  padding: 0;
  text-align: start;
  white-space: normal;
}
.asset-tile[aria-pressed='true'] .asset-thumbnail {
  outline: 2px solid hsl(var(--primary));
  outline-offset: -2px;
}
</style>
