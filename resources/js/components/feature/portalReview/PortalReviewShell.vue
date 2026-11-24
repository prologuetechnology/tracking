<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
  Building2,
  Images,
  Layers3,
  Menu,
  Palette,
  ScanLine,
  ShieldCheck,
  X,
} from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import StaffFooter from '@/components/layout/footer/StaffFooter.vue'
import { Button } from '@/components/ui/button'
const props = defineProps({
  section: { type: String, required: true },
  permissions: { type: Object, required: true },
})
const emit = defineEmits([`navigate`])
const open = ref(false),
  menu = ref(null)
watch(
  () => props.section,
  () => {
    open.value = false
  },
)
const items = computed(() =>
  [
    { id: `lookup`, title: `Shipment lookup`, icon: ScanLine, show: true },
    {
      id: `companies`,
      title: `Companies`,
      icon: Building2,
      show: props.permissions.company,
    },
    {
      id: `themes`,
      title: `Themes`,
      icon: Palette,
      show: props.permissions.themes,
    },
    {
      id: `assets`,
      title: `Shared assets`,
      icon: Images,
      show: props.permissions.assets,
    },
    {
      id: `access`,
      title: `Access`,
      icon: ShieldCheck,
      show: props.permissions.access,
    },
  ].filter((i) => i.show),
)
const owner = usePage().props.auth.user
const displayName =
  [owner.first_name, owner.last_name].filter(Boolean).join(` `) ||
  owner.name ||
  `Staff member`
function closeMenu() {
  open.value = false
  menu.value?.$el?.focus()
}
function navigate(id) {
  emit(`navigate`, id)
  if (open.value) {
    open.value = false
    menu.value?.$el?.focus()
  }
}
</script>
<template>
  <div class="review-shell">
    <aside class="work-rail">
      <div class="wordmark">
        <span><Layers3 :size="21" /></span>
        Prologue
      </div>
      <Button
        ref="menu"
        class="nav-toggle"
        variant="ghost"
        size="icon"
        :aria-expanded="open"
        aria-controls="work-navigation"
        :aria-label="open ? 'Close navigation' : 'Open navigation'"
        @click="open = !open"
      >
        <component :is="open ? X : Menu" />
      </Button>
      <nav
        id="work-navigation"
        aria-label="Staff workspace"
        :class="{ opened: open }"
        @keydown.esc="closeMenu"
      >
        <p>Workspace</p>
        <template v-for="item in items" :key="item.id">
          <p v-if="item.id === 'themes'">Design library</p>
          <button
            :aria-current="section === item.id ? 'page' : undefined"
            @click="navigate(item.id)"
          >
            <component :is="item.icon" :size="17" />
            {{ item.title }}
          </button>
        </template>
      </nav>
      <div class="staff">
        <span>{{ displayName.slice(0, 1) }}</span>
        <div>
          <strong>{{ displayName }}</strong>
          <small>Staff workspace</small>
        </div>
      </div>
    </aside>
    <div class="work-main">
      <header class="mast">
        <span>
          Tracking
          <i>/</i>
          {{
            items.find((i) => i.id === section)?.title || 'Company workspace'
          }}
        </span>
        <Link
          class="direction-entry"
          :href="route('admin.portal-review.directions')"
          >Compare portal designs ↗</Link
        >
        <span class="local-mark">
          <i />
          Local design review
        </span>
      </header>
      <main id="review-main" tabindex="-1"><slot /></main>
    </div>
    <StaffFooter class="work-footer" />
  </div>
</template>
<style scoped>
.review-shell {
  --line: #d9ddd4;
  --sub: #59645d;
  --ink: #203129;
  --background: 60 20% 99%;
  --foreground: 145 20% 16%;
  --primary: 152 27% 21%;
  --primary-foreground: 60 20% 98%;
  --border: 96 10% 83%;
  --input: 96 10% 80%;
  --muted: 64 12% 92%;
  --muted-foreground: 143 6% 36%;
  --radius: 0.55rem;
  background: #f6f7f2;
  color: var(--ink);
  display: grid;
  grid-template-columns: 206px minmax(0, 1fr);
  grid-template-rows: 1fr auto;
  min-height: 100dvh;
  font-family: Figtree, system-ui, sans-serif;
}
.work-rail {
  background: #202d29;
  color: #eef2ea;
  padding: 27px 16px;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  max-height: 100dvh;
}
.work-footer {
  grid-column: 1/-1;
}
.wordmark {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 740;
  font-size: 23px;
  letter-spacing: -1px;
  margin: 0 7px 45px;
}
.wordmark span {
  display: grid;
  place-items: center;
  width: 33px;
  height: 33px;
  background: #edf4ab;
  color: #273329;
  border-radius: 9px;
}
.work-rail nav p {
  color: #a8b5a9;
  font-weight: 650;
  font-size: 11px;
  letter-spacing: 0.04em;
  margin: 22px 10px 7px;
}
.work-rail nav button {
  display: flex;
  align-items: center;
  gap: 12px;
  border-radius: 7px;
  padding: 11px;
  width: 100%;
  text-align: left;
  font-size: 14px;
}
.work-rail nav button:hover {
  background: #31423b;
}
.work-rail nav button[aria-current] {
  color: #f3f8cc;
  background: #3c5046;
}
.staff {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 9px;
  border-top: 1px solid #405047;
  padding: 17px 3px 0;
}
.staff > span {
  width: 33px;
  height: 33px;
  display: grid;
  place-items: center;
  background: #445b4e;
  border-radius: 50%;
}
.staff strong,
.staff small {
  display: block;
  font-size: 12px;
}
.staff small {
  color: #b4c1b5;
}
.work-main {
  min-width: 0;
}
.mast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--line);
  padding: 16px clamp(18px, 3vw, 52px);
  font-size: 13px;
  font-weight: 600;
  background: #fcfcf8;
}
.mast i {
  font-style: normal;
  color: #87928a;
  margin: 0 8px;
}
.direction-entry {
  margin-left: auto;
  color: #344a37;
  text-decoration: underline;
  text-underline-offset: 3px;
}
.mast {
  gap: 12px;
  flex-wrap: wrap;
}
@media (max-width: 600px) {
  .mast .local-mark {
    display: none;
  }
}
.local-mark {
  display: flex;
  align-items: center;
  gap: 7px;
}
.local-mark i {
  background: #6e845e;
  border-radius: 100%;
  width: 7px;
  height: 7px;
  margin: 0;
}
.nav-toggle {
  display: none;
}
main {
  max-width: 1600px;
  padding: clamp(20px, 3.4vw, 54px);
  margin: auto;
}
.review-shell :deep(:focus-visible) {
  outline: 2px solid #668c40;
  outline-offset: 3px;
}
@media (max-width: 820px) {
  .review-shell {
    grid-template-columns: minmax(0, 1fr);
    grid-template-rows: auto 1fr auto;
  }
  .work-rail {
    height: auto;
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto;
    padding: 13px 18px;
  }
  .wordmark {
    margin: 0;
    font-size: 20px;
  }
  .nav-toggle {
    display: flex;
  }
  .work-rail nav,
  .staff {
    display: none;
  }
  .work-rail nav.opened {
    display: block;
    grid-column: 1/-1;
  }
  .mast {
    padding: 11px 18px;
    font-size: 12px;
  }
  main {
    padding: 22px 16px;
  }
}
</style>
