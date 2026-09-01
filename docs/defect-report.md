# Defect Report — Form Builder module

**Environment (runtime verification):** Laravel 8.75 · PHP 8.2.33 · MySQL 26.7 · `APP_DEBUG=true` ·
Chromium 131 (Playwright 1.49.1) · app served via `php artisan serve` at `http://127.0.0.1:8090` ·
`demo.sql` imported (schema only, no seed data). Run date 2026-09-01 — see `docs/execution-summary.md`.

**Classification:**
- **A** = code-confirmed **and** reproduced at runtime by a passing `@known-defect` test
- **B** = potential — code-confirmed, runtime reproduction pending or needs product confirmation
- **C** = design / implementation concern (observed, but may be acceptable for this cut-down build)

`@known-defect` tests assert the **current** behaviour, so the suite stays green and trips if the app is fixed.

| ID | Sev | Pri | Class | Component | Title | Test | Runtime |
|---|---|---|---|---|---|---|---|
| DEF-01 | High | P1 | A | Public form | Optional email / phone fields render as `required` | render TC-18 | ✅ reproduced |
| DEF-02 | High | P1 | A | Public form / API | Text-field constraints dropped by renderer & absent server-side | client-validation + server-validation TC-23 | ✅ reproduced |
| DEF-03 | High | P1 | A | Submission API | `POST /submit` ignores every access guard the `GET` page enforces | access TC-36, TC-37 | ✅ reproduced |
| DEF-04 | High | P1 | A | Submission API | `multi_submission = 0` not enforced server-side | multi-submission TC-28 | ✅ reproduced |
| DEF-05 | High | P1 | A | Routing | `GET /s/{unique_string}` → HTTP 500 (undefined `$role`) | short-link TC-38 | ✅ reproduced |
| DEF-06 | Medium | P2 | A | Submission API | `number` / `tel` have no server-side format/range rule | server-validation TC-20, TC-22 | ✅ reproduced |
| DEF-07 | Medium | P2 | A | Submission API | `select` / `radio` value not whitelisted against options | server-validation TC-24 | ✅ reproduced |
| DEF-08 | Medium | P2 | A | Submission API | `date` field has no server-side `date`/range rule | server-validation TC-08b | ✅ reproduced |
| DEF-09 | Medium | P2 | C | Routing / security | State-changing endpoints have no authorization | *(exercised by every test — all create/publish/archive calls are unauthenticated)* | ⚠️ observed |
| DEF-10 | Medium | P3 | C | Persistence | `form_structure` stored double-JSON-encoded | *(DB layer must double-decode for TC-01/07/10/11/12 to pass)* | ⚠️ observed |
| DEF-11 | Medium | P2 | A | Public form / security | Stored XSS — `<img onerror>` in a field label executes on the public form | render TC-40 | ✅ reproduced |
| DEF-12 | Low | P3 | C | Routing | `routes/web.php` registered twice | *(static only — no user-visible effect)* | — static |
| DEF-13 | Low | P3 | B | Builder | Create vs Update validation rules diverge | *(update path is out of scope)* | — static |
| DEF-15 | Medium | P2 | A | Public form | Post-submit success script throws (`toastr` loaded before jQuery) → **custom redirect never fires**, success toast never shows | settings-publish TC-39 | ✅ discovered at runtime |
| DEF-16 | Low | P3 | C | Layout | `admin.blade.php` loads Bootstrap 4 JS in `<head>` before jQuery (footer) → `Uncaught TypeError: Bootstrap's JavaScript requires jQuery` on **every** page; BS4 JS never initialises | *(observed on every page load in every test)* | ⚠️ observed |

---

### DEF-01 — Optional email / phone fields are rendered as `required` on the public form

- **Status:** Confirmed · **Severity:** High · **Priority:** P1
- **Component:** Public form (`public/js/form-renderer.js`)
- **Preconditions:** A published, accessible form containing an **Email** (or **Phone**) field with the builder's "Required" toggle **off**, plus at least one other field.
- **Steps to reproduce:**
  1. Builder → add a Text field (Required), add an Email field, leave Email **not** required.
  2. Create + Publish. Open `GET /submit/{slug}`.
  3. Inspect the email `<input>`; try to submit with the email left blank.
- **Expected:** Email is optional — no `*`, no `required` attribute; the form submits with email empty (server rule is `nullable|email`).
- **Actual:** The email input is rendered with `required` and a red `*`; client validation blocks submit until an email is entered.
- **Root cause:** `form-renderer.js` `case 'email':` and `case 'tel':` hardcode `required data-required="1"` and a forced star (`forcedEmailStar` / `forcedTelStar`), ignoring `element.required`. Comment in the code: *"Directly inject structural validation bypassing missing element props."*
- **Evidence:** `evidence/screenshots/DEF-01-*.png`, trace from `render.spec.ts` TC-18.
- **Impact:** Forms collect data the author explicitly marked optional; users are blocked from submitting valid partial data. Client/server contract mismatch.
- **Suggested fix:** Render `required` / `*` from `element.required` like every other field type.

---

### DEF-02 — Text-field validation config has no effect (renderer omits it; server ignores it)

- **Status:** Confirmed · **Severity:** High · **Priority:** P1
- **Component:** Public form renderer + `FormSubmissionController::generateValidationRules()`
- **Preconditions:** A form with a **Text input** configured with Min characters = 5, Max characters = 10, "Allowed input" = letters only.
- **Steps to reproduce:**
  1. Build & publish the form. Open the public page; inspect the text `<input>`.
  2. Enter `a1` (2 chars, contains a digit) and submit.
  3. Also `POST /submit/{slug}` directly with `code=ab`, `code=<400 chars>`, `code=abc123`.
- **Expected:** Values shorter than 5 / longer than 10 / containing non-letters are rejected (client inline error + server 422).
- **Actual:** The `<input>` carries **no** `minlength`, `maxlength`, `pattern`, or `data-allowchars` attribute; every value above is accepted and persisted to `form_submissions.submission_data`.
- **Root cause:**
  - `form-renderer.js` `case 'text':` emits only `placeholder`, `value`, `required` — unlike `case 'number'` / `case 'textarea'` it drops `minLengthAttr`, `maxLengthAttr`, `patternAttr`, `allowCharsAttr`.
  - `generateValidationRules()` produces `required`/`nullable` only for `text` — no `min`, `max`, `regex`.
- **Evidence:** `client-validation.spec.ts` TC-23, `server-validation.spec.ts` TC-23.
- **Impact:** All per-field text validation configured in the builder is silently non-functional — bad/oversized data enters the DB.
- **Suggested fix:** Emit the same attributes the `number`/`textarea` cases do; add `min`/`max`/`regex` rules in `generateValidationRules()` from `minLength`/`maxLength`/`pattern`.

---

### DEF-03 — `POST /submit/{slug}` enforces none of the access rules that `GET` enforces

- **Status:** Confirmed · **Severity:** High · **Priority:** P1
- **Component:** `FormSubmissionController::store()`
- **Preconditions:** Either (a) a form with `accessible_using_url = 0`, or (b) a form with `is_published = 0` and `accessible_using_url = 1`.
- **Steps to reproduce:**
  1. `GET /submit/{slug}` → observe `403` (case a) or note the form is a draft (case b).
  2. `POST /submit/{slug}` with a valid CSRF token and minimal field data.
  3. Check `form_submissions`.
- **Expected:** The submission is rejected (mirrors the `GET` guard / "not published").
- **Actual:** `store()` only does `FormTemplate::where('slug',$slug)->firstOrFail()` — no `active`, `accessible_using_url`, `is_published`, `login_required` or `is_dynamic_url` check. A row is created and returns `{success:true}`.
- **Root cause:** `store()` omits the four guards present in `show()`.
- **Evidence:** `access-control/access.spec.ts` TC-36, TC-37.
- **Impact:** Draft / disabled / de-listed forms keep collecting submissions; "unpublish" and "not accessible" give no real protection.
- **Suggested fix:** Extract the guard block from `show()` into a shared method and call it at the top of `store()`.

---

### DEF-04 — `multi_submission = 0` is not enforced server-side

- **Status:** Confirmed · **Severity:** High · **Priority:** P1
- **Component:** `FormSubmissionController::store()`
- **Preconditions:** A published form with "Multiple submission" = **No** (`multi_submission = 0`), non-registration, non-review.
- **Steps to reproduce:** `POST /submit/{slug}` twice (same session or different) with valid data; check `SELECT COUNT(*) FROM form_submissions WHERE form_template_id = ?`.
- **Expected:** The second submission is rejected; count stays at 1.
- **Actual:** Both succeed; count = 2. The only "single submission" logic is a Blade check in `show.blade.php` keyed to `userid = 1` that hides the form on page **reload** — trivially bypassed by a direct POST or a second browser.
- **Root cause:** The non-review/non-registration branch of `store()` calls `FormSubmission::create()` unconditionally.
- **Evidence:** `submission/multi-submission.spec.ts` TC-28 (+ TC-27b shows the Blade-only gate).
- **Impact:** "One response per person" cannot be relied on for polls, RSVPs, single-entry registrations, etc.
- **Suggested fix:** When `multi_submission == 0`, reject (or upsert) if a submission for this identity already exists — server-side.

---

### DEF-05 — `GET /s/{unique_string}` returns HTTP 500

- **Status:** Confirmed · **Severity:** High · **Priority:** P1
- **Component:** `FormSubmissionController::short_link_show()`
- **Preconditions:** Any active form (`active = 1`). The dashboard exposes this link as "Short form link" for every row.
- **Steps to reproduce:** Create + publish a form; open `GET /s/{unique_string}` (value from `form_templates.unique_string`).
- **Expected:** The public form renders (same as `/submit/{slug}`).
- **Actual:** HTTP 500 — `ErrorException: Undefined variable $role` (with `APP_DEBUG=true`; otherwise a generic 500 page).
- **Root cause:** Line ~28 of `short_link_show()`: `if (in_array($role, [2, 20, 6]) && ...)` — `$role` is never assigned in the method.
- **Evidence:** `access-control/short-link.spec.ts` TC-38.
- **Impact:** The short link is completely broken for every form; a primary "share" affordance on the dashboard 500s.
- **Suggested fix:** Remove the dead role check, or assign `$role` before use (there is no auth in this build).

---

### DEF-06 — `number` and `tel` fields have no server-side format / range validation

- **Status:** Confirmed · **Severity:** Medium · **Priority:** P2
- **Component:** `FormSubmissionController::generateValidationRules()`
- **Steps to reproduce:** For a `number` field configured Min 1 / Max 10, `POST /submit/{slug}` with `quantity=999999` and `quantity=abc`. For a `tel` field, `POST` `phone=not-a-phone`.
- **Expected:** 422 — value must be numeric and within range; phone must be 10 digits.
- **Actual:** All accepted and stored verbatim. The rule generated is only `required` / `nullable` — no `numeric`, `min`, `max`, `digits`, or `regex`.
- **Evidence:** `server-validation.spec.ts` TC-20, TC-22.
- **Impact:** Non-numeric / out-of-range numbers and malformed phone numbers enter the DB; downstream consumers (CSV export, reports) break or mislead.
- **Suggested fix:** Emit `numeric` (+ `min`/`max` from `minValue`/`maxValue`, or `digits_between` from `minLength`/`maxLength`) for `number`; `regex:/^\d{10}$/` (or configurable) for `tel`.

---

### DEF-07 — `select` / `radio` submitted value is not validated against the option list

- **Status:** Confirmed · **Severity:** Medium · **Priority:** P2
- **Component:** `FormSubmissionController::generateValidationRules()`
- **Steps to reproduce:** For a Dropdown with options Red/Green/Blue, `POST /submit/{slug}` with `colour=purple`.
- **Expected:** 422 — value must be one of the configured options.
- **Actual:** Accepted and stored. Rule is `required` / `nullable` only.
- **Evidence:** `server-validation.spec.ts` TC-24.
- **Impact:** Arbitrary values pollute single-choice fields; report grouping / analytics become unreliable; possible injection surface in downstream HTML tables.
- **Suggested fix:** `Rule::in($field->options)` for `select` and `radio`.

---

### DEF-08 — `date` field has no server-side `date` rule or range check

- **Status:** Confirmed — code + runtime (2026-09-01) · **Severity:** Medium · **Priority:** P2 · **Class:** A · **Test:** `submission/server-validation.spec.ts` TC-08b
- **Runtime result:** `POST /submit/{slug}` with `event_date=not-a-date` and with an out-of-range date (`2000-01-01` on a field limited to 2026) — **both accepted (HTTP 200) and stored verbatim**.
- **Component:** `generateValidationRules()` (date falls through to the generic branch) + `form-renderer.js` (`data-datecheck` only fires on `change`).
- **Expected:** Non-date strings and out-of-range dates are rejected server-side.
- **Actual:** `POST` with `dob=not-a-date` or a date outside `start_date`/`end_date` is accepted.
- **Suggested fix:** `date` rule + `after_or_equal`/`before_or_equal` from `start_date`/`end_date`.

---

### DEF-09 — State-changing endpoints have no authorization

- **Status:** Confirmed (code) · **Severity:** Medium · **Priority:** P2 · **Class:** C (confirm intent with product)
- **Component:** `routes/web.php`; `FormBuilderController::store / formStatus / deleteForm / unarchiveForm`
- **Detail:** `POST /forms` (create), `POST /form-status` (publish), `POST /forms/{id}` (archive), `POST /forms-unarchive/{id}` are callable by any anonymous client with a CSRF token. `formStatus()` also does no validation on `active`, so `is_published` can be set to any integer.
- **Note:** May be acceptable for this deliberately cut-down build (no auth subsystem). Flagged for confirmation; would be **High** in the full LMS.
- **Suggested fix:** `auth` + policy checks (as the unrouted `index()`/`edit()` methods already attempt).

---

### DEF-10 — `form_templates.form_structure` is stored double-JSON-encoded

- **Status:** Confirmed · **Severity:** Medium · **Priority:** P3 · **Class:** C
- **Detail:** `store()` passes the request's `form_structure` **string** into `FormTemplate::create()`, and the model's `'form_structure' => 'array'` cast JSON-encodes it again. Every raw-SQL consumer compensates with `if (is_string($decoded)) $decoded = json_decode($decoded, true);`.
- **Impact:** Fragile — any new consumer that forgets the second decode gets a string. This suite's DB layer decodes defensively (`decodeMaybeDoubleEncoded`).
- **Suggested fix:** `json_decode` the request value before `create()`, or drop the cast and store a clean string consistently.

---

### DEF-11 — Stored XSS via field label / title / description

- **Status:** Confirmed — code + runtime (2026-09-01) · **Severity:** Medium · **Priority:** P2 · **Class:** A · **Test:** `public-form/render.spec.ts` TC-40
- **Component:** `form-renderer.js` — `element.label`, `element.title`, `element.description` are interpolated into `innerHTML` unescaped.
- **Runtime result:** a form whose field label is `<img src=x onerror="window.__xssProof=1">` was created (via the unauthenticated `POST /forms`), published, and opened. On the public page a real `<img>` element rendered inside the field label **and its `onerror` handler executed** (`window.__xssProof === 1`). Non-destructive PoC only.
- **Detail:** The builder is unauthenticated in this build, so the payload is deliverable end-to-end by any client.
- **Suggested fix:** Escape interpolated user text (the builder already has an `esc()` helper; the renderer does not use it).

---

### DEF-12 — `routes/web.php` is registered twice

- **Status:** Confirmed · **Severity:** Low · **Priority:** P3 · **Class:** C
- **Component:** `RouteServiceProvider::boot()` (loads `base_path('routes/web.php')` at lines 49–51 **and** 53–56).
- **Impact:** Every route + named route is defined twice; middleware groups evaluate twice. Works (last wins) but wasteful and a latent source of "route already defined" style issues on upgrade.

---

### DEF-13 — Create vs Update validation rules diverge

- **Status:** Potential · **Severity:** Low · **Priority:** P3
- **Detail:** `FormBuilderController::update()` validates `success_message` `max:100`, `submit_btn_txt` `max:50`, `redirect_url` `url`, `redirect_method` `in:same_page,custom`. `store()` validates none of these. A form created with a 5,000-char success message is valid until its first edit, which then fails.
- **Suggested fix:** Share one rule set between `store()` and `update()`.

---

### DEF-15 — Post-submit success script throws; custom redirect never fires and the success toast never shows

- **Status:** Confirmed at runtime (reproduced 2026-09-01) · **Severity:** Medium · **Priority:** P2 · **Class:** A
- **Component:** `resources/views/show.blade.php` (script order) + `resources/views/layouts/admin.blade.php` / `includes/js.blade.php` (jQuery loaded in the footer)
- **Preconditions:** A published, accessible form; submit it (non-AJAX, i.e. a normal browser form submit).
- **Steps to reproduce:**
  1. Create a form with `redirect_method = custom`, `redirect_url = /forms`. Publish.
  2. Open `/submit/{slug}` in a browser, fill the required field, click Submit.
  3. Observe the browser after the confirmation page loads.
- **Expected:** A success toast appears; after ~2 s the browser navigates to the configured `redirect_url` (`/forms`).
- **Actual:** No toast. The browser stays on `/submit/{slug}` showing the "Form submitted!" heading; the redirect never happens. Console shows `Uncaught TypeError: Cannot read properties of undefined (reading 'extend')` (toastr) and, for single-submission forms, `Cannot read properties of null (reading 'addEventListener')`.
- **Root cause:**
  - `show.blade.php` loads `toastr.min.js` in the content section; jQuery is loaded later in the layout footer (`includes/js.blade.php`). toastr's UMD wrapper runs `factory(window.jQuery)` while `jQuery` is still `undefined` → `$.extend` throws → `window.toastr` is never defined.
  - The success block is `document.addEventListener('DOMContentLoaded', () => { toastr.success(...); setTimeout(() => location.href = redirect_to, 2000); })`. `toastr.success(...)` throws, so the `setTimeout` that performs the **custom redirect** on the very next line never executes.
  - Separately, `show.blade.php` runs `document.querySelector('#formSubmission').addEventListener('submit', …)` unguarded; on a single-submission confirmation page the `<form>` is absent → `null.addEventListener` throws.
- **Impact:** The **custom post-submit redirect feature is entirely non-functional**. The success toast never renders (single-submission forms still show a "Form submitted!" heading; multi-submission forms give the user no visible confirmation at all).
- **Evidence:** `evidence/defects/DEF-15-*.png`, trace from `settings-publish.spec.ts` TC-39.
- **Suggested fix:** Load jQuery before toastr; wrap `toastr.success(...)` in a guard / `try`; null-check `#formSubmission` before `addEventListener`.

---

### DEF-16 — Bootstrap 4 JS loaded before jQuery in the layout

- **Status:** Confirmed — code + runtime · **Severity:** Low · **Priority:** P3 · **Class:** C
- **Component:** `resources/views/layouts/admin.blade.php` loads `bootstrap/4.5.2/js/bootstrap.min.js` in `<head>`; jQuery is only loaded later via `includes/js.blade.php` in the footer.
- **Runtime result:** every page in every test logs `Uncaught TypeError: Bootstrap's JavaScript requires jQuery. jQuery must be included before Bootstrap's JavaScript.` Bootstrap 4's JS plugins therefore never initialise on any page.
- **Impact:** low for the in-scope surface (the builder wizard and the dashboard use their own JS / Bootstrap 5 via CDN); any BS4-dependent widget elsewhere would be dead. Contributes noise that makes real console errors harder to spot.
- **Suggested fix:** load jQuery before Bootstrap.

---

## Not defects (expected behaviour)

- Duplicate slug → 422 "This form url is already in use."
- "Form already submitted!" shown on **reload** when `multi_submission = 0` and a `userid = 1` submission exists (this is the Blade gate; see DEF-04 for the server-side gap).
- `isEverPublished` latching to 1 and disabling further edits after first publish.
- Title `maxlength=200` truncation in the builder input.
- Phone field stripping non-digits as you type.
