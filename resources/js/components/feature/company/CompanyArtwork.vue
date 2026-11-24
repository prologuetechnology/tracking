<script setup>
import { computed, ref, watch } from 'vue'

import { imageAssetUrl } from '@/composables/hooks/disks'

const props = defineProps({ company: { type: Object, required: true } })
const bannerFailed = ref(false)
const logoFailed = ref(false)
watch(
  () => props.company.banner?.file_path,
  () => {
    bannerFailed.value = false
  },
)
watch(
  () => props.company.logo?.file_path,
  () => {
    logoFailed.value = false
  },
)
const initials = computed(() =>
  props.company.name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join(``)
    .toUpperCase(),
)
const tone = computed(() => props.company.theme?.colors?.root?.primary)
</script>
<template>
  <div
    class="company-artwork"
    aria-hidden="true"
    :class="{
      'company-artwork-plain': !company.banner?.file_path || bannerFailed,
    }"
    :style="{ '--company-tone': tone ? 'hsl(' + tone + ')' : undefined }"
  >
    <img
      v-if="company.banner?.file_path && !bannerFailed"
      :src="imageAssetUrl({ filePath: company.banner.file_path })"
      class="company-banner"
      alt=""
      loading="lazy"
      @error="bannerFailed = true"
    />
    <span
      class="company-monogram"
      :class="{ 'company-logo-plate': company.logo?.file_path && !logoFailed }"
    >
      <img
        v-if="company.logo?.file_path && !logoFailed"
        :src="imageAssetUrl({ filePath: company.logo.file_path })"
        alt=""
        loading="lazy"
        @error="logoFailed = true"
      />
      <template v-else>
        {{ initials }}
      </template>
    </span>
  </div>
</template>
<style scoped>
.company-artwork {
  --company-tone: hsl(var(--primary));
  position: relative;
  display: grid;
  align-items: end;
  overflow: hidden;
  padding: 1rem;
  isolation: isolate;
  background-color: color-mix(
    in srgb,
    var(--company-tone) 15%,
    hsl(var(--card))
  );
}
.company-artwork-plain {
  align-items: center;
  justify-items: center;
}
.company-artwork-plain .company-monogram:not(.company-logo-plate) {
  width: auto;
  height: auto;
  background: transparent;
  color: color-mix(in srgb, var(--company-tone) 25%, hsl(var(--foreground)));
  box-shadow: none;
  font-size: 2.25rem;
  letter-spacing: -0.05em;
}
.company-banner {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: -1;
}
.company-monogram {
  display: grid;
  place-items: center;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: var(--radius);
  background: hsl(var(--card));
  color: hsl(var(--foreground));
  font-weight: 700;
  font-size: 1.25rem;
  box-shadow: 0 1px 5px #0002;
}
.company-logo-plate {
  width: 5.25rem;
  background: white;
  padding: 0.45rem;
}
.company-logo-plate img {
  width: 100%;
  height: 100%;
  min-height: 0;
  object-fit: contain;
}
</style>
