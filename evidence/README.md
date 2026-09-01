# Evidence

Curated proof for the claims in [`../docs/defect-report.md`](../docs/defect-report.md) and
[`../docs/execution-summary.md`](../docs/execution-summary.md). Regenerate with
`node scripts/capture-evidence.mjs` (app must be running; `DB_*` set for the row counts).

## `defects/`

| File | Backs | Shows |
|---|---|---|
| `DEF-01-optional-email-rendered-required.png` | DEF-01 | Public form where the Email field — set **optional** in the builder — renders with a red `*` and the `required` attribute. |
| `DEF-02-text-input-no-constraints.png` | DEF-02 | Public Text input for a field configured "5–10 chars, letters only" — the `<input>` has no `minlength` / `maxlength` / `pattern`. |
| `DEF-05-short-link-500.png` | DEF-05 | `GET /s/{unique_string}` returning the Laravel HTTP 500 page (`Undefined variable $role`). |
| `DEF-11-stored-xss-label.png` | DEF-11 | Public form built from a field whose **label** is `<img src=x onerror=…>` — the tab title has changed to `XSS-FIRED`, proving the handler executed. |
| `DEF-15-custom-redirect-not-fired.png` | DEF-15 | After submitting a form with `redirect_method = custom`, the browser is still on `/submit/{slug}` — the redirect never fired. |
| `api-defect-log.txt` | DEF-01…08, 11, 15 | The HTTP status + resulting `form_submissions` row counts for each API-level reproduction (DEF-03/04/06/07/08 have no meaningful screenshot). |

## `execution/`

| File | Shows |
|---|---|
| `full-suite-run.txt` | `npx playwright test` — 49/49 pass (2 workers). |
| `repeat-each-2-run.txt` | `npx playwright test --repeat-each=2` — 98/98 pass, no flakes. |

## `screenshots/`, `traces/`

Empty by default (`.gitkeep`). Playwright drops failure screenshots / retry traces here via
`playwright.config.ts` (`screenshot: 'only-on-failure'`, `trace: 'on-first-retry'`).
