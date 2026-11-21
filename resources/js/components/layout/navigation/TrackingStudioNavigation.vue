<script setup>
import { Link } from '@inertiajs/vue3'
import {
  Building2,
  ChevronDown,
  Image,
  Layers3,
  LogOut,
  Palette,
  PanelsTopLeft,
  Search,
  Users,
} from 'lucide-vue-next'
import { computed } from 'vue'

import { useLogout, useRolesAndPermissions } from '@/composables/hooks/auth'

const props = defineProps({ company: { type: Object, required: true } })
const { userCan, userIs } = useRolesAndPermissions()
const { logout } = useLogout()
const navigation = computed(() =>
  [
    {
      name: `Companies`,
      icon: Building2,
      href: route(`admin.companies.index`),
      visible: userCan(`company:show`),
    },
    {
      name: `Tracking studio`,
      icon: PanelsTopLeft,
      href: route(`admin.drafts.index`, props.company.uuid),
      visible: userCan(`company:show`) && userCan(`company:update`),
      active: true,
    },
    {
      name: `Shipment lookup`,
      icon: Search,
      href: route(`admin.tracking.index`),
      visible: true,
    },
    {
      name: `Themes`,
      icon: Palette,
      href: route(`admin.themes.index`),
      visible: userCan(`theme:show`),
    },
    {
      name: `Shared assets`,
      icon: Image,
      href: route(`admin.image.index`),
      visible: userCan(`image:show`),
    },
  ].filter((link) => link.visible),
)
const access = [
  [`Users`, `admin.users.index`],
  [`Roles`, `admin.role.index`],
  [`Permissions`, `admin.permissions.index`],
  [`Allowed domains`, `admin.allowed-domains.index`],
]
</script>

<template>
  <div
    class="flex min-h-0 flex-1 flex-col bg-[#25252e] px-3 py-5 text-[#e9e8f0]"
  >
    <Link
      :href="route('admin.dashboard')"
      class="mb-8 flex items-center gap-2.5 rounded-lg px-2 font-sans text-xl font-bold tracking-tight focus-visible:outline-white"
    >
      <div
        class="grid size-8 shrink-0 place-items-center rounded-lg bg-[#f3cd82] text-[#302a24]"
      >
        <Layers3 aria-hidden="true" class="size-5" />
      </div>
      Prologue
    </Link>
    <nav aria-label="Staff workspace">
      <p
        class="mb-2 px-2 font-mono text-[0.65rem] uppercase tracking-widest text-[#c5c2cf]"
      >
        Operations
      </p>
      <Link
        v-for="item in navigation"
        :key="item.name"
        :href="item.href"
        :aria-current="item.active ? 'location' : undefined"
        class="flex min-h-10 items-center gap-2.5 rounded-md px-2.5 text-sm outline-offset-2 hover:bg-[#383841] focus-visible:outline-white"
        :class="
          item.active &&
          'bg-[#45424f] font-semibold text-white shadow-[inset_3px_0_#f3cd82]'
        "
      >
        <component
          :is="item.icon"
          aria-hidden="true"
          class="size-4 shrink-0"
        />{{ item.name }}
      </Link>
      <details
        v-if="userIs('Super Admin')"
        class="group mt-2 border-t border-[#45434e] pt-2"
      >
        <summary
          class="flex min-h-10 cursor-pointer list-none items-center gap-2.5 rounded-md px-2.5 text-sm hover:bg-[#383841] focus-visible:outline-white [&::-webkit-details-marker]:hidden"
        >
          <Users aria-hidden="true" class="size-4" />Access<ChevronDown
            aria-hidden="true"
            class="ml-auto size-3.5 group-open:rotate-180"
          />
        </summary>
        <Link
          v-for="[name, path] in access"
          :key="path"
          :href="route(path)"
          class="flex min-h-9 items-center rounded-md pl-9 pr-2 text-sm hover:bg-[#383841] focus-visible:outline-white"
          >{{ name }}</Link
        >
      </details>
    </nav>
    <button
      type="button"
      class="mt-auto flex min-h-10 items-center gap-2.5 rounded-md px-2.5 text-left text-sm text-[#d5d2df] hover:bg-[#383841] focus-visible:outline-white"
      @click="logout"
    >
      <LogOut class="size-4" aria-hidden="true" />Sign out
    </button>
  </div>
</template>
