# Progress Report — Form Builder QA Automation Assessment

_Last updated: 2026-09-01 (post first live execution)_

This document is a running status log of the assessment work. The permanent project
documentation lives in [`README.md`](README.md) and [`docs/`](docs/).

---

## 1. Where things stand

| Phase | Status |
|---|---|
| Analyse the provided Laravel app | ✅ Done — full read of routes, both controllers (~6,000 lines), models, `demo.sql`, builder wizard JS, public renderer JS |
| Define testing scope | ✅ Done — `docs/test-strategy.md`, README §2 |
| Exploratory analysis + defect discovery | ✅ Done (static) — 13 findings, 7 confirmed, in `docs/defect-report.md` |
| Test-case design | ✅ Done — `docs/test-cases.md` (TC-01…TC-40) |
| Automation framework | ✅ Built — POM + fixtures + API + DB layers, `npx tsc` clean |
| Automated test implementation | ✅ 49 tests / 12 spec files (TC-01…TC-40) |
| **Live test execution** | ✅ **Done 2026-09-01** — local stack stood up (PHP 8.2 / MySQL 26.7 / Composer / Git via scoop). Run 1: **49/49 pass** (2 workers, 3.8 min). `--repeat-each=2`: **98/98 pass** (no flakes). See `docs/execution-summary.md`. |
| Defect runtime verification | ✅ DEF-01…08, 11 reproduced by passing `@known-defect` tests. **DEF-15 discovered at runtime.** DEF-09/10 observed. `docs/defect-report.md` updated. |
| README / defect report / test-case doc / coverage matrix / execution summary | ✅ Updated with runtime results |
| CI/CD | ✅ `.github/workflows/ci.yml` — app bundled at `./app` so it's self-contained. **Green on a clean runner:** [run #6](https://github.com/kshgr2027/form-builder-e2e/actions/runs/33515146355) (commit `0fb06ca`) — composer install → migrate → import `demo.sql` → `php artisan serve` → `npx playwright test`, all steps pass. |
| Evidence capture | ✅ `evidence/defects/` (5 screenshots + API-repro log) + `evidence/execution/` (run logs) |
| Git repo | ✅ Pushed → **https://github.com/kshgr2027/form-builder-e2e** (`main`) |
| Drive package | ✅ `../Senior-QA-Automation-Assessment/` built (6 doc PDFs + evidence + link file) — upload pending |

---

## 2. Application analysis (summary)

**App under test:** a stripped-down slice of a Laravel 8 LMS — the **Form Builder** module.
A user builds a form in a 4-step wizard, it is stored as a `form_templates` row with a JSON
`form_structure`, it is published, and the public submits it into `form_submissions`.

**Only 11 web routes are registered.** No authentication is wired up (`login` route does not
exist). `demo.sql` ships **table structure only — no data**, and no `users`/system tables.

**Testable surface:**

| Route | Purpose |
|---|---|
| `GET /` | Builder wizard (`form.blade.php` + `public/js/form-builder-v3.js`) |
| `GET /forms` | Dashboard + `?filter=` XHR feed (`index_new.blade.php`) |
| `POST /forms` | Create form (no auth) |
| `POST /form-status` | Publish / unpublish (no auth) |
| `GET \| POST /submit/{slug}` | Public form page + submission |
| `GET /s/{unique_string}` | Short link |
| `POST /forms/{id}` · `/forms-unarchive/{id}` | Archive / unarchive |

**Key architectural finding:** validation is implemented **three times and they disagree** —
in the builder, in `form-renderer.js`, and in `FormSubmissionController::generateValidationRules()`.
The server-side generator ignores nearly every per-field constraint the builder can configure
(min/max length, numeric range, allowed characters, option whitelisting, date range). This is
where most defects live and where the suite concentrates.

**Out of scope (documented, with reasons):** authentication & roles, registration forms,
State/City/College/Address fields, file upload (no Azure adapter), report/export/review/scoring
admin screens, `server.js` proctoring, email, dynamic-URL forms.

---

## 3. Defects found (static analysis — see `docs/defect-report.md` for full write-ups)

**A** = confirmed (code + a test reproduces it) · **B** = potential, needs product confirmation · **C** = design concern.

| ID | Sev | Class | Summary |
|---|---|---|---|
| DEF-01 | High | A | Optional **email / phone** fields are rendered `required` on the public form regardless of the builder toggle (`form-renderer.js` hardcodes it). |
| DEF-02 | High | A | **Text-field** constraints (min/max chars, pattern, allowed chars) are dropped by the renderer **and** absent server-side — the config does nothing. |
| DEF-03 | High | A | `POST /submit/{slug}` enforces **none** of the access guards the `GET` page enforces — unpublished / not-accessible forms still accept submissions. |
| DEF-04 | High | A | `multi_submission = 0` ("one response") is **not enforced server-side** — only a Blade check on page reload. |
| DEF-05 | High | A | `GET /s/{unique_string}` (dashboard "short link") returns **HTTP 500** — undefined `$role` in `short_link_show()`. |
| DEF-06 | Medium | A | `number` / `tel` fields have **no server-side** numeric / range / format validation. |
| DEF-07 | Medium | A | `select` / `radio` submitted values are **not whitelisted** against the configured options server-side. |
| DEF-08 | Medium | A | `date` field has no server-side `date` rule or range check. |
| DEF-09 | Medium | C | Create / publish / archive endpoints have **no authorization** (may be acceptable for this cut-down build — confirm). |
| DEF-10 | Medium | C | `form_templates.form_structure` is stored **double-JSON-encoded** (Eloquent cast quirk). |
| DEF-11 | Medium | B | **Stored XSS** via field label / title / description (renderer interpolates into `innerHTML` unescaped). |
| DEF-12 | Low | C | `routes/web.php` is registered **twice** in `RouteServiceProvider`. |
| DEF-13 | Low | B | `store()` and `update()` use **divergent validation rules** (e.g. `success_message` length). |

Nothing is claimed as a bug without a `file:line` citation.

---

## 4. Automation framework (built)

```
playwright-form-builder/
├─ src/
│  ├─ pages/          FormBuilderPage + FieldOptionsPanel, FormsDashboardPage, PublicFormPage, BasePage
│  ├─ api/            FormsApi (create/publish/archive/raw), SubmissionsApi (raw POST /submit + CSRF)
│  ├─ db/             Db + FormTemplateRepo + FormSubmissionRepo   (all SQL here; double-encode handled)
│  ├─ data/           typed field factories + ready-made form structures  (no raw JSON in tests)
│  ├─ fixtures/       test.ts — `forms` factory (per-test fb-e2e- form + auto cleanup), worker-scoped db
│  └─ utils/          env (validated), http (CSRF + form encoding), slug, annotate (knownDefect)
├─ tests/
│  ├─ builder/        create.spec, fields.spec, settings-publish.spec
│  ├─ public-form/    render.spec, client-validation.spec, conditional.spec
│  ├─ submission/     submit-persist.spec, server-validation.spec, multi-submission.spec
│  ├─ access-control/ access.spec, short-link.spec
│  └─ publish/        dashboard.spec
├─ docs/              test-strategy.md, test-cases.md, defect-report.md, coverage-matrix.md
├─ .github/workflows/ ci.yml
├─ global-setup.ts / global-teardown.ts
├─ playwright.config.ts   (fullyParallel, retries in CI, trace on-first-retry, no hard waits)
└─ README.md
```

**Design decisions:**

- **Page Object Model** — every CSS selector lives in `src/pages/**`, every SQL string in `src/db/**`.
  Test files read as business intent (`settings.setSetting('multi', true)`, `publicFormPage.fill(record)`).
- **Arrange via API, act via the thing under test.** `FormsApi.createForm()` + `publish()` stand up a
  ready form in ~200 ms; the browser builder is driven only by the ~10 tests whose *subject* is the builder.
- **Server behaviour is probed via raw HTTP** (`SubmissionsApi` + CSRF from `<meta>`) so client-side JS
  can't mask a server gap — this is how DEF-03/04/06/07 are proven unambiguously.
- **Known-defect tests assert the _current_ (buggy) behaviour**, tagged `@known-defect` + annotated with the
  DEF id. The suite stays green; if the app is fixed the assertion flips and the test fails → close the defect.
- **Isolation & parallelism** — `fullyParallel: true`; each test creates its own `fb-e2e-<hint>-w<worker>-<ts>`
  form and asserts on its own DB rows. Per-test cleanup + `global-teardown` purge of `slug LIKE 'fb-e2e-%'`.
- **No hard waits** — `waitForResponse` / web-first assertions only; "client blocked the submit" is asserted
  by watching that no `POST /submit` fired, not by sleeping.
- **DB assertions degrade gracefully** — if `DB_*` is not set they `test.skip` instead of failing.

**Test count:** 49 tests across 12 spec files (TC-01…TC-40 + sub-cases). TC-08b (date server rule,
`@known-defect` DEF-08), TC-39 (custom redirect, positive), TC-40 (stored XSS, `@known-defect` DEF-11)
implemented 2026-09-01 using the existing architecture; **compile + `--list` clean, execution pending a live run.**

---

## 5. Verification done so far

| Check | Result |
|---|---|
| `npm install` | ✅ `@playwright/test` 1.49.1, `mysql2`, `dotenv` |
| `npx tsc --noEmit` | ✅ clean |
| `npx playwright test --list` | ✅ 49 tests, 12 files |
| Live run (`npx playwright test`) | ✅ **49/49 pass**, 2 workers, 3.8 min (2026-09-01) |
| Isolation (`--repeat-each=2`) | ✅ **98/98 pass**, 7.6 min — no flakes, no ordering deps |
| Parallelism ceiling | `php artisan serve` is single-threaded → `workers: 2` is the supported max (4 saturates it) |

---

## 6. What's needed to run it

1. Install **Git**, and a **PHP 8 + MySQL 8** stack (Laragon or XAMPP).
2. `cd lms-assessment && composer install`; create schema `demo`; `php artisan migrate`;
   `mysql -u root -proot demo < demo.sql`; `php artisan key:generate`.
3. In `lms-assessment/.env`: `APP_URL=http://127.0.0.1:8000`, **`APP_DEBUG=true`** (TC-38 asserts a 500),
   `DB_DATABASE=demo`, `DB_USERNAME=root`, `DB_PASSWORD=root`. Delete `database/migrations/*telescope*.php` if migrate fails.
4. `php artisan serve --host=127.0.0.1 --port=8000`
5. In `playwright-form-builder/`: `cp .env.example .env`, `npm ci`,
   `npx playwright install --with-deps chromium`, `npm test`.

---

## 7. Remaining work

- [x] Implement TC-08b (date server rule), TC-39 (custom redirect → DEF-15), TC-40 (stored XSS / DEF-11).
- [x] Run the full suite against a live app; fix selector/timing issues surfaced by the real DOM (16 automation bugs found + fixed; see execution-summary).
- [x] Stabilise: `npx playwright test --repeat-each=2` → 98/98.
- [ ] Capture per-defect evidence (screenshots + trace zips) into `evidence/`.
- [ ] `git init`, commit, push to GitHub; run the CI workflow end-to-end.
- [ ] Assemble the Google Drive submission folder.
- [ ] Assemble the Google Drive folder: repo link + commit SHA, README.pdf, Test-Cases, Defect-Report.pdf,
      Evidence/, CI run screenshot, Coverage-Matrix.
- [ ] Final pass against the submission checklist in the strategy doc.
