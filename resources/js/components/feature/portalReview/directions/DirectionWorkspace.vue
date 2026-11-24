<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
  ArrowRight,
  ArrowUpRight,
  Check,
  FileImage,
  Layers,
  ShieldCheck,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { assets, assetUrl, styles } from '../reviewStyles'
import ShipmentCanvas from '../ShipmentCanvas.vue'
const props = defineProps({
  section: { type: String, required: true },
  sections: { type: Array, required: true },
  shipment: { type: Object, required: true },
})
defineEmits([`navigate`])
const allowed = (key) => props.sections.some((s) => s.key === key)
const theme = ref(styles[0]),
  asset = ref(assets[0]),
  filter = ref(`All`)
const filtered = computed(() =>
  assets.filter((a) => filter.value === `All` || a.type === filter.value),
)
const directories = [
  {
    name: `Users`,
    detail: `Staff accounts and assigned roles`,
    route: `admin.users.index`,
  },
  {
    name: `Roles`,
    detail: `Permissions grouped by responsibility`,
    route: `admin.role.index`,
  },
  {
    name: `Permissions`,
    detail: `Individual staff capabilities`,
    route: `admin.permissions.index`,
  },
  {
    name: `Allowed domains`,
    detail: `Company domains allowed to sign in`,
    route: `admin.allowed-domains.index`,
  },
]
const directory = ref(directories[0])
const user = usePage().props.auth.user
function changeFilter(value) {
  filter.value = value
  if (!filtered.value.includes(asset.value)) asset.value = filtered.value[0]
}
</script>
<template>
  <div class="workspace-body">
    <template v-if="section === 'companies'">
      <div class="company-file">
        <div class="company-photo">
          <img
            :src="assetUrl('jayco-overlook')"
            alt="Jayco motorhome at the canyon overlook"
          />
        </div>
        <div class="company-profile">
          <span class="dir-label">Company record</span>
          <h2>Jayco, Inc.</h2>
          <p>Middlebury, Indiana</p>
          <img :src="assetUrl('jayco-logo')" alt="Jayco wordmark" />
        </div>
      </div>
      <div class="workspace-columns">
        <section>
          <div class="section-heading"><h2>Tracking designs</h2></div>
          <a
            class="file-row"
            :href="route('admin.portal-review.classic')"
            target="_blank"
            rel="noopener"
            ><Layers /><span
              ><strong>Classic</strong
              ><small>Current customer layout</small></span
            ><ArrowUpRight
          /></a>
          <button
            v-if="allowed('studio')"
            class="file-row"
            @click="$emit('navigate', 'studio')"
          >
            <FileImage /><span
              ><strong>Follow the journey</strong
              ><small>Staff proposal · Journey</small></span
            ><ArrowRight />
          </button>
        </section>
        <aside class="detail-card">
          <span class="dir-label">Sample shipment</span>
          <h3>{{ shipment.carrierPro }}</h3>
          <p>
            {{ shipment.originLocation.city }} to
            {{ shipment.destinationLocation.city }}
          </p>
          <button class="dir-link" @click="$emit('navigate', 'lookup')">
            Open shipment <ArrowRight :size="16" />
          </button>
        </aside>
      </div>
    </template>
    <template v-else-if="section === 'studio' || section === 'themes'">
      <div class="workspace-columns design-spread">
        <section class="style-index">
          <p class="dir-label">
            {{ section === 'studio' ? 'Jayco proposal' : 'Visual styles' }}
          </p>
          <h2>
            {{ section === 'studio' ? 'Follow the journey' : 'Theme library' }}
          </h2>
          <p class="body-note">
            {{
              section === 'studio'
                ? 'Compare styles on the Journey proposal.'
                : 'Select a style to inspect it on the sample shipment.'
            }}
          </p>
          <button
            v-for="item in styles"
            :key="item.key"
            class="style-line"
            :aria-pressed="theme.key === item.key"
            @click="theme = item"
          >
            <i :style="{ background: item.colors.brand }" /><span>{{
              item.name
            }}</span
            ><small>v{{ item.version }}</small
            ><Check v-if="theme.key === item.key" :size="15" />
          </button>
          <div class="palette-slip">
            <span class="dir-label">Style colors</span>
            <div v-for="(color, name) in theme.colors" :key="name">
              <i :style="{ background: color }" /><span>{{ name }}</span
              ><code>{{ color }}</code>
            </div>
          </div>
          <Link
            v-if="section === 'studio'"
            class="dir-link"
            :href="route('admin.portal-review.index') + '#proposal'"
            >Open revision and proof tools <ArrowUpRight :size="15"
          /></Link>
        </section>
        <section class="workspace-canvas">
          <ShipmentCanvas :style-value="theme" banner="jayco-overlook" />
        </section>
      </div>
    </template>
    <template v-else-if="section === 'assets'">
      <div class="section-heading">
        <h2>Jayco collection</h2>
        <div class="scan-filters">
          <button
            v-for="kind in ['All', 'Banner', 'Logo']"
            :key="kind"
            :aria-pressed="filter === kind"
            @click="changeFilter(kind)"
          >
            {{ kind === 'All' ? 'All assets' : kind }}
          </button>
        </div>
      </div>
      <div class="asset-spread">
        <div class="contact-sheet">
          <button
            v-for="item in filtered"
            :key="item.key"
            :aria-pressed="asset.key === item.key"
            @click="asset = item"
          >
            <img
              :src="assetUrl(item.key)"
              :alt="item.name"
              :class="{ wordmark: item.type === 'Logo' }"
            /><span
              ><strong>{{ item.name }}</strong
              ><small>{{ item.type }}</small></span
            >
          </button>
        </div>
        <aside class="detail-card asset-detail">
          <p class="dir-label">Asset details</p>
          <img
            :src="assetUrl(asset.key)"
            :alt="asset.name"
            :class="{ wordmark: asset.type === 'Logo' }"
          />
          <h3>{{ asset.name }}</h3>
          <p>{{ asset.type }} · WebP</p>
          <a
            class="dir-link"
            :href="assetUrl(asset.key)"
            target="_blank"
            rel="noopener"
            >View original <ArrowUpRight :size="15"
          /></a>
        </aside>
      </div>
    </template>
    <template v-else-if="section === 'access'">
      <div class="workspace-columns">
        <section>
          <div class="section-heading"><h2>Access directory</h2></div>
          <button
            v-for="item in directories"
            :key="item.route"
            class="file-row"
            :aria-pressed="directory.route === item.route"
            @click="directory = item"
          >
            <ShieldCheck /><span
              ><strong>{{ item.name }}</strong
              ><small>{{ item.detail }}</small></span
            ><ArrowRight />
          </button>
        </section>
        <aside class="detail-card">
          <div class="dir-label">Administration</div>
          <h3>{{ directory.name }}</h3>
          <p>{{ directory.detail }}</p>
          <dl v-if="directory.name === 'Users'" class="file-specs">
            <div>
              <dt>Signed in</dt>
              <dd>{{ user.first_name }} {{ user.last_name }}</dd>
            </div>
          </dl>
          <Link
            :href="route(directory.route)"
            class="dir-link"
            :aria-label="
              'Manage ' + directory.name.toLowerCase() + ' in the current admin'
            "
            >Manage {{ directory.name.toLowerCase() }} <ArrowUpRight :size="15"
          /></Link>
        </aside>
      </div>
    </template>
  </div>
</template>
