<script setup>
import { computed } from 'vue'

const props = defineProps({
  colors: { type: Object, required: true },
  radius: { type: String, required: true },
  headline: { type: String, required: true },
})
const appearance = computed(() => ({
  'background': `hsl(${props.colors.background})`,
  'color': `hsl(${props.colors.foreground})`,
  '--sample-card': `hsl(${props.colors.card})`,
  '--sample-card-ink': `hsl(${props.colors.cardForeground})`,
  '--sample-border': `hsl(${props.colors.border})`,
  '--sample-action': `hsl(${props.colors.primary})`,
  '--sample-action-ink': `hsl(${props.colors.primaryForeground})`,
  '--sample-accent': `hsl(${props.colors.accent})`,
  '--sample-radius': props.radius,
}))
</script>

<template>
  <figure class="space-y-2">
    <figcaption class="text-sm font-medium">Tracking style sample</figcaption>
    <div class="sample p-5 sm:p-7" :style="appearance">
      <p class="text-sm">Shipment status</p>
      <h3
        class="mt-2 text-3xl font-semibold"
        :class="{
          'font-serif': headline === 'serif',
          'font-sans': headline === 'sans',
        }"
      >
        Delivered
      </h3>
      <div class="sample-card mt-5 grid gap-3 p-4">
        <span class="text-sm">Latest carrier scan</span>
        <strong>Byron, Georgia</strong>
        <span class="sample-action w-fit px-3 py-1.5 text-sm font-semibold"
          >Shipment details</span
        >
      </div>
    </div>
  </figure>
</template>

<style scoped>
.sample {
  border: 1px solid var(--sample-border);
  border-top: 4px solid var(--sample-accent);
}
.sample-card {
  background: var(--sample-card);
  color: var(--sample-card-ink);
  border: 1px solid var(--sample-border);
  border-radius: var(--sample-radius);
}
.sample-action {
  background: var(--sample-action);
  color: var(--sample-action-ink);
  border-radius: var(--sample-radius);
}
</style>
