# Self-Review — as the evaluator

A critical pass over the submission against the assessment brief. Written to pre-empt the
questions a Senior QA interviewer will ask.

## 1. What is complete

| Area | State |
|---|---|
| App analysis | ✅ routes, both controllers, models, `demo.sql`, builder JS, renderer JS |
| Scope | ✅ Form Builder + Public Form; exclusions documented with reasons (`README` §2) |
| Test strategy | ✅ `docs/test-strategy.md` — risk-based, 3 assertion planes (UI / HTTP / DB) |
| Test cases | ✅ TC-01 … TC-40, `docs/test-cases.md`, mapped in `docs/coverage-matrix.md` |
| Framework | ✅ POM, API + DB layers, typed data factories, merged fixtures, `tsc` clean |
| Automated tests | ✅ **49 tests / 12 specs** |
| Live execution | ✅ **49/49 pass** (2 workers, 3.8 min); **98/98** with `--repeat-each=2` — no flakes, no ordering deps |
| Defect verification | ✅ DEF-01…08, 11 reproduced at runtime by passing `@known-defect` tests; **DEF-15 found at runtime**; DEF-09/10/16 observed |
| Evidence | ✅ `evidence/` — per-defect screenshots + API-repro log + run logs |
| Docs | ✅ README, strategy, test cases, defect report, coverage matrix, execution summary |
| Repo | ✅ `git init` + committed (`main`, clean tree, no secrets/artefacts) |
| CI workflow | ✅ written + reviewed (`.github/workflows/ci.yml`) |

## 2. What is still missing / open

| Gap | Why | Fix |
|---|---|---|
| **CI never run end-to-end** | Needs a pushed repo + the Laravel app committed at `./app` or an `APP_REPO` repo variable | Push, add the app, trigger the workflow, attach the green-run screenshot |
| **GitHub push** | Needs the account owner's credentials | 2 commands in `README` §12 / `01-GitHub/Repository-Link.txt` |
| Single browser (Chromium) | A Firefox project is stubbed but commented out; the PHP dev server can't take a 2nd engine in parallel | Enable Firefox as a project, run it `PW_WORKERS=1` |
| Builder drag-reorder | Only arrow-reorder (`[data-act=up/down]`) is tested; SortableJS drag is not | Add 1 test with `dragTo` |

## 3. What could reduce the score — and the answer

| Concern | Response |
|---|---|
| "Only 2 workers — weak parallelism" | Not a framework limit: `php artisan serve` is the PHP built-in server (one request at a time). Demonstrated 47/49 at `--workers=4` (2 navigation timeouts). Real CI would run the app under php-fpm/nginx. `PW_WORKERS` overrides. |
| "`dashboard.spec.ts` is `mode: 'serial'`" | The dashboard is one shared, globally-filtered list DOM — two tests toggling publish + switching filters repaint each other. Tests stay **independent** (each provisions + deletes its own form). This is `mode: 'serial'`, not `describe.serial()` (no inter-test data hand-off). |
| "`@known-defect` tests just make failing tests pass" | They assert the **current** behaviour with a `file:line` root cause and a `knownDefect()` annotation. If the app is fixed the assertion flips → test fails → prompts a defect close. It is a regression tripwire and a living defect record, not a muted test. |
| "Client-validation assertions use loose regexes" | The app writes the **browser's native `validationMessage`** into its error slot, not custom text — so the assertion matches that ("Please fill out this field.") plus the app's own wording. Submit-blocked + error-shown is still asserted precisely. |
| "TC-29 is API-only" | The custom success message is rendered only in the toastr toast, which is broken (DEF-15). The verifiable facts — server persists it, server returns it on submit — are asserted (API + DB). |
| "`page` is exposed on `BasePage`" | Pragmatic: a handful of specs need `page.content()` / `page.evaluate` / URL polling. All *selectors* still live in page objects; this is the common POM compromise, documented in `BasePage`. |
| "Non-standard local env (scoop PHP 8.2 / MySQL 26.7 / port 8090)" | `composer.lock` requires PHP ≥ 8.2; port 8000 is blocked for `php.exe` by local endpoint protection. CI and the README setup use the standard PHP 8.2 + port 8000 path. |

## 4. What a Senior QA interviewer will probe (be ready)

- **Data lifecycle** — `forms` fixture: `fb-e2e-<hint>-w<worker>-<ts>` slug, per-test DB delete, `global-teardown` purge of `slug LIKE 'fb-e2e-%'`. DB verified 0 rows after every run.
- **Arrange via API, act via the thing under test** — `FormsApi.createForm()` + `publish()` in ~200 ms; the browser builder is driven only by the ~10 tests whose subject *is* the builder. The API is *also* the surface under test for server-side defects (DEF-03/04/06/07/08).
- **No hard waits** — `waitForResponse`, web-first assertions, `expect.poll`. `submitExpectingClientBlock()` asserts a POST *never fired* rather than sleeping.
- **DB assertion isolation** — SQL only in `src/db/repositories/**`; typed DTOs; `decodeMaybeDoubleEncoded` handles DEF-10 once; assertions read specific fields, never whole-row snapshots; `test.skip` when `DB_*` is absent.
- **DEF-15 confidence** — reproduced deterministically; root cause is Blade/layout script order (toastr `<script>` before the footer jQuery `<script>`), and the redirect `setTimeout` sits after the throwing `toastr.success()` in the same handler. Page HTML + screenshot captured.
- **Why Chromium build 1148** — Playwright 1.49.1 pins it; `npx playwright install chromium` fetched it.

## 5. Technical decisions to be ready to explain

- Page Object Model with selectors centralised; SQL centralised; typed data factories (no raw JSON in specs).
- Fixture scopes: `env` / `db` **worker-scoped** (one pool per worker), `forms` / page objects **test-scoped**.
- `retries: CI ? 2 : 0`, `trace: 'on-first-retry'`, `screenshot: 'only-on-failure'`, `video: 'retain-on-failure'`.
- `timezoneId` / `locale` pinned (date-range + native-message determinism).
- `webServer` config **not** used — CI starts `php artisan serve` in its own step so the readiness poll and logs are explicit; `global-setup` still health-checks `BASE_URL`.
- `PW_WORKERS` env override; `mode: 'serial'` only where a shared DOM surface demands it.

## 6. Defects to be ready to defend

| DEF | One-line defence | Evidence |
|---|---|---|
| 01 | `form-renderer.js` `case 'email'`/`'tel'` hardcode `required` — comment even says "bypassing missing element props" | `DEF-01-*.png`, TC-18 |
| 02 | `case 'text'` omits `minlength/maxlength/pattern/data-allowchars`; `generateValidationRules()` text branch is `required`/`nullable` only | `DEF-02-*.png`, api log, TC-23 ×2 |
| 03 | `store()` does only `firstOrFail()` on slug — none of `show()`'s four guards | api log (`GET 403 / POST 200`), TC-36/37 |
| 04 | non-review/non-registration branch of `store()` calls `FormSubmission::create()` unconditionally | api log (2 rows), TC-28 |
| 05 | `short_link_show()` references undefined `$role` | `DEF-05-*.png` (HTTP 500), TC-38 |
| 06/07/08 | `generateValidationRules()` emits no `numeric`/`Rule::in`/`date` rules | api log, TC-20/22/24/08b |
| 11 | renderer interpolates `element.label` into `innerHTML` unescaped (builder has an unused `esc()`) | `DEF-11-*.png` (`document.title = XSS-FIRED`), TC-40 |
| 15 | toastr `<script>` before jQuery `<script>`; redirect `setTimeout` after the throwing `toastr.success()` | `DEF-15-*.png`, page HTML, TC-39 |
| 09/10/16 | design/implementation concerns — flagged, not asserted as bugs | observed across every run |

## 7. Playwright questions this project invites

Fixture scope (worker vs test) · `expect.poll` vs locator auto-wait vs `waitForFunction` ·
`Locator.or()` / `.filter()` / `.first()` and strict mode · `mode: 'serial'` vs `describe.serial()` ·
trace viewer & `trace: 'on-first-retry'` · `APIRequestContext` + cookie/session + CSRF ·
`webServer` config · `testInfo.annotations` + `--grep` tags · workers vs `fullyParallel` ·
`page.pdf()` (used by the doc-packaging script).

## 8. Improvements worth doing

1. **Run CI once** (post-push) and attach the green run — biggest single gap.
2. Enable the **Firefox** project (accept `--workers=1` for it).
3. Add ~3 builder tests: SortableJS drag-reorder, duplicate-then-edit-independently, multi-page tab switching.
4. A short **a11y** pass on the public form (`@axe-core/playwright`) — labels/`for`, colour contrast.

## 9. Improvements NOT worth the time

- Blocking the ~14 stripped-app asset 404s via `page.route` — cosmetic; adds complexity.
- A visual-regression baseline — brittle on an app with broken CSS/JS load order.
- Testing the unrouted admin / report / registration surfaces — would require fabricating tables/routes (out of scope).
- Forcing the dashboard specs fully parallel — the app's shared-list design fights it; serial costs ~30 s.
