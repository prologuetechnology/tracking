<script setup>
import '@/components/feature/portalReview/directions/directions.css'

import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  ArrowLeft,
  ArrowUpRight,
  Building2,
  Images,
  Menu,
  PanelsTopLeft,
  ScanLine,
  ShieldCheck,
  SwatchBook,
} from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import DirectionShipment from '@/components/feature/portalReview/directions/DirectionShipment.vue'
import DirectionWorkspace from '@/components/feature/portalReview/directions/DirectionWorkspace.vue'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

defineProps({ shipment: { type: Object, required: true } })
const concepts = [
  { key: `manifest`, name: `Manifest` },
  { key: `fieldbook`, name: `Fieldbook` },
  { key: `signal`, name: `Signal` },
]
const { userCan, userIs } = useRolesAndPermissions()
const sections = [
  { key: `lookup`, name: `Shipment lookup`, icon: ScanLine, visible: true },
  {
    key: `companies`,
    name: `Companies`,
    icon: Building2,
    visible: userCan(`company:show`),
  },
  {
    key: `studio`,
    name: `Design studio`,
    icon: PanelsTopLeft,
    visible: userCan(`company:show`) && userCan(`company:update`),
  },
  {
    key: `themes`,
    name: `Themes`,
    icon: SwatchBook,
    visible: userCan(`theme:show`),
  },
  {
    key: `assets`,
    name: `Shared assets`,
    icon: Images,
    visible: userCan(`image:show`),
  },
  {
    key: `access`,
    name: `Access`,
    icon: ShieldCheck,
    visible: userIs(`Super Admin`),
  },
].filter((s) => s.visible)
const selected = ref(concepts[0])
const active = ref(sections[0])
const menu = ref(false)
const menuButton = ref(null)
const main = ref(null)
const user = usePage().props.auth.user
const displayName =
  [user.first_name, user.last_name].filter(Boolean).join(` `) || `Staff member`
const initials = displayName
  .split(/\s+/)
  .slice(0, 2)
  .map((s) => s[0])
  .join(``)
const activeName = computed(() =>
  active.value.key === `lookup`
    ? {
        manifest: `Shipment desk`,
        fieldbook: `Shipment file`,
        signal: `Shipment console`,
      }[selected.value.key]
    : active.value.name,
)
const titles = {
  manifest: `Operations`,
  fieldbook: `Company office`,
  signal: `Control room`,
}
function href(concept, section) {
  return `#` + concept + `/` + section
}
function sync() {
  const [concept, section] = location.hash.slice(1).split(`/`)
  selected.value = concepts.find((c) => c.key === concept) || concepts[0]
  active.value = sections.find((s) => s.key === section) || sections[0]
  menu.value = false
}
function navigate(section) {
  location.hash = href(selected.value.key, section)
}
function onNavigate() {
  sync()
  main.value?.focus({ preventScroll: true })
  window.scrollTo(0, 0)
}
function closeMenu() {
  menu.value = false
  menuButton.value?.focus()
}
sync()
onMounted(() => window.addEventListener(`hashchange`, onNavigate))
onBeforeUnmount(() => window.removeEventListener(`hashchange`, onNavigate))
</script>

<template>
  <div class="directions-page" :class="'is-' + selected.key">
    <Head :title="selected.name + ' · ' + activeName" />
    <div class="review-strip">
      <Link
        :href="route('admin.portal-review.index')"
        class="return-review"
        aria-label="Return to the staff workbench"
        ><ArrowLeft :size="13" /> Workbench</Link
      >
      <nav aria-label="Design directions">
        <a
          v-for="c in concepts"
          :key="c.key"
          :href="href(c.key, active.key)"
          :aria-current="selected.key === c.key ? 'true' : undefined"
          >{{ c.name }}</a
        >
      </nav>
      <span class="sample-label">Sample workspace</span>
    </div>
    <a
      href="#direction-content"
      class="sr-only focus:not-sr-only"
      @click.prevent="main?.focus()"
      >Skip to content</a
    >
    <div class="direction-shell">
      <header class="direction-brand">
        <div class="prologue-mark" aria-hidden="true"><i /><i /><i /></div>
        <div class="identity">
          <strong>Prologue</strong><span>{{ titles[selected.key] }}</span>
        </div>
        <button
          ref="menuButton"
          class="mobile-menu"
          type="button"
          aria-controls="direction-nav"
          :aria-expanded="menu"
          @click="menu = !menu"
        >
          <Menu :size="17" /> Sections
        </button>
      </header>
      <nav
        id="direction-nav"
        class="direction-nav"
        :class="{ expanded: menu }"
        aria-label="Portal navigation"
        @keydown.esc="closeMenu"
      >
        <a
          v-for="item in sections"
          :key="item.key"
          :href="href(selected.key, item.key)"
          :aria-current="active.key === item.key ? 'page' : undefined"
          @click="menu = false"
        >
          <component :is="item.icon" :size="18" :stroke-width="1.6" /><span>{{
            item.name
          }}</span>
        </a>
      </nav>
      <div class="direction-account">
        <span>{{ initials }}</span>
        <div>{{ displayName }}<small>Staff workspace</small></div>
      </div>
      <main
        id="direction-content"
        ref="main"
        class="direction-main"
        tabindex="-1"
      >
        <div class="dir-heading">
          <div>
            <p class="dir-label">
              {{
                selected.key === 'fieldbook'
                  ? 'Prologue Technology'
                  : titles[selected.key]
              }}
              <span>/</span> Staff portal
            </p>
            <h1>{{ activeName }}</h1>
          </div>
          <div class="dir-context">
            <span class="context-line" /><span>{{
              active.key === 'access'
                ? 'Staff administration'
                : active.key === 'themes'
                  ? 'Shared library'
                  : 'Jayco, Inc.'
            }}</span>
          </div>
        </div>
        <DirectionShipment
          v-if="active.key === 'lookup'"
          :shipment="shipment"
          :direction="selected.key"
          :can-company="sections.some((s) => s.key === 'companies')"
          @company="navigate('companies')"
        />
        <DirectionWorkspace
          v-else
          :key="active.key"
          :section="active.key"
          :sections="sections"
          :shipment="shipment"
          @navigate="navigate"
        />
      </main>
    </div>
    <footer class="direction-footer">
      <span>Prologue Technology</span
      ><span>{{ selected.name }} <i>/</i> Staff portal</span
      ><Link :href="route('admin.portal-review.index')"
        >Return to workbench <ArrowUpRight :size="13"
      /></Link>
    </footer>
  </div>
</template>
