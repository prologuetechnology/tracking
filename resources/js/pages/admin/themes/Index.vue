<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import ThemeCreateButton from '@/components/feature/theme/ThemeCreateButton.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useThemesQuery } from '@/composables/queries/theme'

const { initialThemes } = usePage().props
const { data: themes, isError } = useThemesQuery({
  config: { initialData: initialThemes },
})
const query = ref(``)
const matches = computed(() =>
  (themes.value ?? []).filter((t) =>
    t.name.toLowerCase().includes(query.value.trim().toLowerCase()),
  ),
)
</script>
<template>
  <AuthenticatedLayout title="Theme library" :action="ThemeCreateButton">
    <Head title="Theme library" />
    <div class="space-y-5">
      <div class="grid max-w-md gap-1.5">
        <Label for="find-theme">Find a theme</Label>
        <Input
          id="find-theme"
          v-model="query"
          type="search"
          placeholder="Theme name"
        />
      </div>
      <p v-if="isError" role="alert">Could not load themes.</p>
      <p v-else-if="!matches.length" class="text-sm text-muted-foreground">
        No matching themes.
      </p>
      <ul
        v-else
        class="divide-y rounded-md border bg-card text-card-foreground"
      >
        <li v-for="t in matches" :key="t.uuid" :dusk="'theme-row-' + t.id">
          <Link
            :dusk="'theme-edit-' + t.id"
            :href="route('admin.themes.show', t.uuid)"
            class="flex items-center justify-between gap-4 p-4 hover:bg-muted focus-visible:outline-ring"
          >
            <span class="flex min-w-0 items-center gap-3"
              ><span
                class="flex shrink-0 overflow-hidden rounded-sm border"
                aria-hidden="true"
              >
                <i
                  v-for="key in ['primary', 'accent', 'background']"
                  :key="key"
                  class="h-6 w-3"
                  :style="{ background: 'hsl(' + t.colors.root[key] + ')' }"
                /> </span
              ><strong class="break-words">{{ t.name }}</strong></span
            >
            <span
              class="whitespace-nowrap font-mono text-xs text-muted-foreground"
              >v{{ t.version }}</span
            >
          </Link>
        </li>
      </ul>
    </div>
  </AuthenticatedLayout>
</template>
