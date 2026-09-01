# Coverage Matrix

## Requirement → test cases

| Assignment requirement | Test cases | Spec files |
|---|---|---|
| Positive scenarios | TC-01, 07, 07b, 08, 09, 15, 16, 26, 26b, 27, 27b, 29, 30, 31, 31b | builder/*, public-form/*, submission/* |
| Negative scenarios | TC-02, 04, 05, 06, 08b, 14, 14b, 17, 18, 20, 22, 24, 25, 28, 33, 34, 35, 36, 37, 38, 40 | builder/create, submission/*, access-control/*, public-form/render |
| Boundary / edge | TC-03, 19, 19b, 21, 23 | builder/create, public-form/client-validation |
| E2E flows | TC-01, 15, 31, 31b, 32, 32b | builder/*, publish/dashboard |
| Business logic (not just UI) | TC-15, 16, 23, 26b, 27, 28, 36, 37 | submission/*, access-control/* |
| Permissions / access control | TC-33, 34, 35, 36, 37, 38 | access-control/* |
| Persisted-data validation | TC-01, 07, 10, 11, 12, 14b, 15, 16, 23, 26b, 27, 28, 29, 31, 32 | DB assertions via repositories |
| POM, no selectors in tests | *all* — selectors live in `src/pages/**`, SQL in `src/db/**` | — |
| Independent + parallel | *all* — per-test `fb-e2e-` form + teardown | fixtures/test.ts |
| No hard waits | *all* — `waitForResponse` / web-first assertions | — |
| Defect documentation | DEF-01…13 | docs/defect-report.md |
| README / setup / CI | — | README.md, .github/workflows/ci.yml |

## Defect → covering test

> **Runtime status (2026-09-01):** every `@known-defect` test below **passed against the live app**,
> i.e. each reproduced its documented buggy behaviour. See `docs/defect-report.md` for the A/B/C
> classification and `docs/execution-summary.md` for the run.

| Defect | Covering test(s) | Runtime status |
|---|---|---|
| DEF-01 optional email/phone forced required | TC-18 | ✅ reproduced (`@known-defect`) |
| DEF-02 text constraints dropped / not enforced | TC-23 (client + server) | ✅ reproduced (`@known-defect`) |
| DEF-03 POST /submit ignores access guards | TC-36, TC-37 | ✅ reproduced (`@known-defect`) |
| DEF-04 multi_submission=0 not enforced | TC-28 | ✅ reproduced (`@known-defect`) |
| DEF-05 short link 500 | TC-38 | ✅ reproduced (`@known-defect`) |
| DEF-06 number/tel no server validation | TC-20, TC-22 | ✅ reproduced (`@known-defect`) |
| DEF-07 select/radio value not whitelisted | TC-24 | ✅ reproduced (`@known-defect`) |
| DEF-08 date no server rule | TC-08b | ✅ reproduced (`@known-defect`) |
| DEF-09 no auth on state-changing endpoints | — | ⚠️ observed (every test's create/publish/archive is unauthenticated); class C |
| DEF-10 form_structure double-encoded | — | ⚠️ observed (DB layer must double-decode); class C |
| DEF-11 stored XSS via label/title/description | TC-40 | ✅ reproduced (`@known-defect`) |
| DEF-12 web.php registered twice | — | static only |
| DEF-13 create vs update validation divergence | — | static only (update path out of scope) |
| DEF-15 custom redirect never fires (toastr before jQuery) | TC-39 | ✅ discovered + reproduced at runtime (`@known-defect`) |

## Field-type coverage (public form)

| Type | Render | Client validation | Server validation | Persistence |
|---|---|---|---|---|
| text | TC-07b, 09 | TC-14, 23 | TC-23 | TC-15, 23 |
| textarea | TC-07b | TC-14 | — | TC-15 |
| number | TC-07b | TC-21 | TC-22 | TC-22 |
| email | TC-07b, 18 | TC-18 | TC-17 | TC-15 |
| tel | TC-07b | TC-19, 19b | TC-20 | TC-15 |
| select | TC-07b | TC-14 | TC-24 | TC-15 |
| radio | TC-07b | TC-14 | (DEF-07) | TC-15 |
| checkbox | TC-07b | TC-25 | TC-25 | TC-16 |
| date | TC-07 | TC-21 (range, on change) | TC-08b | — |
| title/description/new_line/hidden/page_break | TC-07 | n/a | n/a | TC-07 |

Field-label HTML handling (all types): **TC-40** — an `<img onerror>` payload in a label executes on the public form (DEF-11).
