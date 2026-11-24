<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import ThemeCreateButton from '@/components/feature/theme/ThemeCreateButton.vue'
import ThemeForm from '@/components/feature/theme/ThemeForm.vue'
import StudioNavigation from '@/components/layout/navigation/StudioNavigation.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  mode: { type: String, required: true },
  initialTheme: { type: Object, default: null },
  initialThemes: { type: Array, default: () => [] },
})
const title = computed(() =>
  props.mode === `create`
    ? `Create a style`
    : (props.initialTheme?.name ?? `Style library`),
)
const query = ref(``)
const matches = computed(() =>
  props.initialThemes.filter((t) =>
    t.name.toLowerCase().includes(query.value.trim().toLowerCase()),
  ),
)
</script>
<template>
  <AuthenticatedLayout
    :title="title"
    :action="mode === 'browse' ? ThemeCreateButton : null"
  >
    <Head :title="title" />
    <StudioNavigation library />
    <ThemeForm
      v-if="mode !== 'browse'"
      :key="initialTheme?.uuid ?? mode"
      :theme="initialTheme"
    />
    <div v-else class="space-y-5">
      <div class="grid max-w-md gap-1.5">
        <Label for="find-theme">Find a style</Label>
        <Input
          id="find-theme"
          v-model="query"
          type="search"
          placeholder="Style name"
        />
      </div>
      <p v-if="!matches.length" class="text-sm text-muted-foreground">
        No matching styles.
      </p>
      <Card v-else as="ul" class="divide-y overflow-hidden">
        <li v-for="t in matches" :key="t.uuid" :dusk="'theme-row-' + t.id">
          <Link
            :dusk="'theme-edit-' + t.id"
            :href="route('admin.studio.styles.show', t.uuid)"
            class="flex items-center justify-between gap-4 p-4 hover:bg-muted focus-visible:outline-ring"
          >
            <span class="flex min-w-0 items-center gap-3"
              ><span
                class="flex shrink-0 overflow-hidden rounded-sm border"
                aria-hidden="true"
              >
                <i
                  v-for="key in ['primary', 'accent', 'background'].filter(
                    (k) => t.colors?.root?.[k],
                  )"
                  :key="key"
                  class="h-6 w-3"
                  :style="{ background: 'hsl(' + t.colors.root[key] + ')' }"
                /> </span
              ><strong class="break-words">{{ t.name }}</strong></span
            >
            <Badge variant="outline" class="font-mono">v{{ t.version }}</Badge>
          </Link>
        </li>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
