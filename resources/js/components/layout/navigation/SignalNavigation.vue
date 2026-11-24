<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
  Building2,
  Images,
  PanelsTopLeft,
  ScanLine,
  ShieldCheck,
  SwatchBook,
} from 'lucide-vue-next'
import { computed } from 'vue'

import { useRolesAndPermissions } from '@/composables/hooks/auth'

const props = defineProps({ company: { type: Object, default: null } })
const emit = defineEmits([`navigate`])
const page = usePage()
const { userCan, userIs } = useRolesAndPermissions()
const items = computed(() => {
  const component = page.component
  const company = props.company || page.props.companyInitialValues
  const picker =
    new URL(page.url, location.origin).searchParams.get(`workspace`) ===
    `studio`
  return [
    {
      name: `Shipment lookup`,
      icon: ScanLine,
      href: route(`admin.tracking.index`),
      active: component.startsWith(`admin/tracking/`),
      show: true,
    },
    {
      name: `Companies`,
      icon: Building2,
      href: route(`admin.companies.index`),
      active: component.startsWith(`admin/companies/`) && !picker,
      show: userCan(`company:show`),
    },
    {
      name: `Design studio`,
      icon: PanelsTopLeft,
      href: company?.uuid
        ? route(`admin.drafts.index`, company.uuid)
        : route(`admin.companies.index`, { workspace: `studio` }),
      active: component.startsWith(`admin/designDrafts/`) || picker,
      show: userCan(`company:show`) && userCan(`company:update`),
    },
    {
      name: `Themes`,
      icon: SwatchBook,
      href: route(`admin.themes.index`),
      active: component.startsWith(`admin/themes/`),
      show: userCan(`theme:show`),
    },
    {
      name: `Shared assets`,
      icon: Images,
      href: route(`admin.image.index`),
      active: component.startsWith(`admin/image/`),
      show: userCan(`image:show`),
    },
    {
      name: `Access`,
      icon: ShieldCheck,
      href: route(`admin.users.index`),
      active: /^admin\/(users|role|permissions|allowedDomains)\//.test(
        component,
      ),
      show: userIs(`Super Admin`),
    },
  ].filter((i) => i.show)
})
</script>
<template>
  <nav class="signal-nav" aria-label="Staff workspace">
    <Link
      v-for="item in items"
      :key="item.name"
      :href="item.href"
      :aria-current="item.active ? 'location' : undefined"
      @click="emit('navigate')"
      ><component :is="item.icon" :size="16" aria-hidden="true" />{{
        item.name
      }}</Link
    >
  </nav>
</template>
