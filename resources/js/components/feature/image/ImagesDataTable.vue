<script setup>
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { SelectField } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useImagesQuery } from '@/composables/queries/image'

import ImageDestroyDialog from './ImageDestroyDialog.vue'
const props = defineProps({
  initialImages: { type: Array, required: true },
  initialImageTypes: { type: Array, required: true },
})
const { userCan } = useRolesAndPermissions()
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
</script>
<template>
  <section aria-label="Image Library">
    <div class="mb-5 flex flex-wrap items-end gap-4">
      <div class="grid max-w-md grow gap-2">
        <Label for="asset-search">Find an asset</Label
        ><Input
          id="asset-search"
          v-model="query"
          type="search"
          placeholder="Asset name"
        />
      </div>
      <SelectField
        id="asset-kind"
        v-model="kind"
        label="Image type"
        empty-label="All image types"
        :choices="initialImageTypes"
        class="min-w-40"
      />
    </div>
    <p v-if="isError" role="alert">The asset library could not be loaded.</p>
    <div
      v-else-if="selected"
      class="grid items-start gap-5 md:grid-cols-[minmax(0,1fr)_minmax(16rem,.65fr)]"
    >
      <Card class="divide-y overflow-hidden">
        <Button
          v-for="asset in images"
          :key="asset.id"
          type="button"
          variant="ghost"
          class="signal-asset-row"
          :aria-pressed="selected.id === asset.id"
          @click="activeId = asset.id"
        >
          <img
            :src="imageAssetUrl({ filePath: asset.file_path })"
            alt=""
            loading="lazy"
          /><span
            ><strong>{{ asset.name }}</strong
            ><small>{{ asset.image_type?.name }}</small></span
          >
        </Button>
      </Card>
      <Card as="aside" class="p-5" aria-label="Asset inspector">
        <p class="mb-3 font-mono text-xs uppercase text-muted-foreground">
          Selected asset
        </p>
        <img
          :src="imageAssetUrl({ filePath: selected.file_path })"
          :alt="selected.name"
          class="mb-4 max-h-56 w-full rounded bg-muted object-contain"
        />
        <h2 class="text-xl font-semibold">{{ selected.name }}</h2>
        <p class="mb-4 text-sm text-muted-foreground">
          {{ selected.image_type?.name }} ·
          {{ selected.company_usage_count }} company assignments
        </p>
        <div class="flex flex-wrap items-center gap-4">
          <a
            :href="imageAssetUrl({ filePath: selected.file_path })"
            target="_blank"
            rel="noopener"
            class="text-primary underline"
            >View original</a
          ><ImageDestroyDialog
            v-if="userCan('image:destroy')"
            :key="selected.id"
            :image="selected"
            >Delete image</ImageDestroyDialog
          >
        </div>
      </Card>
    </div>
    <p v-else class="rounded border bg-card p-6">No matching assets.</p>
  </section>
</template>
