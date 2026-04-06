# Release Process

This is the minimal, boring, reliable way we ship without breaking production.

## Versioning

- Tags use semver: `vMAJOR.MINOR.PATCH` (example: `v1.0.1`).
- Patch: bugfixes, no behavioral surprises.
- Minor: additive features.
- Major: breaking changes.

## Before tagging a release

1. Update `CHANGELOG.md`
   - Move relevant entries from `Unreleased` into a new version section.
   - While we’re building features on `develop`, add entries to `Unreleased` as we go (so release prep is just a quick sweep).
2. Run checks locally:
   - `scripts/predeploy-check.sh` (or `scripts/predeploy-check.sh --full`)
3. Sanity-check smoke flows:
   - homepage loads
   - login works
   - authenticated dashboard loads
   - core snippets pages load

## Tagging

- Tag from `main`.
- Command:
  - `git tag -a vX.Y.Z -m "vX.Y.Z"`
  - `git push origin vX.Y.Z`

## After release

- Keep `Unreleased` at the top of `CHANGELOG.md` so we always have a place to write things down as we go.
