<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
  Building2,
  Images,
  PanelsTopLeft,
  ScanLine,
  ShieldCheck,
} from 'lucide-vue-next'
import { computed } from 'vue'

import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
} from '@/components/ui/navigation-menu'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

const props = defineProps({
  mobile: Boolean,
  company: { type: Object, default: null },
})
const emit = defineEmits([`navigate`])
const page = usePage()
const { userCan, userIs } = useRolesAndPermissions()
const items = computed(() => {
  const component = page.component
  const company = props.company || page.props.companyInitialValues
  const canEditDrafts = userCan(`company:show`) && userCan(`company:update`)
  const libraryRoute = userCan(`theme:show`)
    ? `admin.studio.styles.index`
    : `admin.studio.styles.create`
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
      href: canEditDrafts
        ? company?.uuid
          ? route(`admin.drafts.index`, company.uuid)
          : route(`admin.companies.index`, { workspace: `studio` })
        : route(libraryRoute),
      active: component.startsWith(`admin/designDrafts/`) || picker,
      show: canEditDrafts || userCan(`theme:show`) || userCan(`theme:store`),
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
  <NavigationMenu
    :orientation="mobile ? 'vertical' : 'horizontal'"
    aria-label="Staff workspace"
    class="signal-nav"
    :class="{ 'w-full max-w-none': mobile }"
  >
    <NavigationMenuList
      :class="mobile ? 'w-full flex-col items-stretch gap-1' : 'gap-1'"
    >
      <NavigationMenuItem
        v-for="item in items"
        :key="item.name"
        :class="{ 'w-full': mobile }"
      >
        <NavigationMenuLink as-child
          ><Link
            :href="item.href"
            :aria-current="item.active ? 'location' : undefined"
            @success="emit('navigate')"
            ><component :is="item.icon" :size="16" aria-hidden="true" />{{
              item.name
            }}</Link
          ></NavigationMenuLink
        >
      </NavigationMenuItem></NavigationMenuList
    ></NavigationMenu
  >
</template>
