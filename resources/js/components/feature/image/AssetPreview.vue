<script setup>
import { ImageOff } from 'lucide-vue-next'
import { ref, watch } from 'vue'

import { imageAssetUrl } from '@/composables/hooks/disks'
const props = defineProps({ asset: { type: Object, required: true } })
const failed = ref(false)
watch(
  () => props.asset.file_path,
  () => {
    failed.value = false
  },
)
</script>
<template>
  <span class="asset-preview">
    <img
      v-if="asset.file_path && !failed"
      :src="imageAssetUrl({ filePath: asset.file_path })"
      loading="lazy"
      alt=""
      @error="failed = true"
    />
    <span
      v-else
      class="inline-flex items-center gap-2 text-xs text-muted-foreground"
    >
      <ImageOff class="size-4" />
      Preview unavailable
    </span>
  </span>
</template>
<style scoped>
.asset-preview {
  display: grid;
  place-items: center;
  position: relative;
  overflow: hidden;
  background-color: hsl(var(--card));
  background-image: repeating-conic-gradient(
    hsl(var(--muted) / 0.55) 0% 25%,
    transparent 0% 50%
  );
  background-size: 16px 16px;
}
.asset-preview img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 0.65rem;
}
</style>
