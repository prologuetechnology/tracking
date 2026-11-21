<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { Check, Copy, RefreshCw, Share2 } from 'lucide-vue-next'
import { ref } from 'vue'

import { Button } from '@/components/ui/button'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

const page = usePage()
const { company, reference } = useTrackingDesign()
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
      <img
        v-if="company.logo?.file_path"
        :src="imageAssetUrl({ filePath: company.logo.file_path })"
        :alt="company.name"
        class="max-h-12 max-w-36 object-contain sm:max-w-48"
      />
      <strong v-else class="break-words text-xl tracking-tight">{{
        company.name
      }}</strong>
      <div class="flex gap-1">
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
