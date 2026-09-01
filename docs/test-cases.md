# Test Cases — Form Builder E2E

**Type:** Positive / Negative / Boundary / E2E · **Pri:** P1 critical, P2 high, P3 medium
**Auto:** ✅ automated · ⚠️ partially / manual-leaning · ❌ manual only

All automated tests provision their own `fb-e2e-…` form and assert against their own DB rows.
"DB" in *Expected* means the assertion also checks `form_templates` / `form_submissions`.

| TC | Title | Area | Type | Steps (summary) | Expected | Pri | Auto | Spec |
|---|---|---|---|---|---|---|---|---|
| TC-01 | Create form via wizard (happy path) | Builder | E2E | Title → add Text field → settings → **Create form** | Step 4 shown; share link `/submit/{slug}`; DB row with title + field in `form_structure` | P1 | ✅ | builder/create |
| TC-02 | Blank title blocks step 1 | Builder | Neg | Clear title → Next | Stays on step 1; `#formTitle.is-invalid` | P2 | ✅ | builder/create |
| TC-03 | Title capped at 200 chars | Builder | Bound | Type 250 chars | Value truncated to 200; counter "200" | P3 | ✅ | builder/create |
| TC-04 | Illegal title chars rejected (server) | Builder | Neg | `POST /forms` title `"Question 1: A/B!"` | 422, message mentions title | P2 | ✅ | builder/create |
| TC-05 | Empty form rejected | Builder | Neg | `POST /forms` `form_structure=[]` | 422 "add at least one field" | P2 | ✅ | builder/create |
| TC-06 | Duplicate slug rejected | Builder | Neg | `POST /forms` same slug twice | 2nd → 422 "already in use" | P2 | ✅ | builder/create |
| TC-07 | Add every in-scope field type | Builder | Pos | Add all 14 palette types → save | Each server type present in `form_structure` (DB) | P1 | ✅ | builder/fields |
| TC-07b | In-scope types render correct controls | Public | Pos | Open public form built from `contactForm()` | Each field renders as text/email/tel/number/select/radio/checkbox/textarea | P1 | ✅ | public-form/render |
| TC-08 | Field label round-trips to public form | Public | E2E | Set label in builder → publish → open public form | Label text visible in `#container_<name>` | P1 | ✅ | public-form/render |
| TC-09 | Required text field renders `required` + `*` | Public | Pos | Build required + optional text; open public form | Required one has `required` attr + star; optional one does not | P1 | ✅ | public-form/render |
| TC-10 | Duplicate field | Builder | Pos | Label A, B → duplicate A | 3 cards, order A, A, B; duplicate has distinct `name` (DB) | P2 | ✅ | builder/fields |
| TC-11 | Delete field | Builder | Pos | Label KeepMe, DeleteMe → delete DeleteMe | 1 card; `form_structure` has KeepMe, not DeleteMe | P2 | ✅ | builder/fields |
| TC-12 | Reorder fields (arrows) | Builder | Pos | Label First, Second → move First down | Canvas order Second/First; `form_structure` order `[Second, First]` (DB) | P2 | ✅ | builder/fields |
| TC-14 | Required field blocks submit (client) | Public | Neg | Open published form → submit empty | Inline "required"; **no** `POST /submit` fires | P1 | ✅ | public-form/client-validation |
| TC-14b | Client-blocked submit persists nothing | Submission | Neg | Submit empty → check DB | `COUNT(*) = 0` for the form | P1 | ✅ | submission/submit-persist |
| TC-15 | Valid submission persists exact data | Submission | E2E | Fill contactForm, submit | Success toast; `form_submissions` row; `submission_data` matches; `userid = 1` | P1 | ✅ | submission/submit-persist |
| TC-16 | Checkbox persists as JSON array | Submission | Pos | Check 2 options, submit | `submission_data.interests == ["News","Offers"]` | P2 | ✅ | submission/submit-persist |
| TC-17 | Invalid email rejected (server) | Submission | Neg | `POST /submit` `email=not-an-email` | 422, body mentions email | P1 | ✅ | submission/server-validation |
| TC-18 | **DEF-01** optional email rendered required | Public | Neg | Build optional-email form; open public form | Email input **has** `required` + `*`; submit w/o email blocked | P1 | ✅ | public-form/render |
| TC-19 | Phone must be 10 digits (client) | Public | Bound | Enter `12345`, submit | Inline "10 digits"; submit blocked | P2 | ✅ | public-form/client-validation |
| TC-19b | Phone strips non-digits while typing | Public | Bound | Type `98a7b6c5d4e3` | Value becomes `9876543` | P3 | ✅ | public-form/client-validation |
| TC-20 | **DEF-06** phone accepts non-10-digit (server) | Submission | Neg | `POST /submit` `phone=not-a-phone` | 200 + stored verbatim (buggy; asserted) | P2 | ✅ | submission/server-validation |
| TC-21 | Number range enforced (client) | Public | Bound | Field 1–10; enter 50, blur | Inline "between 1 and 10" | P2 | ✅ | public-form/client-validation |
| TC-22 | **DEF-06** number accepts OOR / non-numeric (server) | Submission | Neg | `POST /submit` `quantity=999999`, `quantity=abc` | Both stored (buggy; asserted) | P2 | ✅ | submission/server-validation |
| TC-23 | **DEF-02** text constraints not enforced | Public + Submission | Neg | Field 5–10 letters; submit `a1`, `ab`, 400-char, `abc123` | No `min/max/pattern` attr; all accepted + persisted | P1 | ✅ | client-validation, server-validation |
| TC-24 | **DEF-07** select accepts value outside options | Submission | Neg | `POST /submit` `colour=purple` | 200 + stored (buggy; asserted) | P2 | ✅ | submission/server-validation |
| TC-25 | Required checkbox group, none selected | Public + Submission | Neg | Submit with group untouched | Client: "select at least one"; API: 422 (`array\|min:1`) | P2 | ✅ | client-validation, server-validation |
| TC-26 | Conditional field hide → show → required toggle | Public | Pos | `details` shown only when `need_details = Yes` | Hidden by default; visible + required on "Yes"; hidden on "No" | P2 | ✅ | public-form/conditional |
| TC-26b | Submit with condition unmet | Public | Pos | `need_details = No`, submit | Success; hidden field not required; `submission_data.need_details = "No"` | P2 | ✅ | public-form/conditional |
| TC-27 | `multi_submission = 1` allows repeats | Submission | Pos | Submit twice | `COUNT(*) = 2` | P2 | ✅ | submission/multi-submission |
| TC-27b | `multi_submission = 0` UI gate on reload | Public | Pos | Submit once → reload public page | "Form already submitted!" shown, no form | P2 | ✅ | submission/multi-submission |
| TC-28 | **DEF-04** `multi_submission = 0` not enforced (server) | Submission | Neg | `POST /submit` twice | Both succeed; `COUNT(*) = 2` (buggy; asserted) | P1 | ✅ | submission/multi-submission |
| TC-29 | Custom success message | Settings | Pos | Set message → publish → submit | Toast shows the message; `success_message` column matches | P3 | ✅ | builder/settings-publish |
| TC-30 | Custom submit button text | Settings | Pos | Set text → publish | `#submitBtn` text matches | P3 | ✅ | builder/settings-publish |
| TC-31 | Publish via wizard "Publish now" | Publish | E2E | Finish wizard → Publish now | Badge "Published"; DB `is_published=1`, `isEverPublished=1` | P1 | ✅ | builder/settings-publish |
| TC-31b | Published form appears under dashboard filter | Publish | E2E | Publish via wizard → dashboard "Published" | Form listed; ribbon "Published" | P2 | ✅ | builder/settings-publish |
| TC-32 | Dashboard publish toggle flips `is_published`; latch | Publish | E2E | Toggle `.fb-pub` on then off | `is_published` 0→1→0; `isEverPublished` stays 1 | P2 | ✅ | publish/dashboard |
| TC-32b | Filter counts reflect a newly published form | Publish | E2E | Toggle on → check counts | Draft count decreases by 1; form under "Published" | P2 | ✅ | publish/dashboard |
| TC-33 | Unknown slug → 404 | Access | Neg | `GET /submit/does-not-exist` | 404 | P2 | ✅ | access-control/access |
| TC-34 | `accessible_using_url = 0` → 403 on GET | Access | Neg | `GET /submit/{slug}` | 403 | P2 | ✅ | access-control/access |
| TC-35 | Archived form (`active = 0`) → 403 on GET | Access | Neg | `GET /submit/{slug}` | 403 | P2 | ✅ | access-control/access |
| TC-36 | **DEF-03** unpublished form viewable + submittable | Access | Neg | Draft + accessible; GET + POST `/submit` | GET 200, POST stores a row (buggy; asserted) | P1 | ✅ | access-control/access |
| TC-37 | **DEF-03** POST accepts not-accessible form | Access | Neg | `accessible_using_url = 0`; GET 403 then POST | POST 200 + row stored (buggy; asserted) | P1 | ✅ | access-control/access |
| TC-38 | **DEF-05** short link 500 | Access | Neg | `GET /s/{unique_string}` for an active form | HTTP 500 (buggy; asserted; needs `APP_DEBUG=true`) | P1 | ✅ | access-control/short-link |
| TC-39 | **DEF-15** custom redirect configured but never fires client-side | Settings | Neg | `redirect_method=custom`, `redirect_url=/forms`; fill + submit | Config persisted; the redirect `<script>` ships; but the browser stays on `/submit/{slug}` (toastr throws before the redirect `setTimeout`) — asserted | P2 | ✅ | builder/settings-publish |
| TC-40 | **DEF-11** stored XSS via field label | Public | Neg | Label `<img src=x onerror="window.__xssProof=1">`; open public form | `<img>` renders as HTML **and** `onerror` runs (`__xssProof===1`); form still renders (buggy; asserted) | P2 | ✅ | public-form/render |
| TC-08b | **DEF-08** date field: no server date/range rule | Submission | Neg | `POST /submit` `event_date=not-a-date` and out-of-range | Both accepted + stored verbatim (buggy; asserted) | P2 | ✅ | submission/server-validation |

**Counts:** 49 automated tests across 12 spec files (TC-01…TC-40 + sub-cases). All designed in-scope
scenarios are automated. **Executed against a live app on 2026-09-01: 49/49 pass; 98/98 with
`--repeat-each=2`.** Full detail in `docs/execution-summary.md`.
