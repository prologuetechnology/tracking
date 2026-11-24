<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'

import SignalShell from './SignalShell.vue'
defineProps({ company: { type: Object, required: true } })
</script>
<template>
  <SignalShell content-id="studio-workspace">
    <nav
      aria-label="Breadcrumb"
      class="mb-4 flex items-center gap-2 text-sm text-muted-foreground"
    >
      <Link
        :href="route('admin.companies.index')"
        class="text-muted-foreground hover:text-foreground hover:underline"
      >
        Companies
      </Link>
      <ChevronRight :size="14" />
      <Link
        :href="route('admin.companies.show', company.uuid)"
        class="font-medium text-foreground hover:underline"
      >
        {{ company.name }}
      </Link>
      <ChevronRight :size="14" aria-hidden="true" />
      <Link
        :href="route('admin.drafts.index', company.uuid)"
        class="hover:underline"
      >
        Design Studio
      </Link>
    </nav>
    <slot />
  </SignalShell>
</template>
