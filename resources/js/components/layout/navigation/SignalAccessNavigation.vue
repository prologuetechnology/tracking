<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'

import { Button } from '@/components/ui/button'

import { accessAreas } from './accessAreas'
const page = usePage()
</script>
<template>
  <div>
    <nav aria-label="Breadcrumb" class="mb-3 flex items-center gap-2 text-sm">
      <Link
        :href="route('admin.settings.index')"
        class="text-primary hover:underline"
      >
        Application Settings
      </Link>
      <ChevronRight class="size-3.5" aria-hidden="true" />
      <span> Access </span>
    </nav>
    <nav aria-label="Access administration" class="signal-access-nav">
      <Button
        v-for="{ name, area, route: link } in accessAreas"
        :key="area"
        size="sm"
        variant="ghost"
        as-child
      >
        <Link
          :href="route(link)"
          :aria-current="
            page.component.startsWith('admin/' + area + '/')
              ? 'page'
              : undefined
          "
        >
          {{ name }}
        </Link>
      </Button>
    </nav>
  </div>
</template>
