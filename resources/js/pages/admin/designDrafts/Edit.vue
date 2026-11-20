<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

import DraftSelect from '@/components/feature/designDraft/DraftSelect.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  company: { type: Object, required: true },
  draft: { type: Object, default: null },
  initialValues: { type: Object, required: true },
  themes: { type: Array, required: true },
  images: { type: [Object, Array], required: true },
  sections: { type: [Object, Array], required: true },
  revisions: { type: Array, required: true },
})
const form = useForm(props.initialValues)

const assets = [
  { key: `logo`, label: `Logo`, empty: `Default logo` },
  { key: `banner`, label: `Banner`, empty: `No banner` },
  { key: `footer`, label: `Footer`, empty: `No footer image` },
]
const save = () => {
  if (props.draft)
    form.put(
      route(`admin.drafts.update`, [props.company.uuid, props.draft.uuid]),
      { preserveState: `errors` },
    )
  else
    form.post(route(`admin.drafts.store`, props.company.uuid), {
      preserveState: `errors`,
    })
}
const preview = (version) =>
  route(`admin.drafts.preview`, [props.company.uuid, props.draft.uuid, version])
</script>

<template>
  <Head :title="`${draft?.name ?? 'New design draft'} · ${company.name}`" />
  <AuthenticatedLayout :title="draft?.name ?? 'Create design draft'">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <Link
        class="text-sm underline"
        :href="route('admin.drafts.index', company.uuid)"
      >
        {{ company.name }} drafts
      </Link>
      <Button v-if="draft" variant="outline" as-child>
        <Link :href="preview(initialValues.base_version)">
          Preview saved revision {{ initialValues.base_version }}
        </Link>
      </Button>
    </div>
    <form class="grid gap-5" @submit.prevent="save">
      <Card>
        <CardHeader>
          <CardTitle>Classic branding</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-5">
          <div class="grid gap-2">
            <Label for="draft-name">Draft name</Label>
            <Input
              id="draft-name"
              v-model="form.name"
              required
              maxlength="120"
              :aria-invalid="!!form.errors.name"
            />
            <p
              v-if="form.errors.name"
              role="alert"
              class="text-sm text-destructive"
            >
              {{ form.errors.name }}
            </p>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <DraftSelect
              id="draft-theme"
              v-model="form.theme_id"
              label="Theme"
              empty-label="Default colors"
              :choices="themes"
              :error="form.errors.theme_id"
            />
            <DraftSelect
              v-for="asset in assets"
              :id="'draft-' + asset.key"
              :key="asset.key"
              v-model="form[asset.key + '_image_id']"
              :label="asset.label"
              :empty-label="asset.empty"
              :choices="images[asset.key] ?? []"
              :error="form.errors[asset.key + '_image_id']"
            />
          </div>
          <fieldset
            v-if="Object.keys(sections).length"
            class="flex flex-wrap gap-4"
          >
            <legend class="mb-2 text-sm font-medium">
              Visible shipment details
            </legend>
            <label
              v-for="(label, slug) in sections"
              :key="slug"
              class="flex items-center gap-2 text-sm"
            >
              <input v-model="form.sections" type="checkbox" :value="slug" />
              {{ label }}
            </label>
          </fieldset>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Preview shipment</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2">
          <DraftSelect
            id="lookup-type"
            v-model="form.search_option"
            label="Find shipment by"
            :choices="[
              { id: 'bol', name: 'Bill of lading' },
              { id: 'carrierPro', name: 'Carrier PRO' },
            ]"
            :error="form.errors.search_option"
          />
          <div class="grid gap-2">
            <Label for="tracking-number">Shipment number (optional)</Label>
            <Input
              id="tracking-number"
              v-model="form.tracking_number"
              maxlength="128"
            />
            <p
              v-if="form.errors.tracking_number"
              role="alert"
              class="text-sm text-destructive"
            >
              {{ form.errors.tracking_number }}
            </p>
          </div>
        </CardContent>
      </Card>
      <p
        v-if="form.errors.base_version"
        role="alert"
        class="text-sm text-destructive"
      >
        {{ form.errors.base_version }}
        <a
          :href="route('admin.drafts.show', [company.uuid, draft.uuid])"
          class="underline"
        >
          Reopen draft
        </a>
      </p>
      <div class="flex items-center gap-4">
        <Button type="submit" :disabled="form.processing">
          {{ draft ? 'Save new revision' : 'Save draft' }}
        </Button>
        <span v-if="form.recentlySuccessful" role="status" class="text-sm">
          Draft saved
        </span>
      </div>
    </form>
    <section v-if="revisions.length > 1" class="mt-8">
      <h2 class="mb-3 font-semibold">Saved revisions</h2>
      <ul class="grid gap-2">
        <li v-for="item in revisions" :key="item.version">
          <Link class="text-sm underline" :href="preview(item.version)">
            Preview revision {{ item.version }}
          </Link>
          <span class="text-sm text-muted-foreground">
            · {{ new Date(item.created_at).toLocaleString() }}
          </span>
        </li>
      </ul>
    </section>
  </AuthenticatedLayout>
</template>
