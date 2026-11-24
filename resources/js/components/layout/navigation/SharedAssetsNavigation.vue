<script setup>
import { Link } from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

defineProps({ library: Boolean })
const { userCan } = useRolesAndPermissions()
</script>

<template>
  <nav aria-label="Shared Assets" class="mb-5 flex flex-wrap gap-1 pb-2">
    <Button
      v-if="userCan('image:show')"
      as-child
      size="sm"
      :variant="library ? 'ghost' : 'secondary'"
    >
      <Link
        :href="route('admin.image.index')"
        :aria-current="library ? undefined : 'location'"
      >
        Images
      </Link>
    </Button>
    <Button
      v-if="userCan('theme:show') || userCan('theme:store')"
      as-child
      size="sm"
      :variant="library ? 'secondary' : 'ghost'"
    >
      <Link
        :href="
          route(
            userCan('theme:show')
              ? 'admin.studio.styles.index'
              : 'admin.studio.styles.create',
          )
        "
        :aria-current="library ? 'location' : undefined"
      >
        Style Library
      </Link>
    </Button>
  </nav>
</template>
