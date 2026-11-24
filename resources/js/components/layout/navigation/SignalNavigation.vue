<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { Building2, Images, ScanLine, Settings } from 'lucide-vue-next'
import { computed } from 'vue'

import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
} from '@/components/ui/navigation-menu'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

defineProps({
  mobile: Boolean,
})
const emit = defineEmits([`navigate`])
const page = usePage()
const { userCan, userIs } = useRolesAndPermissions()
const items = computed(() => {
  const component = page.component
  const styles = component === `admin/designDrafts/StyleLibrary`
  const canSeeImages = userCan(`image:show`)
  const libraryRoute = userCan(`theme:show`)
    ? `admin.studio.styles.index`
    : `admin.studio.styles.create`
  return [
    {
      name: `Shipment Lookup`,
      icon: ScanLine,
      href: route(`admin.tracking.index`),
      active: component.startsWith(`admin/tracking/`),
      show: true,
    },
    {
      name: `Companies`,
      icon: Building2,
      href: route(`admin.companies.index`),
      active:
        component.startsWith(`admin/companies/`) ||
        (component.startsWith(`admin/designDrafts/`) && !styles),
      show: userCan(`company:show`),
    },
    {
      name: `Shared Assets`,
      icon: Images,
      href: route(canSeeImages ? `admin.image.index` : libraryRoute),
      active: component.startsWith(`admin/image/`) || styles,
      show: canSeeImages || userCan(`theme:show`) || userCan(`theme:store`),
    },
    {
      name: `Application Settings`,
      icon: Settings,
      href: route(`admin.settings.index`),
      active: /^admin\/(settings|users|role|permissions|allowedDomains)\//.test(
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
        <NavigationMenuLink as-child>
          <Link
            :href="item.href"
            :aria-current="item.active ? 'location' : undefined"
            @success="emit('navigate')"
          >
            <component :is="item.icon" :size="16" aria-hidden="true" />
            {{ item.name }}
          </Link>
        </NavigationMenuLink>
      </NavigationMenuItem>
    </NavigationMenuList>
  </NavigationMenu>
</template>
