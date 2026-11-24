<script setup>
import SignalShell from './SignalShell.vue'
defineProps({
  wide: Boolean,
  title: { type: String, default: null },
  action: { type: [Function, Object], default: null },
})
</script>
<template>
  <SignalShell>
    <div :class="{ 'signal-content-wide': wide }">
      <header v-if="title || action" class="signal-page-heading">
        <h1 v-if="title">{{ title }}</h1>
        <component :is="action" v-if="action" />
      </header>
      <slot />
    </div>
  </SignalShell>
</template>
