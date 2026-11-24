# Dev Runbook

This runbook is the day-to-day operational companion to
`docs/local-setup.md`. Use the local setup guide for fresh-machine bootstrap.
Use this file for regular development, verification, and debugging.

## Daily Commands

- Install or refresh backend dependencies:
  - `herd composer install`
- Install or refresh frontend dependencies:
  - `npm install`
- Run migrations and seeders:
  - `herd php artisan migrate --seed`
- Start frontend assets:
  - `npm run dev`
- Run the PHP test suite:
  - `herd php artisan test`
- Run Dusk browser coverage:
  - `herd php artisan dusk --without-tty`
- Build production assets:
  - `npm run build`
- Lint frontend code:
  - `npm run lint`
- Check PHP formatting:
  - `herd php vendor/bin/pint --test`

## Local Workflow Notes

- Herd is the source of truth for local web serving. Use the Herd site for the
  app and run `npm run dev` separately for Vite.
- This app requires PHP 8.4. Confirm `herd php -r 'echo PHP_VERSION, PHP_EOL;'`
  reports 8.4 before running Composer, tests, or Dusk.
- `composer dev` still exists, but it uses `php artisan serve`. Prefer Herd for
  normal local work unless you intentionally want the combined artisan stack.
- Standard local runtime uses MySQL. Automated tests and Dusk use sqlite-backed
  environments.

## Auth And Access Notes

- Normal web login is Azure OAuth only.
- Allowed-domain rules still apply after Azure authentication.
- Local and testing environments expose `/testing/oauth-login` as a fallback
  super-admin login helper for smoke checks. Do not rely on that route outside
  local or testing workflows.

## Tracking And Image Notes

- Live tracking in the normal local app depends on valid Pipeline credentials.
- `.env.dusk.local` swaps Dusk onto fake local Pipeline endpoints and sqlite.
- Image uploads default to the `spaces` disk in app config. Fresh local setups
  should usually override `IMAGE_UPLOAD_DISK=public` in `.env` until Spaces
  credentials are available.

## Pre-Deploy Local Check

- `herd php artisan test`
- `npm run lint`
- `npm run build`
- `herd php artisan dusk --without-tty`

## Cache And Route Debug

- Clear stale caches:
  - `herd php artisan optimize:clear`
- Inspect routes:
  - `herd php artisan route:list`

## Common Troubleshooting

- If the app URL, cookies, or OAuth redirect feel wrong, confirm `.env`,
  Herd's local domain, and Azure redirect URIs all point at the same host.
- If image uploads fail locally, confirm `IMAGE_UPLOAD_DISK` matches the disk
  you actually configured.
- If tracking lookups fail in the normal local app, confirm the Pipeline env
  values before debugging frontend behavior.
- If Dusk fails, compare `.env.dusk.local` with `tests/DuskTestCase.php`.

## Tracking studio on Herd

Use the checkout linked to the Herd site and the integration branch
`feature/tracking-layouts-and-branding`. Run the following from that checkout
after pulling changes.

```sh
herd php artisan migrate
npm ci --prefix tools/proof-renderer
npm run build
```

Herd serves the app. For live frontend edits, run `npm run dev` in another
terminal. Proof export always reads `public/build/manifest.json`, so rerun
`npm run build` before exporting changes made with Vite running. The proof
worker needs Chrome and Node. A database or Redis queue also needs a running
worker. For the local database queue, run `DB_QUEUE_RETRY_AFTER=150 herd php artisan queue:work --timeout=110`
in another terminal. Keep it running while testing with Herd and Vite. The revision page displays **Desktop PNG** and **Mobile PNG** download buttons after the job finishes. Restart existing long-lived workers after code changes with
`herd php artisan queue:restart`.

### Test the customer and staff workflow

Choose a company in the local Herd database and a known bill of lading or
carrier PRO for its Pipeline account. Live lookup requires valid Pipeline
credentials.
Keep the original public tracking link open in a private window. Include its
existing brand parameter when the Pipeline company ID is shared. For a company
awaiting its first publication, the link displays Classic.

1. Sign in with a staff account that has `company:show` and `company:update`. Open **Companies**, choose the company, then open **Design drafts**. The list identifies the active publication. **Create design draft** starts a named proposal from the current live design. To work from an earlier saved revision, open its preview and choose **Use revision as new draft**.
2. Open the editor's **Preview shipment** controls, choose **Bill of lading** or **Carrier PRO**, enter a shipment from this company, and choose **Load shipment**. An existing draft with a saved lookup loads automatically. Use **Draft** and **Live** to compare the two designs against one capture. **Desktop** and **Mobile** set the preview width. On narrow screens, switch between **Settings** and **Preview**.
3. Try each inspector tab.

   | Tab        | What to try                                                                                                                                                                                                                                                                                       |
   | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
   | Layout     | Classic retains the original customer layout. Get the answer emphasizes status and dates and opens shipment details in a dialog. Follow the journey pairs dated carrier scans with an itinerary and an expandable map. Inspect the record combines a shipment file with expandable scan evidence. |
   | Appearance | Try a preset, a library theme, or custom brand, accent and page background colors. Choose a headline style and use the thumbnail picker to select draft brand images.                                                                                                                             |
   | Sections   | Drag a supporting section or use the arrow buttons to move it. Toggle visibility for this layout. The editor identifies which section will appear first for this shipment. Switch layouts and back to check the independent settings.                                                             |
   | Features   | Toggle the shipment details available to this company. The public presenter intersects the saved choices with current company entitlements.                                                                                                                                                       |

   When reopening an older curated layout, choose **Use the updated layout** to try the new presentation. Renaming the draft or changing its shipment preserves the saved presentation. A loaded draft preview updates as settings change. In **Inspect preview element** mode, select a brand element or supporting section to open its editor controls. **Refresh shipment** fetches fresh data. A capture is private to the staff user, company and lookup and expires after 15 minutes; use a persistent cache shared by the web processes.

4. Choose **Save draft** or **Save new revision**. Open the saved revision and choose **Export desktop and mobile PNGs**. Both images use the shipment and capture time displayed on that saved revision. Choose **Refresh shipment** to review newer carrier data. An expired capture requires loading the shipment again before export. Once ready, use **Review images** to check those exact files in the browser or download both PNGs.
5. Reopen the same revision after saving another and check that its original proofs are still available. Use **Actual size** to inspect a long history at full resolution. When comparing files externally, `shasum -a 256` should produce the same digest for repeated downloads of each individual PNG.
6. In **Companies → Design Studio → Saved drafts**, choose **Approve revision** on the desired draft. For an older version, open its saved revision and approve it there. Approval applies to that version alone. Choose **Publish revision** and confirm when the existing tracking links should switch. Exporting and downloading images is optional. **Publication history** can restore an earlier design or **Restore original Classic branding**.

Demo the integration branch and obtain business acceptance before opening its
PR to `develop`. Customer-specific changes follow the revision approval and
publication steps above.

### Proof storage and troubleshooting

- The renderer uses installed Google Chrome stable. Set `TRACKING_PROOF_CHROME`
  for a different Chromium executable, or `TRACKING_PROOF_NODE` if Node is
  outside the worker's PATH. Run the worker as an unprivileged user with the
  Chromium sandbox available.
- A local `sync` queue renders during the export request. For deployment use
  an asynchronous worker, a retry interval of at least 150 seconds (for the
  database driver, `DB_QUEUE_RETRY_AFTER=150`), and the same production build
  on the worker and web hosts.
- Mount and back up `storage/app/private/design-proofs` across workers and web
  processes. Keep this directory outside the public document root. Back up
  the database and retain its Laravel encryption key. Each download verifies
  the archived bytes against the stored SHA-256 digest.
- `TRACKING_PROOF_TIMEZONE` defaults to `America/Chicago`. The PNGs capture
  the full page at 1× pixel density, using a 1440 × 900 desktop viewport and
  a 390 × 844 mobile viewport. A visible map can show the reported route
  schematic when detailed map tiles are unavailable.
- **Retry this snapshot** reuses the original shipment data after a failed
  export or one pending for ten minutes. Check the queue worker, Chrome,
  compiled assets, private storage, and availability of branding files.

## Related Docs

- Fresh-machine setup:
  - `docs/local-setup.md`
- Onboarding overview:
  - `README.md`
- Release expectations:
  - `docs/release-process.md`
- Repo source-of-truth index:
  - `docs/context-index.md`
