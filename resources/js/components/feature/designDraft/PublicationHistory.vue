<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'

import ConfirmPublication from './ConfirmPublication.vue'
const props = defineProps({
  company: { type: Object, required: true },
  publications: { type: Array, required: true },
})
const current = computed(() => props.publications[0])
const form = useForm({ publication: 0, restore: 0 })
const title = (entry) =>
  entry?.version
    ? entry.name + ` · Revision ` + entry.version
    : `Original Classic branding`
const proofUrl = (entry) =>
  route(`admin.drafts.preview`, [
    props.company.uuid,
    entry.draft_uuid,
    entry.version,
  ]) + (entry.proof_uuid ? `#proof-` + entry.proof_uuid : ``)
const restore = (entry) => {
  form.publication = current.value.id
  form.restore = entry?.id ?? 0
  form.post(route(`admin.drafts.rollback`, props.company.uuid), {
    preserveScroll: true,
  })
}
</script>
<template>
  <Card as="section" class="my-6 grid gap-2 p-5">
    <h2 class="text-sm font-semibold text-muted-foreground">
      Live tracking design
    </h2>
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
      <p class="font-semibold">{{ title(current) }}</p>
      <Link
        v-if="current?.draft_uuid"
        :href="proofUrl(current)"
        class="text-sm font-medium underline underline-offset-4"
        >{{
          current.proof_uuid ? 'View archived proof' : 'View live revision'
        }}</Link
      >
    </div>
    <Collapsible v-if="current" class="mt-2">
      <CollapsibleTrigger as-child>
        <Button size="sm" variant="outline" type="button">
          Publication history
        </Button>
      </CollapsibleTrigger>
      <CollapsibleContent>
        <ul class="mt-3 divide-y">
          <li
            v-for="entry in publications"
            :key="entry.id"
            class="flex flex-wrap items-center gap-3 py-2 text-sm"
          >
            <span>{{ title(entry) }}</span
            ><span
              >{{ entry.action === 'rollback' ? 'Restored' : 'Published' }}
              {{ new Date(entry.at).toLocaleString() }}</span
            >
            <Link
              v-if="entry.draft_uuid"
              :href="proofUrl(entry)"
              class="font-medium underline underline-offset-4"
            >
              {{ entry.proof_uuid ? 'View archived proof' : 'View revision' }}
            </Link>
            <ConfirmPublication
              v-if="entry.id !== current.id"
              title="Restore this tracking design?"
              :description="
                'Existing tracking links for ' +
                company.name +
                ' will use ' +
                title(entry) +
                '.'
              "
              action="Restore design"
              :busy="form.processing"
              @confirm="restore(entry)"
            >
              <Button size="sm" variant="outline" :disabled="form.processing">
                Restore this version
              </Button>
            </ConfirmPublication>
          </li>
        </ul>
        <ConfirmPublication
          v-if="current.version"
          title="Restore this tracking design?"
          :description="
            'Existing tracking links for ' +
            company.name +
            ' will use ' +
            title(null) +
            '.'
          "
          action="Restore design"
          :busy="form.processing"
          @confirm="restore(null)"
        >
          <Button size="sm" variant="outline" :disabled="form.processing">
            Restore original Classic branding
          </Button>
        </ConfirmPublication>

        <p v-if="form.hasErrors" role="alert" class="text-sm text-destructive">
          {{ Object.values(form.errors)[0] }}
        </p>
      </CollapsibleContent>
    </Collapsible>
  </Card>
</template>
