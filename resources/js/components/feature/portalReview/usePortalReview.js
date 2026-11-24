import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { reviewArchive } from './reviewArchive'
import { clone, previewInput, styles } from './reviewStyles'

export function usePortalReview(owner, permissions) {
  const section = ref(`lookup`),
    page = ref(`lookup`),
    id = ref(null)
  const archive = ref({ themes: clone(styles), revisions: [] }),
    loaded = ref(false),
    busy = ref(false),
    notice = ref(``),
    error = ref(``)
  const draft = ref(clone(styles[0])),
    working = ref(clone(styles[0])),
    banner = ref(`jayco-overlook`)
  const appliedVersion = computed(() =>
    archive.value.themes.find(
      (t) => t.key === working.value.key && t.version === working.value.version,
    ),
  )
  const dirty = computed(
    () =>
      JSON.stringify(working.value) !== JSON.stringify(appliedVersion.value),
  )
  const latest = computed(() =>
    archive.value.themes.filter(
      (t) =>
        !archive.value.themes.some(
          (other) => other.key === t.key && other.version > t.version,
        ),
    ),
  )
  const revision = computed(() =>
    archive.value.revisions.find((r) => r.id === id.value),
  )
  const history = computed(() => [...archive.value.revisions].reverse())
  const navAllowed = (screen) =>
    ({
      lookup: true,
      companies: permissions.company,
      company: permissions.company,
      proposal: permissions.design,
      editor: permissions.themes,
      proof: permissions.design,
      themes: permissions.themes,
      assets: permissions.assets,
      access: permissions.access,
    })[screen]
  function locationChanged() {
    const [screen, refId] = location.hash.slice(1).split(`/`)
    page.value = navAllowed(screen) ? screen : `lookup`
    if (
      page.value === `proof` &&
      !archive.value.revisions.some((r) => r.id === refId)
    )
      page.value = `company`
    id.value = refId
    section.value =
      {
        company: `companies`,
        proposal: `companies`,
        proof: `companies`,
        editor: `themes`,
      }[page.value] ?? page.value
  }
  function go(screen, value) {
    if (!navAllowed(screen)) return
    location.hash = screen + (value ? `/` + value : ``)
    locationChanged()
    window.scrollTo(0, 0)
  }
  function edit(theme, screen = `editor`) {
    working.value = clone(theme)
    notice.value = ``
    go(screen)
  }
  function proposal() {
    edit(draft.value, `proposal`)
  }
  async function mutate(callback, done) {
    if (busy.value || !loaded.value) return
    busy.value = true
    error.value = ``
    try {
      archive.value = await reviewArchive(owner, callback)
      done?.()
    } catch (e) {
      error.value = e.message || `Could not save this review. Try again.`
    } finally {
      busy.value = false
    }
  }
  async function saveTheme() {
    const value = clone(working.value)
    await mutate(
      (state) => {
        value.version =
          Math.max(
            ...state.themes
              .filter((t) => t.key === value.key)
              .map((t) => t.version),
          ) + 1
        state.themes.push(value)
      },
      () => {
        working.value = clone(value)
        notice.value =
          value.name + ` version ` + value.version + ` saved to the library.`
      },
    )
  }
  function apply() {
    if (dirty.value) return
    draft.value = clone(working.value)
    notice.value =
      draft.value.name +
      ` version ` +
      draft.value.version +
      ` applied to the Jayco proposal.`
    go(`proposal`)
  }
  async function saveRevision() {
    const style = clone(draft.value),
      selectedBanner = banner.value,
      id = crypto.randomUUID()
    await mutate(
      (state) => {
        state.revisions.push({
          id,
          number: state.revisions.length + 1,
          style,
          banner: selectedBanner,
          views: null,
          approval: null,
        })
      },
      () => {
        notice.value = `Saved a private Jayco revision.`
        go(`proof`, id)
      },
    )
  }
  async function exportRevision() {
    const saved = clone(revision.value)
    if (busy.value || saved.views) return
    busy.value = true
    error.value = ``
    try {
      const { data } = await axios.post(
        route(`admin.portal-review.proofs`),
        previewInput(saved.style, saved.banner),
      )
      archive.value = await reviewArchive(owner, (state) => {
        const entry = state.revisions.find((r) => r.id === saved.id)
        if (!entry.views) entry.views = data.views
      })
      notice.value =
        `Both PNGs are archived with revision ` + saved.number + `.`
    } catch {
      error.value = `The two proofs could not be saved. Reopen this revision and retry export.`
    } finally {
      busy.value = false
    }
  }
  function approve(name) {
    const saved = id.value
    return mutate(
      (state) => {
        const entry = state.revisions.find((r) => r.id === saved)
        if (entry?.views && !entry.approval) entry.approval = name
      },
      () => {
        notice.value = `Approval recorded in this local review.`
      },
    )
  }
  onMounted(async () => {
    try {
      archive.value = await reviewArchive(owner)
      loaded.value = true
      const last = archive.value.revisions.at(-1)
      if (last) {
        draft.value = clone(last.style)
        working.value = clone(last.style)
        banner.value = last.banner
      }
    } catch {
      error.value = `Browser storage is unavailable. Allow site storage to save versions and proofs.`
    }
    locationChanged()
    window.addEventListener(`hashchange`, locationChanged)
  })
  onBeforeUnmount(() =>
    window.removeEventListener(`hashchange`, locationChanged),
  )
  return {
    section,
    page,
    archive,
    loaded,
    busy,
    notice,
    error,
    draft,
    working,
    banner,
    latest,
    revision,
    history,
    dirty,
    go,
    edit,
    proposal,
    saveTheme,
    apply,
    saveRevision,
    exportRevision,
    approve,
  }
}
