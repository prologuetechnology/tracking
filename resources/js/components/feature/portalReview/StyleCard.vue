<script setup>
import { ArrowUpRight } from 'lucide-vue-next'
import { computed } from 'vue'

import { assetUrl } from './reviewStyles'
const props = defineProps({ theme: { type: Object, required: true } })
const colors = computed(() => props.theme.colors)
</script>
<template>
  <button
    type="button"
    class="style-card"
    :aria-label="'Open ' + theme.name + ' version ' + theme.version"
  >
    <div
      class="style-mini"
      :style="{
        background: colors.paper,
        color: theme.overrides.foreground || colors.brand,
      }"
    >
      <div class="mini-head">
        <span :style="{ background: colors.brand }"></span>
        Shipment tracking
      </div>
      <div class="mini-page">
        <div>
          <small>IN → GA</small>
          <strong
            :style="{
              fontFamily:
                theme.headline === 'sans' ? 'inherit' : 'Georgia, serif',
            }"
          >
            Middlebury
            <br />
            to Byron
          </strong>
        </div>
        <img :src="assetUrl('jayco-overlook')" alt="" />
      </div>
      <div class="mini-rule" :style="{ background: colors.accent }"></div>
    </div>
    <div class="style-caption">
      <div>
        <strong>{{ theme.name }}</strong>
        <small>Version {{ theme.version }}</small>
      </div>
      <ArrowUpRight class="size-4" />
    </div>
  </button>
</template>
<style scoped>
.style-card {
  width: 100%;
  text-align: left;
  overflow: hidden;
  border-radius: 12px;
  border: 1px solid var(--line);
  background: white;
}
.style-card:hover {
  border-color: #788670;
}
.style-mini {
  padding: 15px 17px;
}
.mini-head {
  display: flex;
  align-items: center;
  gap: 5px;
  font: 600 9px system-ui;
}
.mini-head span {
  height: 7px;
  width: 7px;
  border-radius: 2px;
}
.mini-page {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 7px;
  padding: 12px 0;
}
.mini-page small {
  font: 8px monospace;
}
.mini-page strong {
  display: block;
  font-size: 22px;
  letter-spacing: -0.04em;
  line-height: 0.97;
}
.mini-page img {
  width: 41%;
  height: 56px;
  object-fit: cover;
  border-radius: 4px;
}
.mini-rule {
  height: 4px;
  width: 36%;
  border-radius: 2px;
}
.style-caption {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 15px;
}
.style-caption small,
.style-caption strong {
  display: block;
}
.style-caption small {
  color: var(--sub);
  font-size: 11px;
}
</style>
