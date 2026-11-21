<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { Box, Check, Copy, RefreshCw, Share2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

const page = usePage()
const refined = computed(() => page.props.presentationVersion === 2)
const { company, reference, shipment } = useTrackingDesign()
const shareSupported = typeof navigator.share === `function`
const copied = ref(false)
const refreshing = ref(false)
const copy = async () => {
  try {
    await navigator.clipboard.writeText(location.href)
    copied.value = true
  } catch {
    window.prompt(`Copy tracking link`, location.href)
  }
}
const share = async () => {
  try {
    await navigator.share({
      title: company.value.name,
      text: reference.value,
      url: location.href,
    })
  } catch (error) {
    if (error.name !== `AbortError`) await copy()
  }
}
const refresh = () => {
  if (page.props.isStudioPreview) {
    window.parent.postMessage(
      { type: `tracking-studio-refresh` },
      window.origin,
    )
    return
  }
  router.reload({
    onStart: () => (refreshing.value = true),
    onFinish: () => (refreshing.value = false),
  })
}
</script>
<template>
  <header class="border-b border-border bg-card">
    <div
      class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-10"
    >
      <div
        data-design-field="logo"
        :class="
          refined ? 'flex min-w-0 items-center gap-2.5 sm:gap-4' : 'contents'
        "
      >
        <img
          v-if="company.logo?.file_path"
          :src="imageAssetUrl({ filePath: company.logo.file_path })"
          :alt="company.name"
          :class="
            refined
              ? 'max-h-9 max-w-24 object-contain sm:max-h-11 sm:max-w-40'
              : 'max-h-12 max-w-36 object-contain sm:max-w-48'
          "
        />
        <strong v-else class="break-words text-xl tracking-tight">{{
          company.name
        }}</strong>
        <span
          v-if="refined"
          class="hidden border-l pl-3 text-sm font-medium sm:inline"
          >Shipment tracking</span
        >
      </div>
      <div class="flex gap-1" :class="{ 'items-center': refined }">
        <span
          v-if="refined && shipment.carrierName"
          class="mr-2 hidden max-w-44 items-center gap-1.5 break-words text-xs text-muted-foreground md:inline-flex"
        >
          <Box aria-hidden="true" class="size-3.5 shrink-0" />{{
            shipment.carrierName
          }}
        </span>
        <Button
          type="button"
          size="sm"
          variant="ghost"
          aria-label="Refresh shipment"
          :disabled="refreshing"
          @click="refresh"
          ><RefreshCw class="size-4" /><span class="ml-2 hidden sm:inline"
            >Refresh</span
          ></Button
        >
        <template v-if="!page.props.isDraftPreview">
          <Button
            v-if="shareSupported"
            variant="ghost"
            size="sm"
            aria-label="Share tracking link"
            @click="share"
            ><Share2 class="size-4"
          /></Button>
          <Button
            type="button"
            size="sm"
            variant="outline"
            aria-label="Copy tracking link"
            @click="copy"
            ><component :is="copied ? Check : Copy" class="mr-2 size-4" />{{
              copied ? 'Copied' : 'Copy link'
            }}</Button
          >
        </template>
      </div>
    </div>
  </header>
</template>
