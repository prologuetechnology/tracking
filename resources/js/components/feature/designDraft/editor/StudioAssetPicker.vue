<script setup>
import { Check, Image as ImageIcon, ImageOff } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  id: { type: String, required: true },
  label: { type: String, required: true },
  choices: { type: Array, required: true },
  emptyLabel: { type: String, required: true },
  error: { type: String, default: null },
})
const value = defineModel({ type: [Number, String], default: null })
const open = ref(false)
const search = ref(``)
const selected = computed(() =>
  props.choices.find((item) => String(item.id) === String(value.value)),
)
const matches = computed(() =>
  props.choices.filter((item) =>
    item.name.toLowerCase().includes(search.value.trim().toLowerCase()),
  ),
)
watch(open, () => {
  search.value = ``
})
const choose = (id) => {
  value.value = id
  open.value = false
}
</script>
<template>
  <div class="grid gap-1.5">
    <Label :for="id">{{ label }}</Label>
    <Dialog v-model:open="open">
      <DialogTrigger as-child>
        <Button
          :id="id"
          type="button"
          variant="outline"
          :aria-invalid="!!error"
          :aria-describedby="error ? id + '-error' : undefined"
          class="h-auto min-h-12 justify-start gap-3 p-2 text-left"
        >
          <img
            v-if="selected?.preview_url"
            :src="selected.preview_url"
            alt=""
            class="h-9 w-16 shrink-0 rounded bg-muted object-contain"
          />
          <ImageIcon
            v-else
            aria-hidden="true"
            class="mx-5 size-5 shrink-0 text-muted-foreground"
          />
          <span class="min-w-0 flex-1 break-words">{{
            selected?.name || emptyLabel
          }}</span>
        </Button>
      </DialogTrigger>
      <DialogContent
        class="max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto"
      >
        <DialogHeader>
          <DialogTitle>Choose {{ label.toLowerCase() }}</DialogTitle>
          <DialogDescription
            >Select an image from the shared library for this
            draft.</DialogDescription
          >
        </DialogHeader>
        <Input
          v-if="choices.length > 6"
          v-model="search"
          type="search"
          :aria-label="'Search ' + label.toLowerCase() + ' images'"
          placeholder="Search image names"
        />
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
          <button
            v-if="!search"
            type="button"
            :aria-pressed="value == null"
            class="asset-choice"
            @click="choose(null)"
          >
            <span class="grid aspect-[2/1] w-full place-items-center bg-muted"
              ><ImageOff
                class="size-5 shrink-0 text-muted-foreground"
                aria-hidden="true"
            /></span>
            <span class="px-2 py-1.5">{{ emptyLabel }}</span>
          </button>
          <button
            v-for="item in matches"
            :key="item.id"
            type="button"
            :dusk="id + '-option-' + item.id"
            :aria-pressed="selected?.id === item.id"
            :aria-label="item.name"
            class="asset-choice"
            @click="choose(item.id)"
          >
            <img
              v-if="item.preview_url"
              :src="item.preview_url"
              alt=""
              loading="lazy"
              class="aspect-[2/1] w-full bg-muted object-contain"
            />
            <span
              v-else
              class="grid aspect-[2/1] w-full place-items-center bg-muted"
              ><ImageIcon
                class="size-5 shrink-0 text-muted-foreground"
                aria-hidden="true"
            /></span>
            <span class="flex items-center gap-1 px-2 py-1.5"
              ><Check
                v-if="selected?.id === item.id"
                class="size-3 shrink-0"
                aria-hidden="true"
              />{{ item.name }}</span
            >
          </button>
        </div>
        <p
          v-if="search && !matches.length"
          role="status"
          class="text-sm text-muted-foreground"
        >
          No images match this search.
        </p>
      </DialogContent>
    </Dialog>
    <p
      v-if="error"
      :id="id + '-error'"
      role="alert"
      class="text-sm text-destructive"
    >
      {{ error }}
    </p>
  </div>
</template>
<style scoped>
.asset-choice {
  @apply flex min-w-0 flex-col items-stretch overflow-hidden rounded-md border text-left text-xs font-medium hover:border-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring;
}
.asset-choice[aria-pressed='true'] {
  @apply border-primary ring-1 ring-primary;
}
</style>
