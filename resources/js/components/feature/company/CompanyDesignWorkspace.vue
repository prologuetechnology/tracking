<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps({
  company: { type: Object, required: true },
  overview: { type: Object, required: true },
  lookup: { type: Object, default: null },
})
const publicLink = computed(() =>
  props.lookup?.trackingNumber
    ? route(`trackShipment.index`, {
        ...props.lookup,
        companyId: props.company.pipeline_company_id,
        brand: props.company.brand ?? undefined,
      })
    : null,
)
</script>
<template>
  <section aria-label="Live design" class="min-w-0">
    <h2 class="mb-4 text-lg font-semibold">Live design</h2>
    <p class="mb-1 text-base font-medium">
      {{ overview.layout }}
    </p>
    <p v-if="overview.liveTheme" class="mb-2 text-sm">
      {{ overview.liveTheme.name
      }}<span v-if="overview.liveTheme.version">
        · v{{ overview.liveTheme.version }}
      </span>
      <span v-else> · Saved appearance </span>
      <span
        v-if="
          overview.liveTheme.latestVersion > overview.liveTheme.version &&
          overview.liveTheme.version
        "
        class="text-muted-foreground"
      >
        · v{{ overview.liveTheme.latestVersion }} available
      </span>
    </p>
    <p
      v-if="overview.live && overview.live !== 'Original Classic'"
      class="break-words text-sm"
    >
      {{ overview.live }}
    </p>
    <Link
      v-if="overview.liveRevision"
      :href="
        route('admin.drafts.preview', [
          company.uuid,
          overview.liveRevision.draftUuid,
          overview.liveRevision.version,
        ])
      "
      class="mt-2 block text-sm text-primary underline"
    >
      View live revision
    </Link>
    <a
      v-if="publicLink"
      :href="publicLink"
      target="_blank"
      rel="noopener noreferrer"
      class="mt-3 inline-flex items-center gap-1 break-all text-sm text-primary underline"
    >
      <span> Track {{ lookup.trackingNumber }} </span>
      <ArrowUpRight aria-hidden="true" class="size-4 shrink-0" />
    </a>
  </section>
</template>
