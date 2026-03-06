# Vue Frontend Structure Contract

This repository is the Vue adaptation of the transfer-kit frontend structure.

## Core Rules

- Keep one concern per file.
- Keep network access inside Vue Query composables.
- Keep page files small and server-prop driven.
- Prefer local adjacent `index.js` barrels only.
- Use Ziggy for internal route generation.

## Vue Translation Of Source Patterns

- React Query hooks become Vue Query composables in
  `resources/js/composables/queries/**` and
  `resources/js/composables/mutations/**`.
- React components become Vue SFCs using `<script setup>`.
- React layout wrappers become Vue layout components using slots and small prop
  contracts.
- Shared auth state comes from Inertia props plus query-backed refreshes where
  needed.

## First Render Hydration

- Read initial props with `usePage().props`.
- Pass those values into the matching Vue Query composable with
  `config.initialData`.
- Preserve the same response shape between initial props and the follow-up API
  query where possible.
- Tracking pages consume a normalized `trackingData` shipment object. Do not
  depend on raw external envelopes such as `trackingData.data[0]` in Vue code.
- Image admin consumes flat image resources with a nested `image_type` object.
  Do not switch between raw relations and resource-wrapped payloads in Vue.

## Authorization

- Use a shared auth helper composable for role/permission checks.
- Do not inspect `usePage().props.auth` ad hoc across components when a shared
  helper can answer the same question.
- Keep dialog-level uploads and deletes responsible only for mutations and
  query invalidation. The page controller remains the source of first render
  data, and filtered table reads stay inside Vue Query composables.

## Folder Contract

- Pages:
  - `resources/js/pages/admin/**`
  - `resources/js/pages/auth/**`
  - `resources/js/pages/brandedTracking/**`
- Feature components:
  - `resources/js/components/feature/**`
- Layout components:
  - `resources/js/components/layout/**`

## URL And Navigation Rules

- Internal app links use Inertia `<Link>` or programmatic `router.visit(...)`.
- External URLs and protocol handoffs may use native anchors.
- OAuth redirect handoffs may use full page redirects.
