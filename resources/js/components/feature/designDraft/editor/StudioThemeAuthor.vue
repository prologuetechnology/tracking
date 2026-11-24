<script setup>
import { ref } from 'vue'

import ThemeForm from '@/components/feature/theme/ThemeForm.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useThemeQuery } from '@/composables/queries/theme'

const props = defineProps({
  themeId: { type: [Number, String], default: null },
  versionId: { type: [Number, String], default: null },
})
const emit = defineEmits([`close`, `saved`])
const {
  data: theme,
  isError,
  refetch,
} = useThemeQuery({ id: props.themeId, config: { enabled: !!props.themeId } })
const loadKey = ref(0)
async function reload() {
  const result = await refetch()
  if (result.isSuccess) loadKey.value++
}
</script>

<template>
  <Dialog :open="true" @update:open="!$event && emit('close')">
    <DialogContent
      class="max-h-[92dvh] w-[calc(100%-2rem)] max-w-4xl overflow-y-auto"
    >
      <DialogHeader>
        <DialogTitle>{{
          themeId ? 'Edit library theme' : 'Create library theme'
        }}</DialogTitle>
        <DialogDescription
          >Save a reusable version here. Choose Use in draft to apply it to your
          proposal.</DialogDescription
        >
      </DialogHeader>
      <div v-if="themeId && isError" role="alert">
        <p>Could not load this theme.</p>
        <Button type="button" variant="outline" @click="reload">Retry</Button>
      </div>
      <p v-else-if="themeId && !theme" role="status">
        Loading version history…
      </p>
      <ThemeForm
        v-else
        :key="loadKey"
        embedded
        :theme="themeId ? theme : null"
        :initial-version-id="versionId"
        @saved="emit('saved', $event)"
        @cancel="emit('close')"
        @reload="reload"
      />
    </DialogContent>
  </Dialog>
</template>
