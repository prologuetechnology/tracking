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
worker. For the local database queue, run `herd php artisan queue:work --timeout=110`
in another terminal. Restart existing long-lived workers after code changes with
`herd php artisan queue:restart`.

### Test the customer and staff workflow

Choose a company in the local Herd database and a known bill of lading or
carrier PRO for its Pipeline account. Live lookup requires valid Pipeline
credentials.
Keep the original public tracking link open in a private window. Include its
existing brand parameter when the Pipeline company ID is shared. For a company
awaiting its first publication, the link displays Classic.

1. Sign in to the Herd site as a staff user with `company:show` and
   `company:update`. Open **Companies**, choose the company, and select **Design
   drafts → Create design draft**. Give the proposal a name. A new proposal
   starts from that company's current publication or its original branding.
2. Enter a bill of lading or carrier PRO and select **Update preview**.
   Compare the four layouts using **Desktop preview** and **Mobile preview**.
   On a small screen, switch between **Settings** and **Preview**. Choose
   **Update preview** after changing settings or the reference.

   | Layout             | What to check                                                                                                                                                   |
   | ------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
   | Classic            | Logo, banner, footer, shipment details and status history follow the current fixed layout.                                                                      |
   | Get the answer     | Choose delivery timing or the latest event as the lead detail. Move a section up and check its placement under the shipment status.                             |
   | Follow the journey | Check the route, date-grouped travel log and featured itinerary section. On a shipment with a past exception, select **Earlier exception** to jump to the scan. |
   | Inspect the record | Check the featured section in the shipment file. Select a scan to inspect its time, location in the row, and available carrier evidence.                        |

3. In a curated layout, try a preset palette or a library theme, change its
   headline style, reorder supporting sections, and toggle an available company
   feature. A section appears when both the company and shipment support it.
   Switch layouts and return to confirm each keeps its own order. Refresh the
   guest window to check that the public page still shows the current publication.
4. Select **Save draft**, reopen it from **Design drafts**, then change a
   setting and choose **Save new revision**. Under **Saved revisions**, open
   the version you intend to send. Load the shipment and select **Export
   desktop and mobile PNGs**. This makes a fresh lookup and freezes the data
   once for both images. Download **Desktop PNG** and **Mobile PNG**.
5. Select **Back to draft**, reopen that revision, and download its files
   again. Compare the corresponding file hashes with `shasum -a 256`. Each
   original pair retains its proof ID and bytes even after the shipment or
   draft changes. A second export makes a separate proof.
6. Staff send the two images through the customer's usual approval channel.
   On the local demo proof, select **Record customer approval**, enter the
   approver, then choose **Save customer approval**. Only **Publish approved
   revision** changes the tracking design on the existing guest link. Refresh
   that link on desktop and mobile to verify its branding and live data.
7. On **Design drafts**, select **View approved proof** to open the current
   pair. Expand **Publication history** to open any earlier published proof,
   **Restore this version**, or **Restore original Classic branding**. Refresh
   the same guest link after restoring. Customers review PNGs outside the portal.

Demo the integration branch and obtain business acceptance before opening its
PR to `develop`. Customer-specific changes follow the proof approval and
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
