# Execution Summary

First full live execution of the suite against a running instance of the Laravel Form Builder app.

## Environment

| Item | Value |
|---|---|
| Application | Laravel 8.75 "Form Builder" module (`lms-assessment/`), served via `php artisan serve` |
| App URL | `http://127.0.0.1:8090` (port 8000 is blocked for `php.exe` by local endpoint protection; CI uses 8000) |
| PHP | 8.2.33 (scoop `versions/php82`) — `composer.lock` requires ≥ 8.2 (Symfony 7 components) |
| MySQL | 26.7.0 (Community Server), schema `demo`, `root`/`root` |
| Composer | 2.10.3 (installed with `--ignore-platform-reqs`) |
| Node | v24.19.0 |
| Playwright | `@playwright/test` 1.49.1 · Chromium 131.0.6778.33 (build v1148) |
| OS | Windows 10 |
| DB assertions | **enabled** (`DB_*` configured) — persistence checks ran, none skipped |
| Date of run | 2026-09-01 |

## Results

| Run | Command | Workers | Repeat | Tests | Passed | Failed | Flaky | Duration |
|---|---|---|---|---|---|---|---|---|
| 1 | `npx playwright test` | 2 | 1 | 49 | **49** | 0 | 0 | 3.8 min |
| 2 | `npx playwright test --workers=4` | 4 | 1 | 49 | 47 | 2 | 0 | 4.1 min |
| 3 | `npx playwright test --repeat-each=2` | 2 | 2 | 98 | **98** | 0 | 0 | 7.6 min |

- **49 tests / 12 spec files**, TC-01 … TC-40.
- **10 `@known-defect` tests** are included in the pass count — they assert the *current buggy*
  behaviour of DEF-01, 02, 03, 04, 05, 06, 07, 08, 11, 15 and therefore go green while the bug is present.
- Run 3 (`--repeat-each=2`) confirms **no order dependencies and no flakiness** at the supported worker count.

## Run 2 analysis (workers = 4)

The 2 failures under `--workers=4` were **environment saturation, not test or application faults**:

| Test | Failure | Cause |
|---|---|---|
| TC-12 | `page.goto('/')` timed out (20 s) | `php artisan serve` is the PHP built-in server — it handles **one request at a time**. Four concurrent workers (4 browsers + 4 API contexts, each page pulling `style.css` 815 KB + ~14 missing-asset 404s) queue past the navigation timeout. |
| TC-32b | `isListed` returned stale `true` | Same saturation: the `GET /forms` filter response was slow enough that the client re-render lagged the assertion. |

**Mitigation applied:** `playwright.config.ts` now sets `workers: 2` by default (override with `PW_WORKERS`),
`navigationTimeout: 30 s`, `timeout: 60 s`; the dashboard list assertions were made polling
(`FormsDashboardPage.expectListed`) so they absorb render lag; `dashboard.spec.ts` runs `mode: 'serial'`
because the list is one shared, globally-filtered DOM surface. Runs 1 and 3 at 2 workers are clean.

## Automation issues found & fixed during bring-up (not application defects)

| Area | Issue | Fix |
|---|---|---|
| Builder | `addField()` didn't re-open the "Add fields" tab — the builder auto-switches to "Field options" after each add, hiding `#palette` | `FormBuilderPage.openFieldsPanel()` before each palette click |
| Public form | `expectSuccess()` waited only for the transient toastr toast | `expectSubmitted()` — waits for the server-rendered "Form submitted!" heading *or* the toast |
| Client validation | assertions expected app-custom error text; the app surfaces the browser's native `validationMessage` | assertions broadened to the actual text (submit-blocked + error-shown still asserted) |
| Dashboard | `#tableBody, #cardView` / `.fb-item … input.fb-pub` matched multiple elements (card + row + "more" dropdown) | scoped locators: `cardItem()` in `#cardView`, `rowItem()` in `#tableBody`, `label.switch input.fb-pub` for the visible toggle |
| Dashboard | `waitForListLoad()` keyed on `#fbLoader`, which is CSS-hidden regardless of its `d-none` toggle | wait on the XHR + poll the per-form assertion |
| Test data | TC-16 omitted `phone`, which DEF-01 force-renders as required | fill `phone` |

## Application defects — runtime status

See `docs/defect-report.md` for full write-ups. Static findings that were **reproduced at runtime**
by a passing `@known-defect` test: DEF-01, DEF-02, DEF-03, DEF-04, DEF-05, DEF-06, DEF-07, DEF-08, DEF-11.
**DEF-15 was discovered during this execution** (custom post-submit redirect never fires — the success
`<script>` calls `toastr.success()` before the redirect `setTimeout`, and `toastr` is `undefined`
because `toastr.min.js` loads before jQuery). DEF-09 / DEF-10 were observed at runtime but remain
design/implementation concerns. DEF-12 / DEF-13 remain static-only.

## CI

`.github/workflows/ci.yml` is written and reviewed (see README §12 and `docs/` notes). It has **not
yet been run end-to-end** — it needs the Laravel app committed at `./app` or an `APP_REPO` repo
variable, plus a push. Local run 1 is the current green baseline.
