<script setup>
import { faSignOut } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

import Button from '@/components/ui/button/Button.vue'

const logout = () => {
  axios.post(route(`oauth.logout`)).then(() => router.visit(route(`home`)))
}

defineProps({
  size: {
    type: String,
    default: `default`,
  },
})
</script>

<template>
  <!-- prettier-ignore -->
  <Button
    variant="outline"
    :size="size"
    as="button"
    @click="logout"
  >
    <FontAwesomeIcon class="mr-2" :icon="faSignOut" fixed-width />
    Sign Out
  </Button>
</template>
