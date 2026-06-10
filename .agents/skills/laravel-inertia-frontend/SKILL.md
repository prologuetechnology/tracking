---
name: laravel-inertia-frontend
description: Use when building or refactoring a Laravel Inertia frontend in React or Vue that should follow a server-seeded, TanStack Query or Vue Query-enhanced architecture. Applies to Inertia pages, layouts, feature components, query and mutation composables, frontend routing with Ziggy, Tailwind CSS v4 design tokens, shadcn-style primitives, and app shell patterns.
---

# Laravel Inertia Frontend

Use this skill to bring a Laravel + Inertia frontend up to a disciplined, scalable app architecture. The default patterns work for React and Vue; adapt syntax to the selected client while keeping the same boundaries.

## Companion Rules

Use this in tandem with Laravel Boost and Laravel best-practice rules:

- Read application metadata with Boost before assuming package versions.
- Use Boost `search-docs` for Laravel, Inertia, Tailwind, Pest, and installed frontend packages before changing framework APIs.
- Use Boost `get-absolute-url` before sharing local app URLs.
- Use Boost `browser-logs` when debugging frontend runtime errors.
- Follow the project's Laravel, Inertia, Tailwind, and testing skills when they exist.

## First Checks

Before editing, inspect:

- `resources/js/app.js`
- `resources/js/Pages/**`
- `resources/js/components/**`
- `resources/js/composables/**`
- `resources/js/lib/**`
- `resources/css/app.css`
- `vite.config.js`, `eslint.config.js`, `prettier.config.*`, `components.json`, `jsconfig.json` or `tsconfig.json`

Use version-specific docs for Inertia, the selected client adapter, TanStack Query/Vue Query, Tailwind, and Laravel before changing framework APIs.

## Architecture Target

Organize frontend code by responsibility:

```text
resources/js/
  app.js
  Pages/
    domain/
      Index.jsx|vue
      Show.jsx|vue
  components/
    ui/
    layout/
    feature/
      domain/
        DomainPanel.jsx|vue
        DomainDialog.jsx|vue
        index.js
  composables/
    queries/
      domain/
        useDomainItemsQuery.js
        index.js
    mutations/
      domain/
        useCreateDomainItemMutation.js
        index.js
    hooks/          # React-specific hooks, if React is used
    forms/
    helpers/
  lib/
```

Keep pages as route-level composition. Move reusable cards, panels, dialogs, rows, controls, and feature-specific helpers to `components/feature/<domain>`.

## Adapter Rule

Choose React or Vue early and keep file and import conventions consistent:

- React pages/components use `.jsx` or `.tsx`, React hooks, and `@inertiajs/react`.
- Vue pages/components use `.vue`, `<script setup>`, Vue composables, and `@inertiajs/vue3`.
- Shared folders and boundaries stay the same across both clients: pages compose, feature components render product UI, query/mutation composables own API traffic, and `lib` owns framework-light helpers.

## Inertia App Setup

Mount these providers at the Inertia root where applicable:

- TanStack Query provider for the selected adapter
- theme provider when dark mode exists
- global toasts/devtools as appropriate

Use conservative query defaults:

React example:

```js
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000,
      gcTime: 10 * 60 * 1000,
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
})
```

Vue apps should use the equivalent TanStack Vue Query plugin/provider with the same defaults.

Resolve pages with `import.meta.glob` and fail loudly if a route points at a missing page.

## Server-Seeded First Render

Core pages that depend on remote data must receive initial props from Laravel. Avoid blank first paint followed by client fetching when the server can provide the first dataset.

Backend pattern:

```php
return Inertia::render('domain/Index', [
    'domainPage' => [
        'initial_items' => DomainResource::collection($items)->resolve(),
        'filters' => $filters,
    ],
]);
```

Frontend pattern:

```js
const { domainPage = {} } = usePage().props

const itemsQuery = useDomainItemsQuery({
  filters: domainPage.filters,
  config: {
    initialData: domainPage.initial_items,
    initialDataUpdatedAt: 0,
  },
})
```

For Vue, use `usePage()` from `@inertiajs/vue3` and pass the same initial payload into the domain query composable.

Only show skeletons for genuinely deferred, optional, or filter-changed data.

## Query Composables

Create one query composable per file. React example:

```js
import { useQuery } from '@tanstack/react-query'
import axios from 'axios'
import useCurrentWorkspaceId from '@/composables/queries/useCurrentWorkspaceId'

export const domainItemsQueryKey = function (workspaceId, filters = {}) {
  return ['domain-items', workspaceId, filters]
}

const fetchDomainItems = async function (filters) {
  const { data } = await axios.get(route('api.auth.domain-items.index'), {
    params: filters,
  })

  return data.data ?? data
}

export default function useDomainItemsQuery({ filters = {}, config = {} } = {}) {
  const workspaceId = useCurrentWorkspaceId()

  return useQuery({
    queryKey: domainItemsQueryKey(workspaceId, filters),
    queryFn: function () {
      return fetchDomainItems(filters)
    },
    ...config,
  })
}
```

Vue uses the same file-level shape with `@tanstack/vue-query`; only the reactive wrappers differ.

Rules:

- Use array query keys with a stable domain prefix.
- Include workspace, tenant, user, filter, search, and pagination identity when the response depends on it.
- Normalize filter objects before using them in query keys.
- Export query key factories from the composable file.
- Re-export composables and key factories from an adjacent `index.js`.
- Keep network calls in query/mutation composables, not page components.

## Mutation Composables

Create one mutation composable per write action. The composable owns API calls, cache invalidation, optimistic updates when useful, and analytics or toast side effects.

```js
import { useMutation, useQueryClient } from '@tanstack/react-query'
import axios from 'axios'
import { domainItemsBaseQueryKey } from '@/composables/queries/domain'

const createDomainItem = async function (payload) {
  const { data } = await axios.post(route('api.auth.domain-items.store'), payload)

  return data.data ?? data
}

export default function useCreateDomainItemMutation({ config = {} } = {}) {
  const queryClient = useQueryClient()
  const userOnSuccess = config?.onSuccess
  const { onSuccess: _onSuccess, ...mutationConfig } = config

  return useMutation({
    mutationFn: createDomainItem,
    onSuccess: async function (data, variables, context) {
      await queryClient.invalidateQueries({ queryKey: domainItemsBaseQueryKey })
      userOnSuccess?.(data, variables, context)
    },
    ...mutationConfig,
  })
}
```

Prefer narrow invalidation. Use `setQueryData` for low-risk preference toggles or local badge updates, then invalidate on settle.

## Toast Wrapper

For common command-like mutations, wrap `mutateAsync` so components do not repeat try/catch and toast plumbing:

```js
const result = await createItem.run(payload, {
  success: 'Saved',
  error: 'Could not save',
})

if (result.ok) {
  closeDialog()
}
```

The wrapper should return `{ ok: true, data }` or `{ ok: false, error }`. Keep the calling page free of repeated try/catch plumbing.

## Routing

Use Ziggy named routes rather than hardcoded URLs.

```js
export const appRoute = function (name, params = {}, absolute = true) {
  if (typeof window.route !== 'function') {
    throw new Error('Ziggy route helper is not available')
  }

  return window.route(name, params, absolute)
}
```

Use Inertia `Link` for internal navigation. Use native `<a>` only for external URLs, downloads, mail links, or auth/provider handoffs that require a full-page request.

## Forms

Use this split:

- Inertia `useForm` or `<Form>` for classic page submit/redirect flows.
- client state plus TanStack mutation composables for app-like API interactions.
- Shared field primitives for labels, descriptions, errors, and layout.

Labels must be connected to controls: `htmlFor`/`id` in React, or `for`/`id` in Vue.

## Client Side Effects

Do not scatter low-level lifecycle effects. Prefer:

- derived state from props/query results
- event handlers
- TanStack Query
- small named React hooks such as `useMountEffect`, `useGlobalKeydown`, and `usePersistentState`
- small named Vue composables wrapping `onMounted`, `watch`, and browser listeners

If direct React `useEffect` or broad Vue watchers are banned by lint/convention, only allow them inside dedicated helper composables.

## Layouts

Keep authenticated and public layouts separate.

Authenticated layout owns:

- app sidebar/header
- command dialog
- notifications/inbox surfaces
- flash-to-toast handling
- theme/toast/tooltip providers
- page title, description, width, docked action bars

Public layout owns:

- public navigation/footer
- SEO-friendly structure
- public surface tokens
- marketing/public-tool chrome

Pages pass layout props; they do not rebuild shell chrome.

## Tailwind CSS v4

Use CSS-first Tailwind:

```css
@import 'tailwindcss';
@plugin "@tailwindcss/typography";
@custom-variant dark (&:is(.dark *));
@source "../js/**/*.{js,jsx}";
```

Put design decisions in CSS variables:

- font families
- type scale
- spacing rhythm
- motion durations/easing
- elevation
- semantic colors
- shell/public surface tokens

Use `cn()` from `clsx` + `tailwind-merge` for component classes.

## Build And Lint

Keep Vite aliases aligned with `jsconfig.json`.

Use manual chunks only for real boundaries:

- core vendor
- heavy UI packages
- charts
- syntax highlighters
- PDF/export modules

ESLint should enforce the selected client rules, unused imports, Prettier, and any project-specific architecture bans such as no direct `useEffect` or unwrapped broad watchers.

## Avoid

- API calls directly in page components.
- Giant global barrels.
- Query keys without tenant/filter identity.
- Inline ad hoc route strings.
- UI copy or state that assumes backend authorization will agree.
- Duplicating primitives already available under `components/ui`.
- Letting route pages absorb every panel, dialog, and row component.
