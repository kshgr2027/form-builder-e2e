# Form Builder — E2E Automation Suite (Playwright + TypeScript)

End-to-end test suite for the Laravel **Form Builder** module: authoring a form in the
wizard, configuring fields and settings, publishing, and collecting public submissions —
with assertions on **business behaviour and persisted database state**, not just the UI.

- Framework: [`@playwright/test`](https://playwright.dev) + TypeScript, Page Object Model
- DB assertions: `mysql2` against the app's `demo` schema
- CI: GitHub Actions (`.github/workflows/ci.yml`)

---

## 1. Application under test

A stripped-down slice of a larger LMS. Only these routes are live:

| Route | Purpose |
|---|---|
| `GET /` | Form Builder wizard (`form.blade.php` + `public/js/form-builder-v3.js`) |
| `GET /forms` | Forms dashboard (+ `?filter=` XHR feed) |
| `POST /forms` | Create form template |
| `POST /form-status` | Publish / unpublish |
| `GET \| POST /submit/{slug}` | Public form page + submission |
| `GET /s/{unique_string}` | Short link |
| `POST /forms/{id}` · `POST /forms-unarchive/{id}` | Archive / unarchive |

Tables: `form_templates` (the form + its JSON `form_structure` + settings) and
`form_submissions` (`submission_data` JSON).

## 2. Scope

**In scope**

- Form creation (wizard happy path + validation: title required / length / format, empty form, duplicate slug)
- Field management (add every in-scope type, edit label/required, duplicate, delete, reorder → verified in `form_structure`)
- Field configuration enforcement (required, number range, char length, option lists, checkbox-group required, conditional logic)
- Form settings behaviour (`multi_submission`, `accessible_using_url`, `is_published`, custom success message, custom submit text)
- Publish workflow (wizard "Publish now", dashboard toggle, `isEverPublished` latch, filter counts)
- Public form rendering + client-side validation + conditional show/hide
- Submission — positive (persisted `submission_data`), negative & boundary
- Access control on `GET`/`POST /submit/{slug}` and the short link

**Out of scope** (and why)

| Area | Reason |
|---|---|
| Authentication & role permissions | No auth is wired up in this build (`login` route does not exist) |
| Registration forms, State/City/College/Address fields | Depend on tables/routes absent from this build |
| File upload (`file` field, sample-file upload) | `azure` disk has no adapter/credentials → 500 locally |
| Submission reports / exports / edit / review / scoring admin | Unrouted and/or depend on missing tables |
| `server.js` (Socket.IO proctoring), email campaigns, dynamic-URL forms | Not part of the Form Builder flow |

## 3. Tech stack

`@playwright/test`, TypeScript, `mysql2`, `dotenv`. Node 20+. No other runtime deps.

## 4. Prerequisites

- **Node** 20+
- **PHP** 8.x + **Composer** (to run the app)
- **MySQL** 8 (schema named `demo`)
- The Laravel app source (the `lms-assessment/` folder from the assignment)

## 5. Application setup

```bash
cd lms-assessment
composer install

# create an empty schema, then:
php artisan migrate                       # users + Laravel system tables
mysql -u root -proot demo < demo.sql      # form_templates + form_submissions

php artisan key:generate
```

Edit `lms-assessment/.env`:

```
APP_URL=http://127.0.0.1:8000
APP_DEBUG=true          # REQUIRED — the short-link test (DEF-05) asserts an HTTP 500
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=root
```

> If `php artisan migrate` fails on a Telescope migration, delete
> `database/migrations/*telescope*.php` (Telescope is referenced but not installed in this build).

Run the app:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## 6. Environment variables (this suite)

Copy `.env.example` → `.env`:

| Var | Default | Notes |
|---|---|---|
| `BASE_URL` | `http://127.0.0.1:8000` | Must match the app's `APP_URL` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `127.0.0.1` / `3306` / `demo` / `root` / `root` | Omit all to run **without** DB assertions (they `test.skip`) |
| `PW_START_APP` | `0` | `1` → Playwright runs `php artisan serve` for you |
| `PW_APP_DIR` | `../lms-assessment` | Used only when `PW_START_APP=1` |

## 7. Install

```bash
npm ci
npx playwright install --with-deps chromium
```

## 8. Running tests

```bash
npm test                         # all specs, headless, fully parallel
npm run test:headed              # headed
npm run test:ui                  # Playwright UI mode
npm run test:builder             # tests/builder
npm run test:public              # tests/public-form + tests/submission
npm run test:access              # tests/access-control
npm run test:known-defects       # only @known-defect tests
npm run test:no-known-defects    # everything except known-defect tests

npx playwright test -g "TC-15"                 # one test case
npx playwright test tests/submission/ --workers=1   # serialise
npx playwright test --repeat-each=2                 # isolation / flake check
PW_WORKERS=4 npx playwright test                    # override worker count (see note below)
```

## 9. Parallel execution & isolation

- `fullyParallel: true`; `workers` defaults to **2** (override with `PW_WORKERS`). The app runs on
  `php artisan serve` — the PHP built-in server handles one request at a time, so > 2 concurrent
  workers saturate it and cause navigation timeouts. Verified: 49/49 at 2 workers; 47/49 at 4.
- **Every test provisions its own form** via the API, slug-prefixed `fb-e2e-<hint>-w<worker>-<ts>`,
  and asserts against **its own** `form_templates` / `form_submissions` rows. No shared fixture, no
  ordering. The `forms` fixture deletes each form it created in teardown; `global-teardown.ts`
  purges any residual `slug LIKE 'fb-e2e-%'` as a safety net.
- Dashboard tests scope every assertion to their own form name (never to absolute totals) — except
  `TC-32b`, which asserts a *delta* in the draft count.

## 10. Reports, screenshots, traces

- HTML report: `playwright-report/` → `npm run report`
- JUnit XML: `results/junit.xml` (for CI dashboards)
- `screenshot: 'only-on-failure'`, `video: 'retain-on-failure'`, `trace: 'on-first-retry'`
- Open a trace: `npx playwright show-trace test-results/<...>/trace.zip`
- Curated evidence for the defect report lives under `evidence/` (screenshots + sample traces).

## 11. Defect summary

Full write-ups with repro steps, expected/actual, severity, evidence and code citations in
[`docs/defect-report.md`](docs/defect-report.md). Defects reproduced at runtime (2026-09-01) by a
passing `@known-defect` test:

| ID | Sev | Title | Test |
|---|---|---|---|
| DEF-01 | High | Optional email/phone fields are rendered `required` on the public form | `public-form/render.spec.ts` TC-18 |
| DEF-02 | High | Text-field constraints (min/max chars, pattern, allowed chars) are dropped by the renderer and absent server-side | `public-form/client-validation.spec.ts` TC-23, `submission/server-validation.spec.ts` TC-23 |
| DEF-03 | High | `POST /submit/{slug}` enforces none of the access guards the `GET` page enforces (unpublished / not-accessible forms still accept submissions) | `access-control/access.spec.ts` TC-36, TC-37 |
| DEF-04 | High | `multi_submission = 0` is not enforced server-side | `submission/multi-submission.spec.ts` TC-28 |
| DEF-05 | High | `GET /s/{unique_string}` returns HTTP 500 (undefined `$role`) | `access-control/short-link.spec.ts` TC-38 |
| DEF-06 | Medium | `number` / `tel` fields have no server-side format or range validation | `submission/server-validation.spec.ts` TC-20, TC-22 |
| DEF-07 | Medium | `select` / `radio` values are not whitelisted against the option list server-side | `submission/server-validation.spec.ts` TC-24 |
| DEF-08 | Medium | `date` field has no server-side `date` rule or range check | `submission/server-validation.spec.ts` TC-08b |
| DEF-11 | Medium | Stored XSS — an `<img onerror>` payload in a field label executes on the public form | `public-form/render.spec.ts` TC-40 |
| DEF-15 | Medium | Post-submit success script throws (`toastr` loaded before jQuery) → **custom redirect never fires**, success toast never shows — *discovered at runtime* | `builder/settings-publish.spec.ts` TC-39 |

`@known-defect` tests assert the **current (buggy)** behaviour so the suite stays green and acts as
a tripwire — if the app is fixed, the assertion flips and the test fails, prompting a defect close.
Two further items are design/implementation concerns observed at runtime but not asserted as bugs:
**DEF-09** (no authorization on create/publish/archive) and **DEF-10** (`form_structure` stored
double-JSON-encoded). See `docs/defect-report.md` for the full A/B/C classification.

## 12. CI/CD

`.github/workflows/ci.yml` runs on push / PR / manual dispatch:

1. MySQL 8 service container (`demo` schema)
2. Checkout suite; checkout the app under test (from `./app`, or clone via the `APP_REPO` repo variable — see below)
3. PHP 8.2, `composer install`, configure `.env` (`APP_DEBUG=true`), remove the Telescope migration
4. `php artisan migrate` + import `demo.sql`
5. `php artisan serve` (with a readiness poll)
6. Node 20, `npm ci`, `npx playwright install --with-deps chromium`
7. `npx playwright test`
8. Upload `playwright-report/` always; `test-results/` + artisan log on failure

**Providing the app to CI:** the Laravel app under test **is bundled in this repo at `./app`**
(source only — `vendor/`, `.env` and `storage/*` runtime state are git-ignored and rebuilt in CI).
The workflow is therefore self-contained: clone, push, and it runs. (Alternative: set repo variable
`APP_REPO` (`owner/repo`) + optional `APP_REF` + secret `APP_REPO_TOKEN` to pull the app from
elsewhere instead.)

## 13. Assumptions

- **No authentication** exists in this build; `login_required` is therefore not verifiable (DEF-14, informational).
- `demo.sql` ships **schema only, no data**, and no `users`/system tables — those come from `php artisan migrate`.
- Every submission is attributed to `userid = 1` (hardcoded in `FormSubmissionController::store()`).
- File-upload paths are excluded (no Azure adapter/credentials).
- `APP_DEBUG=true` is required for `TC-38` to observe a 500 rather than a generic error page.
- `form_templates.form_structure` is stored **double-JSON-encoded** (Eloquent cast quirk, DEF-10); the DB layer decodes defensively.
- DB-dependent assertions **skip** (not fail) when `DB_*` is not configured.

## 14. Known limitations

- Single browser project (Chromium) by default; a Firefox project is stubbed in the config.
- Multi-page forms are covered only for "renders across a page break", not full next/prev navigation (no such UI in this build).
- `TC-32b` asserts a delta in the dashboard draft count; if other clients mutate forms concurrently it could be noisy — run the dashboard specs with `--workers=1` if needed.
- The suite (**49 tests / 12 specs**, TC-01…TC-40) was executed against a live app on 2026-09-01:
  **49/49 pass** (2 workers), **98/98** with `--repeat-each=2`. Full detail in `docs/execution-summary.md`.
- `dashboard.spec.ts` runs `mode: 'serial'` — the dashboard list is one shared, globally-filtered DOM
  surface. Its tests are still independent (each provisions and cleans up its own form).

## 15. Layout

```
src/
  api/         FormsApi, SubmissionsApi           (HTTP; CSRF handling)
  db/          Db + repositories                  (all SQL lives here)
  data/        field factories + form structures  (no raw JSON in tests)
  fixtures/    test.ts                            (merged fixtures + `forms` factory)
  pages/       builder/ dashboard/ public/        (all selectors live here)
  utils/       env, http, slug, annotate
tests/
  builder/  public-form/  submission/  access-control/  publish/
docs/          test-strategy.md, test-cases.md, defect-report.md, coverage-matrix.md
.github/workflows/ci.yml
```
