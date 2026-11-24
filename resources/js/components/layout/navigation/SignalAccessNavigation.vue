<script setup>
import { Link, usePage } from '@inertiajs/vue3'
const page = usePage()
const areas = [
  [`Users`, `users`, `admin.users.index`],
  [`Roles`, `role`, `admin.role.index`],
  [`Permissions`, `permissions`, `admin.permissions.index`],
  [`Allowed domains`, `allowedDomains`, `admin.allowed-domains.index`],
]
</script>
<template>
  <nav aria-label="Access administration" class="signal-access-nav">
    <Link
      v-for="[name, area, link] in areas"
      :key="area"
      :href="route(link)"
      :aria-current="
        page.component.startsWith('admin/' + area + '/') ? 'page' : undefined
      "
      >{{ name }}</Link
    >
  </nav>
</template>
