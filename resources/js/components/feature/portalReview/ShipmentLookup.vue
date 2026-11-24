<script setup>
import { ArrowRight, ArrowUpRight, Check, Search } from 'lucide-vue-next'
import { ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

import { assetUrl } from './reviewStyles'
const props = defineProps({
  shipment: { type: Object, required: true },
  canCompany: Boolean,
  canDesign: Boolean,
})
const emit = defineEmits([`company`, `proposal`])
const reference = defineModel({ type: String, required: true })
// The Vue macro requires literal names for named models.
/* eslint-disable quotes */
const lookupType = defineModel('type', { type: String, required: true })
const result = defineModel('result', { type: String, required: true })
/* eslint-enable quotes */
const submitted = ref(``)
function lookup() {
  submitted.value = reference.value
  result.value =
    reference.value.trim().toUpperCase() ===
    props.shipment[lookupType.value].toUpperCase()
      ? `found`
      : `missing`
}
function sample() {
  reference.value = props.shipment[lookupType.value]
  lookup()
}
</script>
<template>
  <div class="shipment-lookup">
    <div class="view-heading">
      <p class="kicker">Operations</p>
      <h1>Shipment lookup</h1>
      <p class="intro">Find a shipment and open its customer tracking page.</p>
    </div>
    <div class="lookup-grid">
      <section class="panel search-panel">
        <form @submit.prevent="lookup">
          <h2>Find a shipment</h2>
          <label for="lookup-method">Search by</label>
          <select id="lookup-method" v-model="lookupType" @change="result = ''">
            <option value="carrierPro">Carrier PRO</option>
            <option value="bolNum">Bill of lading</option>
          </select>
          <label for="lookup-input">Shipment reference</label>
          <div class="search-line">
            <Input
              id="lookup-input"
              v-model="reference"
              placeholder="Enter shipment reference"
              required
              @input="result = ''"
            />
            <Button type="submit">
              <Search class="size-4" />
              Find shipment
            </Button>
          </div>
          <button type="button" class="sample" @click="sample">
            Use sample {{ shipment[lookupType] }}
            <ArrowRight :size="13" />
          </button>
        </form>
        <div v-if="result === 'found'" class="found">
          <div class="found-title">
            <strong>{{ shipment.carrierPro }}</strong>
            <span class="status">
              <Check :size="13" />
              Delivered
            </span>
          </div>
          <p>Middlebury, IN → Byron, GA</p>
          <small>Latest scan · Nov 18, 2026 at 11:33 AM in Byron, GA</small>
          <div class="actions">
            <Button v-if="canCompany" @click="emit('company')">
              Open company workspace
            </Button>
            <Button as-child variant="outline">
              <a
                :href="route('admin.portal-review.classic')"
                target="_blank"
                rel="noopener"
              >
                Open customer page
                <ArrowUpRight :size="14" />
              </a>
            </Button>
          </div>
        </div>
        <p v-if="result === 'missing'" role="status" class="found">
          No fixture shipment matched {{ submitted }}. Check the lookup type or
          use the sample above.
        </p>
      </section>
      <article v-if="canCompany" class="customer-card">
        <img
          :src="assetUrl('jayco-overlook')"
          alt="Jayco motorhome at a canyon campsite"
        />
        <div>
          <p class="kicker">Company workspace</p>
          <h2>Jayco</h2>
          <p>Classic is live · 1 proposal</p>
          <button class="text-link" @click="emit('company')">
            View workspace
            <ArrowRight :size="15" />
          </button>
        </div>
      </article>
    </div>
    <section v-if="canDesign" class="recent">
      <h2>Design in progress</h2>
      <button class="proposal-row" @click="emit('proposal')">
        <img :src="assetUrl('jayco-logo')" alt="" />
        <span>
          <strong>Follow the journey</strong>
          <small>Jayco · Tracking proposal</small>
        </span>
        <ArrowRight :size="17" />
      </button>
    </section>
  </div>
</template>
<style scoped>
.lookup-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.55fr) minmax(240px, 1fr);
  gap: 22px;
  align-items: start;
}
.search-panel {
  padding: clamp(20px, 2.5vw, 38px);
}
.search-panel form {
  display: grid;
  gap: 9px;
}
.search-panel h2 {
  margin-bottom: 10px;
}
.search-panel label {
  font-weight: 650;
  font-size: 13px;
}
.search-panel select {
  justify-self: start;
  margin-bottom: 8px;
}
.search-line {
  display: flex;
  gap: 8px;
}
.search-line input {
  min-width: 0;
  flex: 1;
}
.sample,
.text-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 650;
  font-size: 13px;
}
.sample {
  justify-self: start;
  margin-top: 4px;
  color: #516446;
}
.customer-card {
  overflow: hidden;
  border: 1px solid var(--line);
  border-radius: 15px;
  background: #fff;
}
.customer-card > img {
  width: 100%;
  height: clamp(130px, 13vw, 208px);
  object-fit: cover;
}
.customer-card > div {
  padding: 18px 21px;
}
.customer-card p:not(.kicker),
.found small {
  color: var(--sub);
  font-size: 13px;
}
.customer-card button {
  margin-top: 13px;
}
.found {
  margin-top: 23px;
  padding-top: 19px;
  border-top: 1px solid var(--line);
}
.found-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 7px;
  flex-wrap: wrap;
}
.recent {
  margin-top: 28px;
}
.proposal-row {
  margin-top: 12px;
  width: 100%;
  text-align: left;
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 14px 18px;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
}
.proposal-row img {
  width: 52px;
  height: 37px;
  object-fit: contain;
}
.proposal-row span {
  margin-right: auto;
}
.proposal-row strong,
.proposal-row small {
  display: block;
}
.proposal-row small {
  color: var(--sub);
}
@media (max-width: 690px) {
  .lookup-grid {
    grid-template-columns: 1fr;
  }
  .search-line {
    flex-wrap: wrap;
  }
  .customer-card > img {
    height: 155px;
  }
}
</style>
