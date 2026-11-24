<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({
  company: { type: Object, required: true },
  revisions: { type: Object, required: true },
})
</script>
<template>
  <section
    v-if="revisions.data.length || revisions.prev_page_url"
    aria-labelledby="approved-revisions"
    class="mt-8"
  >
    <h2 id="approved-revisions" class="mb-3 text-lg font-semibold">
      Approved revisions
    </h2>
    <ul class="divide-y">
      <li
        v-for="revision in revisions.data"
        :key="revision.draftUuid + '-' + revision.version"
        class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"
      >
        <Link
          :href="
            route('admin.drafts.preview', [
              company.uuid,
              revision.draftUuid,
              revision.version,
            ])
          "
          class="font-medium text-primary underline"
        >
          {{ revision.name }} · Revision {{ revision.version }}
        </Link>
        <time :datetime="revision.approvedAt" class="text-muted-foreground">
          Approved {{ new Date(revision.approvedAt).toLocaleDateString() }}
        </time>
      </li>
    </ul>
    <nav
      v-if="revisions.prev_page_url || revisions.next_page_url"
      aria-label="Approved revision pages"
      class="mt-3 flex justify-between text-sm underline"
    >
      <Link
        v-if="revisions.prev_page_url"
        :href="revisions.prev_page_url"
        preserve-scroll
        >Previous revisions</Link
      >
      <Link
        v-if="revisions.next_page_url"
        :href="revisions.next_page_url"
        preserve-scroll
        >More revisions</Link
      >
    </nav>
  </section>
</template>
