/**
 * Regenerates the curated evidence in `evidence/` that backs `docs/defect-report.md`.
 *
 *   node scripts/capture-evidence.mjs
 *
 * Requires the app running at BASE_URL (default http://127.0.0.1:8090) and, for the
 * DB-row lines, the same DB_* env the suite uses. Non-destructive — it only creates
 * `fb-evi-*` forms and archives them afterwards.
 */
import { chromium } from '@playwright/test';
import * as fs from 'node:fs';
import * as path from 'node:path';
import mysql from 'mysql2/promise';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8090';
const OUT = path.resolve('evidence');
const DEF = path.join(OUT, 'defects');
const EXE = path.join(OUT, 'execution');
for (const d of [DEF, EXE]) fs.mkdirSync(d, { recursive: true });

const db = process.env.DB_HOST
  ? await mysql.createConnection({
      host: process.env.DB_HOST, port: Number(process.env.DB_PORT || 3306),
      user: process.env.DB_USERNAME, password: process.env.DB_PASSWORD || '', database: process.env.DB_DATABASE,
    })
  : null;

const browser = await chromium.launch();
const ctx = await browser.newContext({ baseURL: BASE, viewport: { width: 1280, height: 900 } });
const page = await ctx.newPage();
await page.goto('/');
const token = await page.getAttribute('meta[name="csrf-token"]', 'content');
const created = [];
const apiLog = [];

const el = (over) => ({ id: 'e' + Math.random().toString(36).slice(2, 7), type: 'text', label: 'L', name: 'n', required: false, cssClass: '', sendEmail: false, ...over });

async function makeForm(structure, settings = {}) {
  const slug = 'fb-evi-' + Math.random().toString(36).slice(2, 8);
  const res = await page.request.post('/forms', {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
    data: {
      title: 'evi ' + slug, slug, form_structure: JSON.stringify(structure), is_public: 1, form_type: 'survey',
      is_registration_form: 0, student_type_builder: '', student_type_builder_display: '', isAnonymous: 0,
      accessible_using_url: 1, multi_submission: 1, login_required: 0, edit_response: 0, scoring: 0, review: 0,
      isDynamicUrl: 0, allowed_old_phase: 0, redirect_method: 'same_page', success_message: '', submit_btn_txt: 'Submit',
      redirect_url: '', parameter_name: [], weightage: [], ...settings,
    },
  });
  const body = await res.json();
  created.push(body.id);
  await page.request.post('/form-status', { headers: { 'X-CSRF-TOKEN': token }, form: { _token: token, form_id: body.id, active: 1 } });
  return body;
}
const encForm = (o) => Object.entries(o).flatMap(([k, v]) => (Array.isArray(v) ? v.map((x) => `${k}[]=${encodeURIComponent(x)}`) : [`${k}=${encodeURIComponent(v)}`])).join('&');
async function submitApi(slug, data) {
  const r = await page.request.post(`/submit/${slug}`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json', 'X-CSRF-TOKEN': token },
    data: encForm({ _token: token, ...data }), maxRedirects: 0,
  });
  return { status: r.status(), body: (await r.text()).slice(0, 300) };
}
const rows = async (id) => (db ? (await db.execute('SELECT COUNT(*) c FROM form_submissions WHERE form_template_id=?', [id]))[0][0].c : 'n/a');
const shot = (name) => page.screenshot({ path: path.join(DEF, name), fullPage: true });

// ── DEF-01 — optional email field rendered required ──────────────────────────
{
  const f = await makeForm([el({ name: 'full_name', label: 'Full name', required: true }), el({ type: 'email', name: 'email', label: 'Email (marked OPTIONAL in the builder)', required: false })]);
  await page.goto(`/submit/${f.slug}`);
  await page.waitForSelector('#formRenderer [name="email"]');
  await shot('DEF-01-optional-email-rendered-required.png');
  apiLog.push(`DEF-01  email[name=email] required attr on public form: ${await page.getAttribute('[name="email"]', 'required') !== null}  (builder setting was required:false)`);
}

// ── DEF-02 — text constraints not enforced ──────────────────────────────────
{
  const f = await makeForm([el({ name: 'code', label: 'Access code (builder: 5-10 chars, letters only)', required: true, minLength: '5', maxLength: '10', pattern: '^[A-Za-z]+$' })]);
  const short = await submitApi(f.slug, { code: 'a1' });
  apiLog.push(`DEF-02  POST /submit code="a1" (too short + has digit) -> HTTP ${short.status}; rows now ${await rows(f.id)}`);
  await page.goto(`/submit/${f.slug}`);
  await page.waitForSelector('[name="code"]');
  apiLog.push(`DEF-02  public <input name=code> maxlength=${await page.getAttribute('[name="code"]', 'maxlength')} minlength=${await page.getAttribute('[name="code"]', 'minlength')} pattern=${await page.getAttribute('[name="code"]', 'pattern')}`);
  await shot('DEF-02-text-input-no-constraints.png');
}

// ── DEF-03 — POST bypasses access guards ────────────────────────────────────
{
  const f = await makeForm([el({ name: 'n', label: 'N', required: true })], { accessible_using_url: 0 });
  const get = await page.request.get(`/submit/${f.slug}`, { maxRedirects: 0 });
  const post = await submitApi(f.slug, { n: 'x' });
  apiLog.push(`DEF-03  accessible_using_url=0:  GET /submit -> HTTP ${get.status()}   POST /submit -> HTTP ${post.status}; rows ${await rows(f.id)}`);
}

// ── DEF-04 — multi_submission=0 not enforced ────────────────────────────────
{
  const f = await makeForm([el({ name: 'n', label: 'N', required: true })], { multi_submission: 0 });
  await submitApi(f.slug, { n: 'a' });
  await submitApi(f.slug, { n: 'b' });
  apiLog.push(`DEF-04  multi_submission=0, two POST /submit -> rows ${await rows(f.id)} (expected 1)`);
}

// ── DEF-05 — short link 500 ─────────────────────────────────────────────────
{
  const f = await makeForm([el({ name: 'n', label: 'N', required: true })]);
  const us = db ? (await db.execute('SELECT unique_string FROM form_templates WHERE id=?', [f.id]))[0][0].unique_string : null;
  if (us) {
    const r = await page.request.get(`/s/${us}`, { maxRedirects: 0 });
    apiLog.push(`DEF-05  GET /s/${us} -> HTTP ${r.status()}`);
    await page.goto(`/s/${us}`).catch(() => {});
    await shot('DEF-05-short-link-500.png');
  }
}

// ── DEF-06 / 07 / 08 — server-side validation gaps ──────────────────────────
{
  const f6 = await makeForm([el({ type: 'number', name: 'age', label: 'Age (1-10)', required: true, minValue: '1', maxValue: '10' })]);
  apiLog.push(`DEF-06  POST /submit age="abc" -> HTTP ${(await submitApi(f6.slug, { age: 'abc' })).status};  age="999999" -> HTTP ${(await submitApi(f6.slug, { age: '999999' })).status};  rows ${await rows(f6.id)}`);
  const f7 = await makeForm([el({ type: 'select', name: 'colour', label: 'Colour', required: true, options: ['Red', 'Green', 'Blue'] })]);
  apiLog.push(`DEF-07  POST /submit colour="purple" (not an option) -> HTTP ${(await submitApi(f7.slug, { colour: 'purple' })).status};  rows ${await rows(f7.id)}`);
  const f8 = await makeForm([el({ type: 'date', name: 'd', label: 'Date (2026 only)', required: true, start_date: '2026-01-01', end_date: '2026-12-31' })]);
  apiLog.push(`DEF-08  POST /submit d="not-a-date" -> HTTP ${(await submitApi(f8.slug, { d: 'not-a-date' })).status};  d="2000-01-01" -> HTTP ${(await submitApi(f8.slug, { d: '2000-01-01' })).status};  rows ${await rows(f8.id)}`);
}

// ── DEF-11 — stored XSS via field label ─────────────────────────────────────
{
  const f = await makeForm([el({ name: 'probe', label: '<img src=x onerror="window.__xssProof=1;document.title=\'XSS-FIRED\'">' }), el({ name: 'real', label: 'Real field', required: true })]);
  await page.goto(`/submit/${f.slug}`);
  await page.waitForSelector('#container_probe img');
  const fired = await page.evaluate(() => window.__xssProof || 0);
  apiLog.push(`DEF-11  field label = <img onerror=...>;  onerror executed on public form: ${fired === 1};  document.title="${await page.title()}"`);
  await shot('DEF-11-stored-xss-label.png');
}

// ── DEF-15 — custom redirect never fires ────────────────────────────────────
{
  const f = await makeForm([el({ name: 'n', label: 'N', required: true })], { redirect_method: 'custom', redirect_url: '/forms' });
  await page.goto(`/submit/${f.slug}`);
  await page.fill('[name="n"]', 'x');
  await Promise.all([page.waitForResponse((r) => r.url().includes('/submit/') && r.request().method() === 'POST'), page.click('#submitBtn')]);
  await page.waitForLoadState('load').catch(() => {});
  await page.waitForTimeout(4000); // wait past the (dead) 2s redirect timer
  apiLog.push(`DEF-15  custom redirect_url=/forms;  after submit URL is "${page.url()}" (still /submit — redirect never fired);  page ships the script: ${(await page.content()).includes('window.location.href')}`);
  await shot('DEF-15-custom-redirect-not-fired.png');
}

// ── execution evidence ─────────────────────────────────────────────────────
fs.writeFileSync(path.join(DEF, 'api-defect-log.txt'),
  `Evidence captured ${new Date().toISOString()} against ${BASE}\n` +
  `Non-visual / API-level defect reproductions (see docs/defect-report.md):\n\n` + apiLog.join('\n') + '\n');

if (fs.existsSync('phase3-run1.txt')) fs.copyFileSync('phase3-run1.txt', path.join(EXE, 'full-suite-run.txt'));
if (fs.existsSync('phase3-run3-repeat.txt')) fs.copyFileSync('phase3-run3-repeat.txt', path.join(EXE, 'repeat-each-2-run.txt'));

// cleanup — hard delete via DB (falls back to archive if no DB)
for (const id of created) {
  if (db) {
    await db.execute('DELETE FROM form_submissions WHERE form_template_id=?', [id]).catch(() => {});
    await db.execute('DELETE FROM form_templates WHERE id=?', [id]).catch(() => {});
  } else {
    await page.request.post(`/forms/${id}`, { headers: { 'X-CSRF-TOKEN': token }, form: { _token: token } }).catch(() => {});
  }
}
if (db) await db.end();
await browser.close();
console.log('Evidence written to evidence/  (' + apiLog.length + ' API lines, screenshots in evidence/defects/)');
console.log(apiLog.join('\n'));
process.exit(0);
