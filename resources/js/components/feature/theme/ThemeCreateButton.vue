<script setup>
import { faPlus } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link } from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

const { userCan } = useRolesAndPermissions()
</script>

<template>
  <Button v-if="userCan(`theme:store`)" variant="default" as-child size="sm">
    <Link :href="route('admin.themes.create')">
      <FontAwesomeIcon :icon="faPlus" class="mr-2" fixed-width />

      <span>Create Theme</span></Link
    >
  </Button>
</template>
