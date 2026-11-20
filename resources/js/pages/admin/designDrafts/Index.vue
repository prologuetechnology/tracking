<script setup>
import { Head, Link } from '@inertiajs/vue3'

import PublicationHistory from '@/components/feature/designDraft/PublicationHistory.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

defineProps({
  company: { type: Object, required: true },
  publications: { type: Array, required: true },
  drafts: { type: Object, required: true },
})
</script>

<template>
  <Head :title="`Design drafts · ${company.name}`" />
  <AuthenticatedLayout title="Design drafts">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <Link
        :href="route('admin.companies.show', company.uuid)"
        class="text-sm underline"
      >
        {{ company.name }}
      </Link>
      <Button as-child>
        <Link :href="route('admin.drafts.create', company.uuid)">
          Create design draft
        </Link>
      </Button>
    </div>
    <PublicationHistory :company="company" :publications="publications" />
    <Card>
      <CardContent class="p-5">
        <p v-if="!drafts.data.length" class="text-muted-foreground">
          No saved drafts for {{ company.name }} yet.
        </p>
        <ul v-else class="divide-y">
          <li
            v-for="item in drafts.data"
            :key="item.uuid"
            class="flex flex-wrap items-baseline justify-between gap-2 py-3"
          >
            <Link
              class="break-words font-semibold underline"
              :href="route('admin.drafts.show', [company.uuid, item.uuid])"
            >
              {{ item.name }}
            </Link>
            <span class="text-sm text-muted-foreground">
              Revision {{ item.revisions_count }} ·
              {{ new Date(item.updated_at).toLocaleDateString() }}
            </span>
          </li>
        </ul>
      </CardContent>
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
  </AuthenticatedLayout>
</template>
