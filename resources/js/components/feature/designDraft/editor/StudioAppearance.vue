<script setup>
import { Check } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import DraftSelect from '@/components/feature/designDraft/DraftSelect.vue'
import { Button } from '@/components/ui/button'
import { useAuthorization } from '@/composables/hooks/auth'

import StudioColorFields from './StudioColorFields.vue'
import StudioThemeAuthor from './StudioThemeAuthor.vue'

const props = defineProps({
  liveDesign: { type: Object, required: true },
  classic: { type: Boolean, required: true },
  palettes: { type: Array, required: true },
  themes: { type: Array, required: true },
  errors: { type: Object, required: true },
})
const emit = defineEmits([`library-saved`])
const { can } = useAuthorization()
const authorMode = ref(null)
const notice = ref(``)
function savedVersion(saved) {
  emit(`library-saved`, saved)
  candidateTheme.value = saved.id
  candidateVersion.value = saved.theme_version_id
  notice.value = saved.name + ` v` + saved.version + ` saved to the library.`
  authorMode.value = null
}
// Vue's compiler requires string literals for named models.
/* eslint-disable quotes */
const colors = defineModel('colors', { type: Object, required: true })
const palette = defineModel('palette', { type: String, required: true })
const theme = defineModel('theme', { type: [String, Number], default: null })
const themeVersion = defineModel('themeVersion', {
  type: [String, Number],
  default: null,
})
const headline = defineModel('headline', { type: String, required: true })
/* eslint-enable quotes */
const candidateTheme = ref(null)
const candidateVersion = ref(null)
const libraryTheme = computed(() =>
  props.themes.find((t) => String(t.id) === String(candidateTheme.value)),
)
const versionChoices = computed(() =>
  (libraryTheme.value?.versions ?? []).map((v) => ({
    ...v,
    name: `v${v.version} · ${v.name}`,
  })),
)
const selectedVersion = computed(() =>
  versionChoices.value.find(
    (v) => String(v.id) === String(candidateVersion.value),
  ),
)
const appliedTheme = computed(() =>
  props.themes.find((t) => String(t.id) === String(theme.value)),
)
const appliedVersion = computed(() =>
  appliedTheme.value?.versions?.find(
    (v) => String(v.id) === String(themeVersion.value),
  ),
)
const canApply = computed(
  () =>
    String(candidateTheme.value) !== String(theme.value) ||
    String(candidateVersion.value) !== String(themeVersion.value),
)
function chooseTheme(id) {
  candidateTheme.value = id
  candidateVersion.value = libraryTheme.value?.versions?.[0]?.id ?? null
}
watch(
  [theme, themeVersion],
  () => {
    chooseTheme(theme.value)
    if (themeVersion.value) candidateVersion.value = themeVersion.value
  },
  { immediate: true },
)
function apply() {
  theme.value = candidateTheme.value
  themeVersion.value = candidateVersion.value
  palette.value = `company`
  if (selectedVersion.value?.style)
    headline.value = selectedVersion.value.style.headline
}
</script>
<template>
  <div class="grid gap-4">
    <fieldset v-if="!classic" class="min-w-0 space-y-2">
      <legend class="text-sm font-medium">Color palette</legend>
      <div class="flex flex-wrap gap-2">
        <label
          v-for="item in palettes"
          :key="item.id"
          :dusk="'palette-' + item.id"
          class="cursor-pointer"
        >
          <input
            v-model="palette"
            class="peer sr-only"
            type="radio"
            name="palette"
            :value="item.id"
          />
          <span
            class="inline-flex min-h-9 items-center gap-2 rounded-md border px-2.5 text-xs font-semibold hover:bg-muted peer-checked:border-primary peer-checked:bg-primary/5 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ring"
          >
            <span
              aria-hidden="true"
              class="size-3.5 rounded-full border border-foreground/15"
              :style="{
                background:
                  item.color ||
                  'linear-gradient(90deg, currentColor 50%, transparent 50%)',
              }"
            />
            {{ item.name
            }}<Check
              v-if="palette === item.id"
              class="size-3.5"
              aria-hidden="true"
            />
          </span>
        </label>
      </div>
      <p v-if="errors.palette" role="alert" class="text-sm text-destructive">
        {{ errors.palette }}
      </p>
    </fieldset>
    <StudioColorFields
      v-if="!classic && palette === 'custom'"
      v-model="colors"
      :errors="errors"
    />
    <section
      v-if="classic || palette === 'company'"
      class="grid gap-3 rounded-md border p-3"
    >
      <h4 class="text-sm font-semibold">Reusable style</h4>
      <div class="text-xs text-muted-foreground">
        <p>
          Live page · {{ liveDesign.themeName ?? 'Layout colors'
          }}<span v-if="liveDesign.themeVersion">
            v{{ liveDesign.themeVersion }}</span
          ><span v-else-if="liveDesign.themeId"> · Saved appearance</span>
        </p>
        <p dusk="draft-style-applied">
          Proposal ·
          {{ appliedVersion?.name ?? appliedTheme?.name ?? 'Default colors'
          }}<span v-if="appliedVersion"> v{{ appliedVersion.version }}</span
          ><span v-else-if="appliedTheme"> · Saved appearance</span>
        </p>
        <p
          v-if="
            appliedVersion &&
            appliedTheme.versions[0].version > appliedVersion.version
          "
        >
          v{{ appliedTheme.versions[0].version }} available in the library.
        </p>
      </div>
      <DraftSelect
        id="draft-theme"
        :model-value="candidateTheme"
        label="Library theme"
        empty-label="Default colors"
        :choices="themes"
        :error="errors.theme_id"
        @update:model-value="chooseTheme"
      />
      <DraftSelect
        v-if="versionChoices.length"
        id="draft-theme-version"
        v-model="candidateVersion"
        label="Version"
        :choices="versionChoices"
        :error="errors.theme_version_id"
      />
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="can('theme:update') && libraryTheme?.versions?.length"
          type="button"
          size="sm"
          variant="outline"
          @click="authorMode = 'edit'"
          >Edit library version</Button
        >
        <Button
          v-if="can('theme:store')"
          type="button"
          size="sm"
          variant="outline"
          @click="authorMode = 'create'"
          >Create library theme</Button
        >
      </div>
      <p v-if="notice" role="status" class="text-xs text-muted-foreground">
        {{ notice }}
      </p>
      <StudioThemeAuthor
        v-if="authorMode"
        :theme-id="authorMode === 'edit' ? candidateTheme : null"
        :version-id="authorMode === 'edit' ? candidateVersion : null"
        @close="authorMode = null"
        @saved="savedVersion"
      />
      <Button
        type="button"
        size="sm"
        class="w-fit"
        :disabled="!canApply"
        @click="apply"
        >Use in draft</Button
      >
    </section>
    <DraftSelect
      v-if="!classic"
      id="draft-headline"
      v-model="headline"
      label="Headline style"
      :choices="[
        { id: 'layout', name: 'Layout default' },
        { id: 'sans', name: 'Modern sans' },
        { id: 'serif', name: 'Editorial serif' },
      ]"
      :error="errors.headline_style"
    />
  </div>
</template>
