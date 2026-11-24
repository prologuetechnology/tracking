# Roadmap

## Active

- [x] Keep existing companies on Classic with the same links, branding, fixed
      section order, and guest refresh on desktop and mobile.
- [x] Offer Get the answer, Follow the journey, and Inspect the record with
      preset palettes, library themes, headline styles, feature switches and
      per-layout section order.
- [x] Give staff a private studio for named drafts and live company shipment
      previews at desktop and mobile widths. Save and reopen design revisions.
- [x] Compare draft and live layouts on one shipment capture, inspect elements, choose private custom colors and images, and reorder or hide sections per layout.
- [x] Start separate proposals from old revisions and review their archived approval images in the studio.
- [x] Archive both PNGs from a single shipment snapshot. Record customer
      approval on the selected proof, publish its revision, reopen the exact
      pair from history, and restore an earlier publication or Classic.
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

- [x] Select Signal and build the shipment console and staff appearance preference.

- [ ] Review the clickable staff portal at `/admin/portal-review` in Herd. After design acceptance, connect the shipment-first workspace and versioned style library to the live staff workflow.

- [ ] Demo all four layouts and the draft, export, approval, publication, and
      rollback workflow from `feature/tracking-layouts-and-branding`. Open the
      integration PR to `develop` after business acceptance.
- [ ] After release, invite customers with existing branding to review
      proposals for their own tracking pages.
- [x] Add deterministic Dusk environment wiring for repeatable browser smoke
      runs
- [ ] Add stronger error and observability guidance around Pipeline failures
- [ ] Expand request authorization and response-shaping coverage across any
      remaining non-aligned API controllers
