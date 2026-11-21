<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import {
  ChevronDown,
  ChevronLeft,
  Eye,
  History,
  Image,
  PanelsTopLeft,
  Settings2,
} from 'lucide-vue-next'
import { computed, nextTick, ref } from 'vue'

import DraftSelect from '@/components/feature/designDraft/DraftSelect.vue'
import StudioAppearance from '@/components/feature/designDraft/editor/StudioAppearance.vue'
import StudioLayoutPicker from '@/components/feature/designDraft/editor/StudioLayoutPicker.vue'
import StudioSections from '@/components/feature/designDraft/editor/StudioSections.vue'
import StudioShipmentPreview from '@/components/feature/designDraft/StudioShipmentPreview.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
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
  catalog: { type: Object, required: true },
})
const form = useForm(props.initialValues)
const mobilePane = ref(`settings`)
const selectedLayout = computed(() =>
  props.catalog.layouts.find((layout) => layout.id === form.layout),
)
const sectionError = computed(
  () =>
    Object.entries(form.errors).find(([key]) => key.startsWith(`section`))?.[1],
)
const assets = [
  { key: `logo`, label: `Logo`, empty: `Default logo` },
  { key: `banner`, label: `Banner`, empty: `No banner` },
  { key: `footer`, label: `Footer`, empty: `No footer image` },
]
const preview = (version) =>
  route(`admin.drafts.preview`, [props.company.uuid, props.draft.uuid, version])
const save = () => {
  const options = {
    preserveState: `errors`,
    onError: async () => {
      mobilePane.value = `settings`
      await nextTick()
      const invalid = document.querySelector(
        `#design-editor [aria-invalid="true"]`,
      )
      if (invalid) {
        const details = invalid.closest(`details`)
        if (details) details.open = true
        invalid.focus()
      }
    },
  }
  if (props.draft)
    form.put(
      route(`admin.drafts.update`, [props.company.uuid, props.draft.uuid]),
      options,
    )
  else form.post(route(`admin.drafts.store`, props.company.uuid), options)
}
</script>

<template>
  <div>
    <Head :title="(draft?.name ?? 'New design draft') + ' · ' + company.name" />
    <AuthenticatedLayout wide>
      <header
        class="mb-5 flex flex-wrap items-end justify-between gap-4 border-b pb-5"
      >
        <div class="min-w-0 space-y-2">
          <Link
            :href="route('admin.drafts.index', company.uuid)"
            class="inline-flex items-center gap-1 text-sm font-medium text-muted-foreground hover:underline"
          >
            <ChevronLeft class="size-4" aria-hidden="true" />{{ company.name }}
            drafts
          </Link>
          <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
            Tracking studio
          </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button v-if="draft" variant="outline" as-child>
            <Link :href="preview(initialValues.base_version)"
              ><Eye class="mr-2 size-4" aria-hidden="true" />Preview saved
              revision {{ initialValues.base_version }}</Link
            >
          </Button>
          <Button
            type="submit"
            form="design-editor"
            :disabled="form.processing"
            >{{ draft ? 'Save new revision' : 'Save draft' }}</Button
          >
        </div>
      </header>

      <div
        class="mb-4 flex gap-1 rounded-lg border bg-muted/40 p-1 lg:hidden"
        role="group"
        aria-label="Studio view"
      >
        <Button
          type="button"
          class="flex-1"
          size="sm"
          :variant="mobilePane === 'settings' ? 'default' : 'ghost'"
          :aria-pressed="mobilePane === 'settings'"
          @click="mobilePane = 'settings'"
          ><Settings2 class="mr-2 size-4" aria-hidden="true" />Settings</Button
        >
        <Button
          type="button"
          class="flex-1"
          size="sm"
          :variant="mobilePane === 'preview' ? 'default' : 'ghost'"
          :aria-pressed="mobilePane === 'preview'"
          @click="mobilePane = 'preview'"
          ><Eye class="mr-2 size-4" aria-hidden="true" />Preview</Button
        >
      </div>

      <div
        class="grid min-w-0 items-start gap-5 lg:grid-cols-[minmax(20rem,25rem)_minmax(0,1fr)] xl:grid-cols-[26rem_minmax(0,1fr)]"
      >
        <div
          class="min-w-0 space-y-5 lg:block"
          :class="mobilePane === 'settings' ? 'block' : 'hidden'"
        >
          <form
            id="design-editor"
            class="overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm"
            novalidate
            @submit.prevent="save"
          >
            <div
              class="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/30 px-5 py-3.5"
            >
              <h2 class="flex items-center gap-2 font-semibold">
                <PanelsTopLeft class="size-4" aria-hidden="true" />Configuration
              </h2>
              <span role="status" class="text-xs text-muted-foreground">{{
                form.isDirty
                  ? 'Unsaved changes'
                  : draft
                    ? 'Revision ' + initialValues.base_version
                    : 'New draft'
              }}</span>
            </div>
            <div class="grid gap-5 border-b p-5">
              <div class="grid gap-2">
                <Label for="draft-name">Draft name</Label>
                <Input
                  id="draft-name"
                  v-model="form.name"
                  maxlength="120"
                  required
                  :aria-invalid="!!form.errors.name"
                  :aria-describedby="
                    form.errors.name ? 'draft-name-error' : undefined
                  "
                />
                <p
                  v-if="form.errors.name"
                  id="draft-name-error"
                  role="alert"
                  class="text-sm text-destructive"
                >
                  {{ form.errors.name }}
                </p>
              </div>
              <StudioLayoutPicker
                v-model="form.layout"
                :layouts="catalog.layouts"
                :error="form.errors.layout"
              />
              <DraftSelect
                v-if="form.layout === 'answer.v1'"
                id="draft-emphasis"
                v-model="form.emphasis"
                label="Show first"
                :choices="[
                  { id: 'delivery', name: 'Delivery timing' },
                  { id: 'event', name: 'Latest carrier event' },
                ]"
                :error="form.errors.emphasis"
              />
            </div>
            <div class="grid gap-4 border-b p-5">
              <h3 class="font-semibold">Preview shipment</h3>
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
                  :aria-invalid="!!form.errors.tracking_number"
                  :aria-describedby="
                    form.errors.tracking_number ? 'tracking-error' : undefined
                  "
                />
                <p
                  v-if="form.errors.tracking_number"
                  id="tracking-error"
                  role="alert"
                  class="text-sm text-destructive"
                >
                  {{ form.errors.tracking_number }}
                </p>
              </div>
              <Button
                type="button"
                variant="outline"
                class="lg:hidden"
                @click="mobilePane = 'preview'"
                >Open live preview</Button
              >
            </div>
            <div class="grid gap-4 border-b p-5">
              <h3 class="font-semibold">Appearance</h3>
              <StudioAppearance
                v-model:theme="form.theme_id"
                v-model:palette="form.palette"
                v-model:headline="form.headline_style"
                :classic="form.layout === 'classic.v1'"
                :palettes="catalog.palettes"
                :themes="themes"
                :errors="form.errors"
              />
              <details
                class="group rounded-md border"
                :open="
                  assets.some(
                    (asset) => form.errors[asset.key + '_image_id'],
                  ) || undefined
                "
              >
                <summary
                  class="flex cursor-pointer list-none items-center gap-2 rounded-md px-3 py-2 text-sm font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring [&::-webkit-details-marker]:hidden"
                >
                  <Image
                    class="size-4 text-muted-foreground"
                    aria-hidden="true"
                  />Brand images<ChevronDown
                    class="ml-auto size-4 group-open:rotate-180"
                    aria-hidden="true"
                  />
                </summary>
                <div class="grid gap-4 border-t p-3">
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
              </details>
            </div>
            <div class="grid gap-3 border-b p-5">
              <h3 class="font-semibold">Shipment sections</h3>
              <StudioSections
                v-if="selectedLayout"
                v-model:orders="form.section_orders"
                v-model:features-enabled="form.sections"
                :layout="selectedLayout"
                :modules="catalog.modules"
                :features="sections"
                :error="sectionError"
              />
            </div>
            <p
              v-if="form.errors.base_version"
              role="alert"
              class="border-t p-5 text-sm text-destructive"
            >
              {{ form.errors.base_version }}
              <a
                :href="route('admin.drafts.show', [company.uuid, draft.uuid])"
                class="underline"
                >Reopen draft</a
              >
            </p>
          </form>

          <section
            v-if="revisions.length"
            class="rounded-xl border bg-card p-5"
          >
            <h2 class="mb-2 flex items-center gap-2 font-semibold">
              <History class="size-4" aria-hidden="true" />Saved revisions
            </h2>
            <ul class="divide-y">
              <li
                v-for="item in revisions"
                :key="item.version"
                class="flex flex-wrap items-baseline justify-between gap-2 py-2"
              >
                <Link
                  class="text-sm font-medium underline underline-offset-4"
                  :href="preview(item.version)"
                  >Preview revision {{ item.version }}</Link
                >
                <time
                  class="text-xs text-muted-foreground"
                  :datetime="item.created_at"
                  >{{ new Date(item.created_at).toLocaleString() }}</time
                >
              </li>
            </ul>
          </section>
        </div>

        <div
          class="min-w-0 lg:sticky lg:top-4 lg:block"
          :class="mobilePane === 'preview' ? 'block' : 'hidden'"
        >
          <StudioShipmentPreview :company="company" :form="form" />
        </div>
      </div>
    </AuthenticatedLayout>
  </div>
</template>
