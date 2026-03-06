# React Frontend Structure Contract

Last updated: 2026-02-08  
Scope: React migration branch `codex/react-migration`

## Purpose
- Lock down frontend structure before migration work.
- Keep imports explicit and discoverable.
- Preserve one-intent-per-file and adjacent index re-exports.

## Directory Blueprint

```text
resources/js/
  app.jsx
  bootstrap.js
  pages/
    auth/
      Login.jsx
    account/
      Emails.jsx
    snippets/
      Index.jsx
      Show.jsx
      Shared.jsx
    tools/
      Index.jsx
      Base64.jsx
      ...
  components/
    ui/
      button/
        Button.jsx
        index.js
      card/
        Card.jsx
        CardHeader.jsx
        CardContent.jsx
        index.js
      ...
    layout/
      AppLayout.jsx
      index.js
    feature/
      snippets/
        SnippetCard.jsx
        SnippetShareManager.jsx
        index.js
      account/
        UserEmailList.jsx
        index.js
      tools/
        HistoryList.jsx
        index.js
  composables/
    queries/
      snippets/
        useSnippetsQuery.js
        useSnippetQuery.js
        useSnippetSharesQuery.js
        index.js
      account/
        useUserEmailsQuery.js
        index.js
      tools/
        useToolHistoryQuery.js
        index.js
    mutations/
      snippets/
        useCreateSnippetMutation.js
        useDeleteSnippetMutation.js
        useCreateSnippetShareMutation.js
        useRevokeSnippetShareMutation.js
        useResolveSnippetShareMutation.js
        index.js
      account/
        useAddUserEmailMutation.js
        useVerifyUserEmailMutation.js
        useResendUserEmailVerificationMutation.js
        useRemoveUserEmailMutation.js
        index.js
      tools/
        usePhpUnserializeMutation.js
        ...
        index.js
    hooks/
      auth/
        useCurrentUser.js
        index.js
      billing/
        useCurrentTier.js
        index.js
    forms/
      snippets/
        useCreateSnippetForm.js
        useCreateSnippetShareForm.js
        index.js
      account/
        useAddUserEmailForm.js
        index.js
    helpers/
      strings/
        normalizeEmail.js
        index.js
      dates/
        toLocalDisplay.js
        index.js
  lib/
    tool-access.js
    cn.js
    utils.js
```

## Naming Rules
- Query hooks: `useXQuery.js`
- Mutation hooks: `useYMutation.js`
- Generic hooks: `useZHookName.js` or domain-specific `useCurrentTier.js`
- Form hooks: `useAForm.js`
- Components: `PascalCase.jsx`
- One intent per file.

## Re-export Rules
- Every leaf folder has an adjacent `index.js`.
- Re-export defaults with aliases, example:

```js
export { default as useSnippetsQuery } from './useSnippetsQuery'
export { default as useSnippetQuery } from './useSnippetQuery'
```

```js
export { default as SnippetShareManager } from './SnippetShareManager'
export { default as SnippetCard } from './SnippetCard'
```

## Import Rules
- Prefer domain-scoped imports over global barrels.
- Good:

```js
import {
  useSnippetsQuery,
  useSnippetQuery,
} from '@/composables/queries/snippets'
```

```js
import {
  useCreateSnippetShareMutation,
  useResolveSnippetShareMutation,
} from '@/composables/mutations/snippets'
```

```js
import { SnippetShareManager } from '@/components/feature/snippets'
```

- Avoid:

```js
import { useSnippetsQuery } from '@/composables/queries'
import { SnippetShareManager } from '@/components/feature'
```

## API Call Placement
- Keep network calls inside query/mutation hooks for discoverability.
- Shared transforms/parsers can live in `composables/helpers/**` or `lib/**`.
- Do not introduce a separate mandatory `lib/api/**` abstraction layer unless needed later.

## TanStack Query Convention
- React hooks should mirror current Vue behavior:
  - same query keys
  - same invalidation strategy
  - same route name usage via Ziggy
- Primary package change is:
  - from `@tanstack/vue-query`
  - to `@tanstack/react-query`

## UI Library Convention
- React migration uses official React shadcn CLI:
  - `npx shadcn@latest add <component>`
- Do not use `shadcn-vue` CLI for React files.

