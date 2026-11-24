<script setup>
import { Download } from 'lucide-vue-next'
import { ref, watch } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
const props = defineProps({
  revision: { type: Object, required: true },
  busy: Boolean,
})
const emit = defineEmits([`export`, `approve`])
const approver = ref(``),
  selected = ref(null)
watch(
  () => props.revision.id,
  () => {
    approver.value = ``
    selected.value = null
  },
)
const png = (view) => `data:image/png;base64,` + props.revision.views[view].png
</script>
<template>
  <div class="proof-review">
    <div class="proof-header">
      <div>
        <p class="kicker">Jayco · Follow the journey</p>
        <h1>Revision {{ revision.number }} proofs</h1>
        <p class="intro">
          {{ revision.style.name }} · Theme version
          {{ revision.style.version }} · PRO-DEMO-7018
        </p>
      </div>
    </div>
    <div v-if="!revision.views" class="panel">
      <h2>Export this revision</h2>
      <p>
        Capture the desktop and mobile pages from the saved shipment and style.
      </p>
      <Button :disabled="busy" @click="emit('export')">
        {{ busy ? 'Rendering both PNGs…' : 'Export desktop and mobile PNGs' }}
      </Button>
    </div>
    <template v-else>
      <div class="proof-grid">
        <article
          v-for="(width, view) in { desktop: 1440, mobile: 390 }"
          :key="view"
          class="panel"
        >
          <div class="proof-title">
            <h2>{{ view === 'desktop' ? 'Desktop' : 'Mobile' }}</h2>
            <span>{{ width }} px</span>
          </div>
          <button
            class="proof-image"
            :aria-label="'Inspect ' + view + ' proof'"
            @click="selected = view"
          >
            <img :src="png(view)" :alt="view + ' Jayco proof'" />
          </button>
          <a
            :href="png(view)"
            :download="'jayco-r' + revision.number + '-' + view + '.png'"
          >
            <Download :size="15" />
            Download PNG
          </a>
        </article>
      </div>
      <div class="panel approval">
        <div>
          <h2>External approval</h2>
          <p>
            {{
              revision.approval
                ? 'Recorded for ' + revision.approval
                : 'After the customer reviews both images, record who approved this revision.'
            }}
          </p>
        </div>
        <form
          v-if="!revision.approval"
          @submit.prevent="approver.trim() && emit('approve', approver.trim())"
        >
          <label for="reviewer">Customer approver</label>
          <Input
            id="reviewer"
            v-model="approver"
            name="customer_approver"
            autocomplete="off"
            required
            maxlength="120"
          />
          <Button :disabled="busy">Record review approval</Button>
        </form>
      </div>
    </template>
    <Dialog :open="!!selected" @update:open="!$event && (selected = null)">
      <DialogContent class="max-h-[90dvh] max-w-[90vw] overflow-auto">
        <DialogHeader>
          <DialogTitle>
            {{ selected }} proof · Revision {{ revision.number }}
          </DialogTitle>
        </DialogHeader>
        <img
          v-if="selected"
          :src="png(selected)"
          :alt="selected + ' proof at full resolution'"
          class="max-w-none"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>
<style scoped>
.proof-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
  gap: 18px;
}
.proof-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.proof-title span {
  color: var(--sub);
  font-size: 12px;
}
.proof-image {
  display: flex;
  justify-content: center;
  width: 100%;
  background: #e9ece4;
  border-radius: 8px;
  overflow: hidden;
  padding: 12px;
  margin: 12px 0;
}
.proof-image img {
  max-height: 410px;
  object-fit: contain;
}
.proof-grid a {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 650;
}
.approval {
  margin-top: 18px;
}
.approval form {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.approval form input {
  max-width: 240px;
}
@media (max-width: 680px) {
  .proof-grid {
    grid-template-columns: 1fr;
  }
}
</style>
