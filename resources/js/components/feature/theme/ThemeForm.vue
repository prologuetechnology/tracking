<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { computed, ref, watch } from 'vue'

import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { SelectField } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import {
  useThemeCreateMutation,
  useThemeUpdateMutation,
} from '@/composables/mutations/theme'

import { tokenHex } from './themeColorFields'
import ThemeDestroyDialog from './ThemeDestroyDialog.vue'
import ThemeStyleControls from './ThemeStyleControls.vue'
import ThemeStyleSample from './ThemeStyleSample.vue'
import { useThemeStylePreview } from './useThemeStylePreview'

const props = defineProps({
  theme: { type: Object, default: null },
  embedded: Boolean,
  initialVersionId: { type: [String, Number], default: null },
})
const emit = defineEmits([`saved`, `cancel`, `reload`])
const savedNotice = ref(``)
const { userCan } = useRolesAndPermissions()
const canWrite = computed(() =>
  userCan(props.theme ? `theme:update` : `theme:store`),
)
const cache = useQueryClient()
const source = ref(null)
const baseVersion = ref(null)
const name = ref(``)
const style = ref(null)
const original = ref(``)
const errors = ref({})
const errorMessage = ref(``)
const versions = computed(() => props.theme?.versions ?? [])
const selectedId = ref(null)

function select(version) {
  source.value = version
  selectedId.value = version?.id ?? null
  baseVersion.value = props.theme?.version ?? null
  name.value = props.theme?.name ?? ``
  const root = version?.colors?.root ?? {}
  const radius = parseFloat(version?.radius)
  const value = version?.style ?? {
    colors: {
      brand: tokenHex(root.brand ?? root.primary) ?? `#185E86`,
      accent: tokenHex(root.accent) ?? `#B7D9E8`,
      paper: tokenHex(root.background) ?? `#F5F8FA`,
    },
    headline: `layout`,
    corners: radius === 0 ? `square` : radius >= 1 ? `round` : `soft`,
    overrides: {},
  }
  // Empty PHP arrays are serialized as JSON arrays; the editor adds named token keys.
  style.value = {
    ...value,
    colors: { ...value.colors },
    overrides: { ...value.overrides },
  }
  original.value = JSON.stringify(style.value)
  errors.value = {}
  errorMessage.value = ``
}
watch(
  () => props.theme?.uuid,
  () =>
    select(
      versions.value.find(
        (v) => String(v.id) === String(props.initialVersionId),
      ) ??
        versions.value[0] ??
        props.theme,
    ),
  { immediate: true },
)
const sameStyle = computed(() => JSON.stringify(style.value) === original.value)
const savedAppearance = computed(() =>
  sameStyle.value && source.value?.colors
    ? { colors: source.value.colors, radius: source.value.radius ?? `0.5rem` }
    : null,
)
const {
  appearance,
  derived,
  error: previewError,
  loading,
  retry,
} = useThemeStylePreview(style, savedAppearance)
const dirty = computed(
  () =>
    !!name.value.trim() &&
    (!props.theme ||
      name.value.trim() !== props.theme.name ||
      !sameStyle.value ||
      selectedId.value !== props.theme.theme_version_id),
)
const create = useThemeCreateMutation()
const update = useThemeUpdateMutation()
const saving = computed(() => create.isPending.value || update.isPending.value)
function changeVersion(id) {
  select(versions.value.find((v) => String(v.id) === id))
}
async function save() {
  if (!canWrite.value || !dirty.value || saving.value) return
  errors.value = {}
  errorMessage.value = ``
  const formData = { name: name.value.trim() }
  if (props.theme) formData.base_version = baseVersion.value
  if (source.value?.id && sameStyle.value)
    formData.source_version_id = source.value.id
  else formData.style = style.value
  let saved
  try {
    saved = props.theme
      ? await update.mutateAsync({ id: props.theme.id, formData })
      : await create.mutateAsync({ formData })
  } catch (e) {
    errors.value = e.response?.data?.errors ?? {}
    errorMessage.value =
      Object.values(errors.value).flat()[0] ??
      `Could not save this version. Try again.`
    return
  }
  cache.setQueryData([`themes`, saved.id], saved)
  await cache.invalidateQueries({ queryKey: [`themes`] })
  if (props.embedded) emit(`saved`, saved)
  else if (userCan(`theme:show`))
    router.visit(route(`admin.studio.styles.index`))
  else {
    select(null)
    savedNotice.value = saved.name + ` saved to the library.`
  }
}
function reload() {
  if (props.embedded) emit(`reload`)
  else window.location.reload()
}
</script>

<template>
  <div class="space-y-5">
    <SelectField
      v-if="versions.length"
      id="library-history"
      label="Version to inspect"
      class="max-w-sm"
      :model-value="selectedId"
      :choices="
        versions.map((v) => ({
          id: v.id,
          name:
            'v' +
            v.version +
            ' · ' +
            v.name +
            (v.version === theme.version ? ' (latest)' : ''),
        }))
      "
      :disabled="saving"
      @update:model-value="changeVersion"
    />
    <form
      id="themeForm"
      dusk="theme-form"
      class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
      @submit.prevent="save"
    >
      <Card as="section" class="grid gap-5 p-5 sm:p-6">
        <div class="grid gap-1">
          <Label for="theme-name">Style name</Label>
          <Input
            id="theme-name"
            v-model="name"
            name="theme_name"
            dusk="theme-name"
            maxlength="255"
            required
            :readonly="!canWrite"
          />
        </div>
        <ThemeStyleControls
          v-model="style"
          :derived="derived"
          :readonly="!canWrite"
        />
        <p v-if="errorMessage" role="alert" class="text-sm text-destructive">
          {{ errorMessage }}
          <Button
            v-if="errors.base_version"
            variant="link"
            size="sm"
            type="button"
            class="font-medium underline"
            @click="reload"
          >
            Reload version history
          </Button>
        </p>
        <div class="flex flex-wrap gap-3">
          <Button
            v-if="canWrite"
            type="submit"
            dusk="theme-form-save"
            :disabled="!dirty || saving"
            >{{ theme ? 'Save new version' : 'Create style' }}</Button
          >
          <Button
            v-if="embedded"
            type="button"
            variant="outline"
            @click="emit('cancel')"
            >Cancel</Button
          >
          <Link
            v-else-if="userCan('theme:show')"
            class="self-center text-sm font-medium underline underline-offset-4"
            :href="route('admin.studio.styles.index')"
            >Back to style library</Link
          >
          <p v-if="savedNotice" role="status">{{ savedNotice }}</p>
        </div>
      </Card>
      <aside
        class="grid gap-4"
        aria-label="Style inspector"
        :aria-busy="loading"
      >
        <ThemeStyleSample
          v-if="appearance"
          :colors="appearance.colors.root"
          :radius="appearance.radius"
          :headline="style.headline"
        />
        <p v-if="previewError" role="alert" class="text-sm text-destructive">
          The style preview could not be updated.
          <Button
            variant="link"
            size="sm"
            type="button"
            class="font-semibold underline"
            @click="retry"
          >
            Retry
          </Button>
        </p>
        <section v-if="theme" class="space-y-2 border-t pt-4">
          <h2 class="font-semibold">Where this style is used</h2>
          <p v-if="!theme.usage?.length" class="text-sm text-muted-foreground">
            No visible assignments.
          </p>
          <ul v-else class="divide-y text-sm">
            <li
              v-for="entry in theme.usage"
              :key="
                entry.kind +
                '-' +
                entry.company_uuid +
                '-' +
                (entry.draft_uuid ?? '')
              "
              class="flex flex-wrap items-center justify-between gap-2 py-2"
            >
              <span
                >{{ entry.company_name
                }}<span v-if="entry.draft_name">
                  · {{ entry.draft_name }}</span
                ></span
              >
              <span
                >{{ entry.kind === 'live' ? 'Live' : 'Draft' }} ·
                {{
                  entry.version ? 'v' + entry.version : 'Saved appearance'
                }}</span
              >
            </li>
          </ul>
        </section>
      </aside>
    </form>
    <ThemeDestroyDialog v-if="theme && !embedded" :theme="theme" />
  </div>
</template>
