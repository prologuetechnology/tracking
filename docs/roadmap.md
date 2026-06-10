# Roadmap

## Active

- [x] Transfer-kit alignment baseline docs completed and kept current
- [x] Herd-first onboarding docs and env contract refreshed for fresh-machine
      setup
- [x] Laravel 13 and PHP 8.4 framework/runtime upgrade completed with stable
      package constraints
- [ ] Page routes moved to thin controllers
- [ ] Company admin surface refactored to actions/resources with stable payloads
- [ ] Theme admin surface refactored to actions/resources with stable payloads
- [ ] Allowed-domain admin surface refactored to actions/resources with stable
      payloads
- [x] Image admin surface normalized to page-controller hydration and
      resource-backed API payloads
- [x] Company asset dialogs now reuse the shared image library and unassign
      images safely without deleting shared records
- [x] RBAC admin surface refactored to thin controllers and explicit requests
- [x] Tracking workflows extracted from controllers into actions/services
- [x] Feature coverage added for page access, hydration, and core admin APIs
- [x] Coverage expanded for active middleware, super-admin page hydration, and
      branded tracking success flows
- [x] Lint/build/test scripts documented and validated
- [x] Request context, API JSON exception rendering, Vue Query defaults, and
      pattern-alignment architecture tests added

## Next

- [x] Add deterministic Dusk environment wiring for repeatable browser smoke
      runs
- [ ] Add stronger error and observability guidance around Pipeline failures
- [ ] Expand request authorization and response-shaping coverage across any
      remaining non-aligned API controllers
