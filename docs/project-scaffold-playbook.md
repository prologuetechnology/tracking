# Project Scaffold Playbook

This file explains where work should land in this repository.

## Backend Layout

- `app/Http/Controllers/Pages/**`
  - page controllers for Inertia-rendered surfaces
- `app/Http/Controllers/Api/**`
  - thin JSON controllers
- `app/Http/Requests/**`
  - validation and authorization
- `app/Http/Resources/**`
  - API response shaping
- `app/Actions/**`
  - domain workflows
- `app/Services/**`
  - reusable business logic and third-party integrations
- `app/Models/**`
  - Eloquent models

## Frontend Layout

- `resources/js/pages/**`
  - page entry points only
- `resources/js/components/layout/**`
  - shared layout shells
- `resources/js/components/feature/**`
  - domain UI
- `resources/js/components/ui/**`
  - shared shadcn-vue primitives
- `resources/js/composables/queries/**`
  - one query per file
- `resources/js/composables/mutations/**`
  - one mutation per file
- `resources/js/composables/hooks/**`
  - local cross-cutting Vue helpers
- `resources/js/lib/**`
  - shared helpers and types

## Page Pattern

1. Route points to a page controller.
2. Page controller authorizes and assembles the initial payload.
3. Inertia page receives initial props.
4. Vue Query composables reuse those props as `initialData`.
5. Feature components stay focused on rendering and user interaction.

## API Pattern

1. Route points to a thin API controller.
2. Controller uses a `FormRequest` for auth and validation.
3. Controller delegates to an action/service.
4. Response is returned through a resource or stable response payload.

## Documentation Pattern

- Update `docs/agent-handoff.md` when priorities change.
- Update `docs/roadmap.md` when a new workstream starts or finishes.
- Update `CHANGELOG.md` when architecture or behavior changes land.
