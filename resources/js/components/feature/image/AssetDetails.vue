<script setup>
import { ExternalLink } from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { imageAssetUrl } from '@/composables/hooks/disks'

import AssetPreview from './AssetPreview.vue'
import ImageDestroyDialog from './ImageDestroyDialog.vue'
defineProps({ asset: { type: Object, required: true } })
const { userCan } = useRolesAndPermissions()
const dates = new Intl.DateTimeFormat(undefined, {
  year: `numeric`,
  month: `short`,
  day: `numeric`,
})
</script>
<template>
  <div class="grid min-w-0 gap-4">
    <AssetPreview :asset="asset" class="aspect-[16/9] w-full rounded-md" />
    <dl
      class="grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-3 gap-y-2 py-3 text-sm"
    >
      <dt class="text-muted-foreground">Image type</dt>
      <dd class="text-right capitalize">
        {{ asset.image_type?.name ?? 'Unknown' }}
      </dd>
      <dt class="text-muted-foreground">Company assignments</dt>
      <dd class="text-right font-mono">
        {{ asset.company_usage_count }}
      </dd>
      <template v-if="asset.created_at">
        <dt class="text-muted-foreground">Uploaded</dt>
        <dd class="text-right">
          {{ dates.format(new Date(asset.created_at)) }}
        </dd>
      </template>
    </dl>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <Button
        v-if="asset.file_path"
        as-child
        size="sm"
        variant="outline"
        class="gap-2"
      >
        <a
          :href="imageAssetUrl({ filePath: asset.file_path })"
          target="_blank"
          rel="noopener"
        >
          View original <ExternalLink class="size-4" />
        </a>
      </Button>
      <ImageDestroyDialog
        v-if="userCan('image:destroy')"
        :key="asset.id"
        :image="asset"
      >
        Delete image
      </ImageDestroyDialog>
    </div>
  </div>
</template>
