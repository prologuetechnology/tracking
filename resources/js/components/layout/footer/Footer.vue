<script setup>
import { usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { computed } from 'vue'

import { imageAssetUrl } from '@/composables/hooks/disks'

const page = usePage()
const artwork = computed(() => page.props.initialCompany?.footer)
const image = computed(
  () =>
    artwork.value?.file_path &&
    imageAssetUrl({ filePath: artwork.value.file_path }),
)
</script>
<template>
  <footer
    data-tracking-footer
    data-design-field="footer"
    class="mt-auto w-full shrink-0 border-t border-border bg-card text-card-foreground"
  >
    <div
      v-if="image"
      class="mx-auto w-full max-w-6xl px-4 pt-5 text-center sm:px-8"
    >
      <img
        :src="image"
        :alt="artwork.name || ''"
        class="mx-auto block h-auto max-h-48 max-w-full object-contain"
      />
    </div>
    <p
      class="flex min-h-14 flex-wrap items-center justify-center gap-x-1.5 px-4 py-3 text-center text-sm"
    >
      <a
        href="https://prologuetechnology.com/"
        target="_blank"
        rel="noopener noreferrer"
        class="font-semibold underline decoration-primary/60 underline-offset-4 hover:decoration-current"
      >
        {{ page.props.app.name }}
      </a>
      <span>© {{ dayjs(page.props.frozenAt || undefined).year() }}</span>
    </p>
  </footer>
</template>

<style scoped>
footer {
  border-block-start: 2px solid hsl(var(--primary) / 0.28);
  background-image: linear-gradient(
    hsl(var(--primary) / 0.045),
    hsl(var(--primary) / 0.045)
  );
}
</style>
