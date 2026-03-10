<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'

import ThemeCreateButton from '@/components/feature/theme/ThemeCreateButton.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useThemesQuery } from '@/composables/queries/theme'

const { userCan } = useRolesAndPermissions()
const { initialThemes } = usePage().props

const { data: themes, isError } = useThemesQuery({
  config: {
    initialData: initialThemes,
  },
})
</script>

<template>
  <Head title="Manage Themes" />

  <AuthenticatedLayout title="Manage Themes" :action="ThemeCreateButton">
    <div v-if="themes && !isError">
      <div class="flex flex-col justify-stretch space-y-4">
        <div
          v-for="theme in themes"
          :key="theme.uuid"
          :dusk="`theme-row-${theme.id}`"
          class="flex w-full flex-row items-center justify-start space-x-8 rounded-lg border p-4"
        >
          <div class="flex flex-col items-stretch justify-start space-y-2">
            <div class="flex flex-row items-center justify-center -space-x-2">
              <div
                class="z-10 aspect-square h-8 w-8 rounded-full outline outline-4 outline-background"
                :style="{
                  backgroundColor: `hsl(${theme.colors.root.primary})`,
                }"
              />

              <div
                class="-z-0 aspect-square h-8 w-8 rounded-full"
                :style="{
                  backgroundColor: `hsl(${theme.colors.root.accent})`,
                }"
              />
            </div>
          </div>

          <div class="w-full">
            <Label class="text-base">{{ theme.name }}</Label>
          </div>

          <div
            class="ml-auto flex w-full flex-row items-center justify-end space-x-4"
          >
            <Button as-child variant="outline" size="sm">
              <Link
                v-if="userCan(`theme:update`)"
                :dusk="`theme-edit-${theme.id}`"
                :href="route(`admin.themes.show`, theme.uuid)"
                >Edit</Link
              >
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
