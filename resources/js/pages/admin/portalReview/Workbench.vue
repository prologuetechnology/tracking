<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import {
  ArrowLeft,
  ArrowRight,
  ArrowUpRight,
  Check,
  FileImage,
  Image as ImageIcon,
  Palette,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

import PortalReviewShell from '@/components/feature/portalReview/PortalReviewShell.vue'
import ProofReview from '@/components/feature/portalReview/ProofReview.vue'
import {
  assets,
  assetUrl,
  clone,
} from '@/components/feature/portalReview/reviewStyles'
import ShipmentCanvas from '@/components/feature/portalReview/ShipmentCanvas.vue'
import ShipmentLookup from '@/components/feature/portalReview/ShipmentLookup.vue'
import StyleCard from '@/components/feature/portalReview/StyleCard.vue'
import { usePortalReview } from '@/components/feature/portalReview/usePortalReview'
import ThemeStyleControls from '@/components/feature/theme/ThemeStyleControls.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
const { shipment } = defineProps({ shipment: { type: Object, required: true } })
const { userCan, userIs } = useRolesAndPermissions()
const permissions = {
  company: userCan(`company:show`),
  design: userCan(`company:show`) && userCan(`company:update`),
  themes: userCan(`theme:show`),
  author: userCan(`theme:show`) && userCan(`theme:update`),
  assets: userCan(`image:show`),
  access: userIs(`Super Admin`),
}
const {
  section,
  page,
  archive,
  loaded,
  busy,
  notice,
  error,
  draft,
  working,
  banner,
  latest,
  revision,
  history,
  dirty,
  go,
  edit,
  proposal,
  saveTheme,
  apply,
  saveRevision,
  exportRevision,
  approve,
} = usePortalReview(usePage().props.auth.user.uuid, permissions)
const reference = ref(``),
  lookupType = ref(`carrierPro`),
  result = ref(``),
  search = ref(``),
  filter = ref(`All`),
  picking = ref(false),
  pane = ref(`edit`),
  derived = ref({})
const editorForm = ref(null)
const matchingAssets = computed(() =>
  assets.filter(
    (a) =>
      (filter.value === `All` || a.type === filter.value) &&
      a.name.toLowerCase().includes(search.value.trim().toLowerCase()),
  ),
)
const matchingThemes = computed(() =>
  latest.value.filter((t) =>
    t.name.toLowerCase().includes(search.value.trim().toLowerCase()),
  ),
)
const applied = computed(
  () =>
    !dirty.value &&
    draft.value.key === working.value.key &&
    draft.value.version === working.value.version,
)
function navigate(screen) {
  search.value = ``
  filter.value = `All`
  picking.value = false
  notice.value = ``
  go(screen)
}
function cancelPicker() {
  picking.value = false
  go(`proposal`)
}
function pick() {
  picking.value = true
  search.value = ``
  filter.value = `Banner`
  go(`assets`)
}
function selectBanner(asset) {
  banner.value = asset.key
  picking.value = false
  notice.value = asset.name + ` selected for this proposal.`
  go(`proposal`)
}
function openProposal() {
  pane.value = `edit`
  proposal()
}
function changeStyle(event) {
  const selected = archive.value.themes[Number(event.target.value)]
  if (selected) working.value = clone(selected)
}
function saveLibraryVersion() {
  if (editorForm.value?.reportValidity()) saveTheme()
}
const labels = {
  lookup: `Shipment lookup`,
  companies: `Companies`,
  company: `Jayco`,
  proposal: `Follow the journey`,
  themes: `Themes`,
  editor: `Theme editor`,
  assets: `Shared assets`,
  proof: `Revision proofs`,
  access: `Access`,
}
</script>
<template>
  <PortalReviewShell
    :section="section"
    :permissions="permissions"
    @navigate="navigate"
  >
    <Head :title="labels[page] + ' · Staff workbench'" />
    <p v-if="notice" class="message" role="status">
      <Check :size="16" />
      {{ notice }}
    </p>
    <p v-if="error" class="message issue" role="alert">{{ error }}</p>
    <ShipmentLookup
      v-if="page === 'lookup'"
      v-model="reference"
      v-model:type="lookupType"
      v-model:result="result"
      :shipment="shipment"
      :can-company="permissions.company"
      :can-design="permissions.design"
      @company="go('company')"
      @proposal="openProposal"
    />
    <template v-else-if="page === 'companies'">
      <div class="view-heading">
        <p class="kicker">Workspace</p>
        <h1>Companies</h1>
      </div>
      <label for="find-company">Find a company</label>
      <Input
        id="find-company"
        v-model="search"
        class="search-box"
        type="search"
        placeholder="Company name"
      />
      <button
        v-if="'jayco'.includes(search.toLowerCase().trim())"
        class="company-row"
        @click="go('company')"
      >
        <img :src="assetUrl('jayco-logo')" alt="" />
        <span>
          <strong>Jayco</strong>
          <small>Live design · Classic</small>
        </span>
        <ArrowRight :size="17" />
      </button>
      <p v-else role="status">No matching company.</p>
    </template>
    <template v-else-if="page === 'company'">
      <div class="company-hero">
        <img :src="assetUrl('jayco-overlook')" alt="" />
        <div>
          <p class="kicker">Company workspace</p>
          <h1>Jayco</h1>
          <p v-if="result === 'found'">Shipment {{ shipment.carrierPro }}</p>
        </div>
      </div>
      <div class="company-grid">
        <article class="panel">
          <p class="kicker">Live design</p>
          <h2>Classic</h2>
          <p class="intro">Jayco’s current tracking page.</p>
          <Button as-child variant="outline">
            <a
              :href="route('admin.portal-review.classic')"
              target="_blank"
              rel="noopener"
            >
              Open customer page
              <ArrowUpRight :size="15" />
            </a>
          </Button>
        </article>
        <article v-if="permissions.design" class="panel">
          <p class="kicker">Proposal</p>
          <h2>Follow the journey</h2>
          <p class="intro">Shipment itinerary and carrier travel log.</p>
          <Button @click="openProposal">
            Open proposal
            <ArrowRight :size="15" />
          </Button>
        </article>
      </div>
      <section v-if="permissions.design" class="panel history">
        <h2>Saved revisions and proofs</h2>
        <p v-if="!history.length">
          Save a proposal revision to start its review history.
        </p>
        <button
          v-for="item in history"
          :key="item.id"
          class="history-row"
          @click="go('proof', item.id)"
        >
          <FileImage :size="17" />
          <span>
            <strong>Revision {{ item.number }}</strong>
            <small>{{ item.style.name }} · v{{ item.style.version }}</small>
          </span>
          <span>
            {{
              item.approval
                ? 'Approved'
                : item.views
                  ? '2 archived PNGs'
                  : 'Ready to export'
            }}
          </span>
          <ArrowRight :size="15" />
        </button>
      </section>
    </template>
    <template v-else-if="page === 'themes'">
      <div class="view-heading">
        <p class="kicker">Design library</p>
        <h1>Themes</h1>
        <p class="intro">Reusable visual styles for customer proposals.</p>
      </div>
      <label for="find-theme">Find a theme</label>
      <Input
        id="find-theme"
        v-model="search"
        class="search-box"
        type="search"
        placeholder="Theme name"
      />
      <div class="card-grid">
        <article v-for="theme in matchingThemes" :key="theme.key">
          <StyleCard :theme="theme" @click="edit(theme)" />
          <small class="usage">
            {{
              theme.key === 'lake'
                ? 'Jayco · Classic uses v1'
                : draft.key === theme.key
                  ? 'Jayco proposal · v' + draft.version
                  : 'Available for proposals'
            }}
          </small>
        </article>
      </div>
      <p v-if="!matchingThemes.length">No matching themes.</p>
    </template>
    <template v-else-if="page === 'proposal' || page === 'editor'">
      <button
        class="go-back"
        @click="navigate(page === 'proposal' ? 'company' : 'themes')"
      >
        <ArrowLeft :size="14" />
        {{ page === 'proposal' ? 'Jayco workspace' : 'Theme library' }}
      </button>
      <div class="view-heading">
        <p class="kicker">
          {{
            page === 'proposal'
              ? 'Jayco · Proposal'
              : 'Design library · Theme editor'
          }}
        </p>
        <h1>{{ page === 'proposal' ? 'Follow the journey' : working.name }}</h1>
        <p class="intro">
          {{
            page === 'proposal'
              ? 'Applied style: ' + draft.name + ' · v' + draft.version
              : 'Preview this style on the Jayco sample shipment.'
          }}
        </p>
      </div>
      <div class="mobile-panes">
        <Button
          :variant="pane === 'edit' ? 'default' : 'outline'"
          @click="pane = 'edit'"
        >
          Edit
        </Button>
        <Button
          :variant="pane === 'preview' ? 'default' : 'outline'"
          @click="pane = 'preview'"
        >
          Preview
        </Button>
      </div>
      <div class="editor-grid" :data-pane="pane">
        <div class="editor-panel panel">
          <form ref="editorForm" @submit.prevent="saveLibraryVersion">
            <label for="library-version">Library style and version</label>
            <select
              id="library-version"
              :value="
                archive.themes.findIndex(
                  (t) => t.key === working.key && t.version === working.version,
                )
              "
              @change="changeStyle"
            >
              <option
                v-for="(theme, index) in archive.themes"
                :key="theme.key + '-' + theme.version"
                :value="index"
              >
                {{ theme.name }} · v{{ theme.version }}
              </option>
            </select>
            <ThemeStyleControls
              v-model="working"
              :derived="derived"
              :readonly="!permissions.author"
            />
            <Button
              v-if="permissions.author"
              type="submit"
              variant="outline"
              :disabled="!loaded || !dirty || busy"
            >
              <Palette :size="15" />
              Save theme version
            </Button>
          </form>
          <div class="apply-actions">
            <p v-if="dirty">Previewing unsaved colors and styling.</p>
            <Button
              v-if="permissions.design"
              :disabled="!loaded || dirty || busy"
              @click="apply"
            >
              Use in draft
            </Button>
          </div>
          <template v-if="page === 'proposal'">
            <button v-if="permissions.assets" class="asset-link" @click="pick">
              <ImageIcon :size="16" />
              Change banner
              <ArrowRight :size="14" />
            </button>
            <div class="revision-actions">
              <strong>Jayco proposal</strong>
              <small>
                {{ draft.name }} · v{{ draft.version }} ·
                {{ assets.find((a) => a.key === banner)?.name }}
              </small>
              <Button
                :disabled="!loaded || busy || !applied"
                @click="saveRevision"
              >
                Save revision
              </Button>
            </div>
          </template>
        </div>
        <ShipmentCanvas
          class="editor-canvas"
          :style-value="working"
          :banner="banner"
          @derived="derived = $event"
        />
      </div>
    </template>
    <template v-else-if="page === 'assets'">
      <div class="view-heading">
        <p class="kicker">Design library</p>
        <h1>{{ picking ? 'Choose a banner' : 'Shared assets' }}</h1>
      </div>
      <button v-if="picking" class="go-back" @click="cancelPicker">
        <ArrowLeft :size="14" />
        Return to proposal
      </button>
      <div class="asset-filters">
        <div>
          <label for="find-asset">Find an asset</label>
          <Input
            id="find-asset"
            v-model="search"
            type="search"
            placeholder="Asset name"
          />
        </div>
        <div>
          <label for="asset-type">Type</label>
          <select id="asset-type" v-model="filter">
            <option
              v-for="type in picking ? ['Banner'] : ['All', 'Banner', 'Logo']"
              :key="type"
            >
              {{ type }}
            </option>
          </select>
        </div>
      </div>
      <div class="card-grid">
        <article
          v-for="asset in matchingAssets"
          :key="asset.key"
          class="asset-card"
        >
          <img
            :src="assetUrl(asset.key)"
            :alt="asset.name"
            :class="{ logo: asset.type === 'Logo' }"
          />
          <div>
            <strong>{{ asset.name }}</strong>
            <small>{{ asset.type }}</small>
            <Button
              v-if="picking"
              variant="outline"
              @click="selectBanner(asset)"
            >
              Use in proposal
            </Button>
          </div>
        </article>
      </div>
      <p v-if="!matchingAssets.length">No matching assets.</p>
    </template>
    <template v-else-if="page === 'proof' && revision">
      <button class="go-back" @click="go('company')">
        <ArrowLeft :size="14" />
        Jayco workspace
      </button>
      <ProofReview
        :revision="revision"
        :busy="busy"
        @export="exportRevision"
        @approve="approve"
      />
    </template>
    <template v-else-if="page === 'access'">
      <div class="view-heading">
        <p class="kicker">Administration</p>
        <h1>Access</h1>
        <p class="intro">Open the existing staff administration screens.</p>
      </div>
      <div class="panel">
        <a
          v-for="[title, endpoint] in [
            ['Users', 'admin.users.index'],
            ['Roles', 'admin.role.index'],
            ['Permissions', 'admin.permissions.index'],
            ['Allowed domains', 'admin.allowed-domains.index'],
          ]"
          :key="endpoint"
          :href="route(endpoint)"
          class="history-row"
        >
          {{ title }}
          <ArrowUpRight :size="15" />
        </a>
      </div>
    </template>
  </PortalReviewShell>
</template>
<style>
.review-shell h1 {
  font-size: clamp(2rem, 3.6vw, 3.6rem);
  line-height: 1.03;
  font-weight: 650;
  letter-spacing: -0.055em;
}
.review-shell h2 {
  font-size: clamp(1.2rem, 1.55vw, 1.6rem);
  line-height: 1.2;
  font-weight: 650;
  letter-spacing: -0.035em;
}
.review-shell .kicker {
  font-size: 11px;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  font-weight: 700;
  color: #566858;
  margin-bottom: 9px;
}
.review-shell .intro,
.review-shell .panel > p:not(.kicker) {
  color: var(--sub);
  font-size: 14px;
  margin: 9px 0 15px;
}
.review-shell .view-heading,
.review-shell .proof-header {
  margin-bottom: 24px;
}
.review-shell .panel,
.review-shell .asset-card {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: clamp(16px, 2vw, 27px);
  min-width: 0;
}
.review-shell .panel button,
.review-shell .actions button {
  gap: 0.4rem;
}
.review-shell .actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}
.review-shell label {
  font-weight: 600;
  font-size: 13px;
}
.review-shell select {
  background: white;
  border: 1px solid var(--line);
  border-radius: 7px;
  padding: 8px 10px;
  font-size: 14px;
}
.review-shell .status,
.review-shell .message {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border-radius: 7px;
  background: #e7eedf;
  color: #36562f;
  padding: 5px 9px;
  font-size: 12px;
}
.review-shell .message {
  margin-bottom: 15px;
}
.review-shell .issue {
  background: #f9e5d9;
  color: #763711;
}
.review-shell .go-back,
.review-shell .asset-link {
  display: inline-flex;
  gap: 7px;
  align-items: center;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 14px;
}
.review-shell .search-box {
  max-width: 360px;
  margin: 5px 0 18px;
}
.review-shell .company-row,
.review-shell .history-row {
  display: flex;
  align-items: center;
  gap: 13px;
  text-align: left;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);
  width: 100%;
}
.review-shell .company-row {
  background: white;
  padding: 16px;
  border: 1px solid var(--line);
  border-radius: 9px;
}
.review-shell .company-row img {
  width: 70px;
  height: 44px;
  object-fit: contain;
}
.review-shell .company-row span,
.review-shell .history-row span:first-of-type {
  margin-right: auto;
}
.review-shell small {
  display: block;
  color: var(--sub);
  font-size: 12px;
}
.review-shell .history-row span {
  font-size: 13px;
}
.review-shell .company-hero {
  height: 210px;
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  background: #27342e;
  margin-bottom: 18px;
}
.review-shell .company-hero img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.review-shell .company-hero > div {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: end;
  align-items: start;
  padding: 25px;
  color: white;
  background: linear-gradient(70deg, #18231ce0, transparent 80%);
}
.review-shell .company-hero .kicker {
  color: #f0f3dc;
}
.review-shell .company-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.review-shell .history {
  margin-top: 17px;
}
.review-shell .card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(215px, 100%), 1fr));
  gap: 16px;
}
.review-shell .usage {
  padding: 7px 3px;
}
.review-shell .editor-grid {
  display: grid;
  grid-template-columns: minmax(265px, 340px) minmax(0, 1fr);
  gap: 18px;
}
.review-shell .editor-panel {
  align-self: start;
}
.review-shell .editor-panel form > select {
  width: 100%;
  margin: 6px 0 20px;
}
.review-shell .apply-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin: 10px 0;
}
.review-shell .apply-actions p {
  font-size: 12px;
}
.review-shell .asset-link {
  margin: 10px 0;
}
.review-shell .revision-actions {
  display: grid;
  gap: 7px;
  margin-top: 13px;
  padding-top: 13px;
  border-top: 1px solid var(--line);
}
.review-shell .mobile-panes {
  display: none;
}
.review-shell .asset-filters {
  display: flex;
  gap: 12px;
  align-items: end;
  margin-bottom: 18px;
}
.review-shell .asset-filters label {
  display: block;
  margin-bottom: 5px;
}
.review-shell .asset-card {
  padding: 0;
  overflow: hidden;
}
.review-shell .asset-card img {
  width: 100%;
  height: 140px;
  object-fit: cover;
}
.review-shell .asset-card img.logo {
  object-fit: contain;
  padding: 23px;
  background: #fafaf8;
}
.review-shell .asset-card > div {
  padding: 15px;
}
.review-shell .asset-card button {
  margin-top: 12px;
}
@media (max-width: 740px) {
  .review-shell .company-grid,
  .review-shell .editor-grid {
    grid-template-columns: 1fr;
  }
  .review-shell .mobile-panes {
    display: flex;
    gap: 5px;
    margin-bottom: 10px;
  }
  .review-shell [data-pane='edit'] > .editor-canvas,
  .review-shell [data-pane='preview'] > .editor-panel {
    display: none;
  }
  .review-shell .company-hero {
    height: 170px;
  }
}
</style>
