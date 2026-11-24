<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight } from 'lucide-vue-next'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'

const props = defineProps({
  company: { type: Object, required: true },
  overview: { type: Object, required: true },
  lookup: { type: Object, default: null },
})
const params = computed(() => ({
  company: props.company.uuid,
  _query: props.lookup ?? {},
}))
const publicLink = computed(() =>
  props.lookup?.trackingNumber
    ? route(`trackShipment.index`, {
        ...props.lookup,
        companyId: props.company.pipeline_company_id,
        brand: props.company.brand ?? undefined,
      })
    : null,
)
const proofLink = (proof) =>
  route(`admin.drafts.preview`, [
    props.company.uuid,
    proof.draftUuid,
    proof.version,
  ]) +
  `#proof-` +
  proof.uuid
</script>
<template>
  <section
    aria-label="Company design workspace"
    class="mb-6 grid gap-4 lg:grid-cols-3"
  >
    <Card as="section" class="min-w-0 p-5">
      <h2 class="text-sm font-semibold text-muted-foreground">Live design</h2>
      <p class="my-2 text-xl font-semibold">{{ overview.layout }}</p>
      <p v-if="overview.liveTheme" class="mb-2 text-sm">
        {{ overview.liveTheme.name
        }}<span v-if="overview.liveTheme.version">
          · v{{ overview.liveTheme.version }}</span
        ><span v-else> · Saved appearance</span>
        <span
          v-if="
            overview.liveTheme.latestVersion > overview.liveTheme.version &&
            overview.liveTheme.version
          "
          class="text-muted-foreground"
        >
          · v{{ overview.liveTheme.latestVersion }} available</span
        >
      </p>
      <p
        v-if="overview.live && overview.live !== 'Original Classic'"
        class="break-words text-sm"
      >
        {{ overview.live }}
      </p>
      <Link
        v-if="overview.liveProof"
        :href="proofLink(overview.liveProof)"
        class="mt-2 block text-sm text-primary underline"
        >View live proof</Link
      >
      <a
        v-if="publicLink"
        :href="publicLink"
        target="_blank"
        rel="noopener noreferrer"
        class="mt-3 inline-flex items-center gap-1 break-all text-sm text-primary underline"
      >
        <span>Track {{ lookup.trackingNumber }}</span>
        <ArrowUpRight aria-hidden="true" class="size-4 shrink-0" />
      </a>
    </Card>
    <template v-if="overview.recentDrafts">
      <Card as="section" class="min-w-0 p-5">
        <h2 class="font-semibold">Recent drafts</h2>
        <ul class="my-3 divide-y divide-border">
          <li
            v-for="draft in overview.recentDrafts"
            :key="draft.uuid"
            class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 py-2"
          >
            <Link
              :href="route('admin.drafts.show', [company.uuid, draft.uuid])"
              class="break-words text-sm text-primary underline"
              >{{ draft.name }}</Link
            >
            <span class="font-mono text-xs text-muted-foreground"
              >r{{ draft.revisions_count }}</span
            >
          </li>
        </ul>
        <p
          v-if="!overview.recentDrafts.length"
          class="mb-3 text-sm text-muted-foreground"
        >
          No saved drafts.
        </p>
        <div class="flex flex-wrap items-center gap-3">
          <Button as-child>
            <Link :href="route('admin.drafts.create', params)"
              >Create design draft</Link
            >
          </Button>
          <Link
            :href="route('admin.drafts.index', params)"
            class="text-sm text-primary underline"
            >Design drafts</Link
          >
        </div>
      </Card>
      <Card as="section" class="min-w-0 p-5">
        <h2 class="font-semibold">Approved proofs</h2>
        <p
          v-if="!overview.approvedProofs.data.length"
          class="mt-3 text-sm text-muted-foreground"
        >
          No approved proofs.
        </p>
        <ul class="mt-1 divide-y divide-border">
          <li
            v-for="proof in overview.approvedProofs.data"
            :key="proof.uuid"
            class="py-2"
          >
            <Link
              :href="proofLink(proof)"
              class="block break-words text-sm text-primary underline"
              >{{ proof.name }} · Revision {{ proof.version }}</Link
            >
            <p class="break-all font-mono text-xs text-muted-foreground">
              {{ proof.reference }}<span v-if="proof.live"> · Live</span>
            </p>
            <time
              :datetime="proof.approvedAt"
              class="text-xs text-muted-foreground"
              >Approved {{ new Date(proof.approvedAt).toLocaleString() }}</time
            >
          </li>
        </ul>
        <nav
          v-if="
            overview.approvedProofs.prev_page_url ||
            overview.approvedProofs.next_page_url
          "
          aria-label="Approved proof pages"
          class="mt-3 flex justify-between text-sm text-primary underline"
        >
          <Link
            v-if="overview.approvedProofs.prev_page_url"
            :href="overview.approvedProofs.prev_page_url"
            preserve-scroll
            >Previous proofs</Link
          >
          <Link
            v-if="overview.approvedProofs.next_page_url"
            :href="overview.approvedProofs.next_page_url"
            preserve-scroll
            >More proofs</Link
          >
        </nav>
      </Card>
    </template>
  </section>
</template>
