<script setup>
import { faPlus } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link } from '@inertiajs/vue3'

import { Button } from '@/components/ui/button'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

const { userCan } = useRolesAndPermissions()
</script>

<template>
  <Button v-if="userCan('company:store')" size="sm" variant="default" as-child>
    <Link :href="route('admin.companies.create')">
      <FontAwesomeIcon :icon="faPlus" class="mr-2" fixed-width />

      <span>Create Company</span></Link
    >
  </Button>
</template>
