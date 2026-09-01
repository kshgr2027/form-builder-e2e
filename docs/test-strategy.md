# Test Strategy — Form Builder E2E

## Objective

Prove that a form authored in the builder behaves correctly when a member of the public
fills and submits it — with the emphasis on **business logic, permissions and persisted
data**, not UI presence checks or HTTP status codes alone.

## Risk-based scope

The module implements validation **three times** — in the builder, in `form-renderer.js`,
and in `FormSubmissionController::generateValidationRules()` — and they disagree. The
server-side generator ignores nearly every per-field constraint the builder can configure.
So the suite concentrates on:

1. **Configuration → enforcement.** Does a constraint set in the builder actually gate a submission (client *and* server)?
2. **Access control.** Do `is_published` / `accessible_using_url` / `active` / unknown-slug behave consistently across `GET` and `POST /submit/{slug}` and the short link?
3. **Persistence.** Does `submission_data` contain exactly what was entered (types, arrays, encoding)?
4. **State transitions.** Publish / unpublish / archive, `isEverPublished` latch, dashboard counts.

Out of scope and why: see README §2 (no auth in build; missing tables/routes for
registration & State/City/College; no Azure for file upload; unrouted admin screens).

## Test design principles

- **Assert on three planes:** UI (web-first assertions), HTTP (status / JSON / redirect target),
  and **DB** (row exists, `submission_data` matches, flags transitioned). A test that only
  checks a toast is not sufficient at this level.
- **Arrange via API, act via the thing under test.** `FormsApi.createForm()` + `publish()`
  stand up a ready form in ~200 ms. The browser builder is exercised only by the ~10 tests
  whose *subject* is the builder.
- **Probe server behaviour via `SubmissionsApi`** (raw HTTP + CSRF) so client JS never
  masks a server-side gap — this is how DEF-03/04/06/07 are proven unambiguously.
- **Known-defect tests assert the *current* behaviour** and carry `@known-defect` + an
  annotation with the DEF id. The suite stays green; if the app is fixed the assertion
  flips and the test fails, prompting a defect close.

## Isolation & parallelism

- `fullyParallel: true`. Every test creates its own `fb-e2e-<hint>-w<worker>-<ts>` form and
  asserts against its own rows. No shared fixture, no ordering.
- The `forms` fixture deletes each form it created (DB delete, or API archive when no DB).
- `global-setup` purges stale `fb-e2e-%` before the run; `global-teardown` purges after.
- Dashboard assertions are scoped to the test's own form name; the one count-delta assertion
  (TC-32b) tolerates concurrency or is run with `--workers=1`.

## Synchronization (no hard waits)

- Zero `page.waitForTimeout`. Builder DOM mutation is synchronous → `expect(locator)` auto-waits.
- Submissions: `Promise.all([page.waitForResponse(/\/submit\//), submitButton.click()])`.
- Dashboard: wait on the `GET /forms` XHR response after every mutating action.
- "Client validation blocked submit" is asserted by watching that **no** `POST /submit` request
  fires and that a `.is-invalid` field appears — not by sleeping.

## Environment contract

`BASE_URL` + a running app with `demo.sql` imported and `APP_DEBUG=true`; optional `DB_*`
for persistence assertions (they `test.skip` when absent). CI provisions MySQL, the app,
and the browser (see `.github/workflows/ci.yml`).

## Traceability

Every `test()` title carries its `TC-id`; known-defect tests also carry the `DEF-id` in the
title and an annotation. `docs/coverage-matrix.md` maps TC ↔ requirement ↔ DEF ↔ spec file.

## Exit criteria

- All non-`@known-defect` tests green on CI.
- All `@known-defect` tests green (asserting the documented bug) — any red one means the app
  changed and the defect report needs revisiting.
- `npx playwright test --repeat-each=2 --workers=4` stable (no order/parallel coupling).
- Defect report complete for every class-A finding with evidence + code citation.
