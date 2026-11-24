<script setup>
import { Head, Link } from '@inertiajs/vue3'

import ApprovedDesignRevisions from '@/components/feature/designDraft/ApprovedDesignRevisions.vue'
import PublicationHistory from '@/components/feature/designDraft/PublicationHistory.vue'
import RevisionReleaseControls from '@/components/feature/designDraft/RevisionReleaseControls.vue'
import TrackingStudioLayout from '@/components/layout/page/TrackingStudioLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'

defineProps({
  company: { type: Object, required: true },
  approvedRevisions: { type: Object, required: true },
  publications: { type: Array, required: true },
  shipmentLookup: { type: Object, default: null },
  drafts: { type: Object, required: true },
})
</script>

<template>
  <TrackingStudioLayout :company="company">
    <Head :title="`Design Studio · ${company.name}`" />
    <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
          Design Studio
        </h1>
        <p
          v-if="shipmentLookup"
          class="mt-1 break-all font-mono text-sm text-muted-foreground"
        >
          {{ shipmentLookup.searchOption === 'bol' ? 'BOL' : 'Carrier PRO' }} ·
          {{ shipmentLookup.trackingNumber }}
        </p>
      </div>
      <Button as-child>
        <Link
          :href="
            route('admin.drafts.create', {
              company: company.uuid,
              _query: shipmentLookup ?? {},
            })
          "
        >
          Create design draft
        </Link>
      </Button>
    </header>
    <PublicationHistory :company="company" :publications="publications" />
    <Card as="section" class="overflow-hidden" aria-labelledby="named-drafts">
      <h2 id="named-drafts" class="px-4 pb-1 pt-5 font-semibold sm:px-5">
        Saved drafts
      </h2>
      <p v-if="!drafts.data.length" class="p-5 text-sm text-muted-foreground">
        No saved drafts for {{ company.name }} yet.
      </p>
      <ul v-else class="divide-y">
        <li
          v-for="item in drafts.data"
          :key="item.uuid"
          class="grid min-w-0 gap-2 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:gap-5 sm:px-5"
        >
          <div class="min-w-0">
            <Link
              class="break-words font-semibold underline decoration-border underline-offset-4 hover:decoration-current"
              :href="route('admin.drafts.show', [company.uuid, item.uuid])"
            >
              {{ item.name }}
            </Link>
            <p class="mt-1 text-sm text-muted-foreground">
              {{ item.layout }} · Revision {{ item.version }}
            </p>
          </div>
          <div
            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
          >
            <Badge
              v-if="
                publications[0]?.draft_uuid === item.uuid &&
                publications[0].version !== item.version
              "
              variant="secondary"
            >
              Revision {{ publications[0].version }} live
            </Badge>
            <time :datetime="item.updated_at">
              Updated
              {{ new Date(item.updated_at).toLocaleDateString() }}
            </time>
            <RevisionReleaseControls
              v-if="item.version"
              :company="company"
              :draft="item"
              :version="item.version"
              :approved="Boolean(item.approved_at)"
              :live="
                publications[0]?.draft_uuid === item.uuid &&
                publications[0].version === item.version
              "
              :publication="publications[0]?.id ?? 0"
            />
          </div>
        </li>
      </ul>
    </Card>
    <nav
      v-if="drafts.last_page > 1"
      aria-label="Draft pages"
      class="mt-4 flex justify-between"
    >
      <Link
        v-if="drafts.prev_page_url"
        :href="drafts.prev_page_url"
        class="underline"
      >
        Previous page
      </Link>
      <Link
        v-if="drafts.next_page_url"
        :href="drafts.next_page_url"
        class="ml-auto underline"
      >
        Next page
      </Link>
    </nav>
    <ApprovedDesignRevisions
      :company="company"
      :revisions="approvedRevisions"
    />
  </TrackingStudioLayout>
</template>
