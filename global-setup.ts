import { request } from '@playwright/test';
import { loadEnv } from './src/utils/env';
import { Db } from './src/db/db';

/**
 * Pre-flight: verify the app is reachable and (if configured) the DB is usable.
 * Fails loudly with actionable messages instead of letting every test time out.
 */
async function main(): Promise<void> {
  const env = loadEnv();
  const line = '─'.repeat(64);
  console.log(`\n${line}\n Form Builder E2E — pre-flight\n${line}`);
  console.log(` BASE_URL : ${env.baseUrl}`);
  console.log(` DB       : ${env.hasDb ? `${env.db!.user}@${env.db!.host}:${env.db!.port}/${env.db!.database}` : 'not configured (persistence assertions will skip)'}`);

  // ── App reachability ──────────────────────────────────────────────────────
  const ctx = await request.newContext({ baseURL: env.baseUrl, ignoreHTTPSErrors: true });
  const deadline = Date.now() + 30_000;
  let lastErr = '';
  for (;;) {
    try {
      const res = await ctx.get('/', { timeout: 5_000 });
      if (res.ok()) {
        const html = await res.text();
        if (!/Form Builder/i.test(html) && !/formTitle/.test(html)) {
          console.warn(' WARN: `/` responded but does not look like the builder wizard.');
        }
        break;
      }
      lastErr = `HTTP ${res.status()}`;
    } catch (e) {
      lastErr = (e as Error).message;
    }
    if (Date.now() > deadline) {
      await ctx.dispose();
      throw new Error(
        `App not reachable at ${env.baseUrl} (${lastErr}).\n` +
          `Start it with:  cd ${env.appDir} && php artisan serve --host=127.0.0.1 --port=8000\n` +
          `and set APP_URL / APP_DEBUG in the Laravel .env (APP_DEBUG=true is required for the 500-assertion tests).`,
      );
    }
    await new Promise((r) => setTimeout(r, 1_000));
  }
  const forms = await ctx.get('/forms', { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
  console.log(` GET /forms (xhr) : HTTP ${forms.status()}`);
  await ctx.dispose();

  // ── DB reachability ───────────────────────────────────────────────────────
  if (env.hasDb && env.db) {
    let db: Db;
    try {
      db = await Db.connect(env.db);
    } catch (e) {
      throw new Error(
        `DB configured but unreachable: ${(e as Error).message}\n` +
          `Fix DB_* in .env, or remove them to run without persistence assertions.`,
      );
    }
    for (const table of ['form_templates', 'form_submissions']) {
      const rows = await db.query<any[]>(`SELECT COUNT(*) AS c FROM \`${table}\``).catch(() => null);
      if (rows === null) {
        await db.close();
        throw new Error(`Table \`${table}\` missing. Import demo.sql:  mysql -u<user> -p ${env.db.database} < ${env.appDir}/demo.sql`);
      }
      console.log(` DB table ${table.padEnd(17)} : ${rows[0].c} row(s)`);
    }
    // Leftovers from a previous crashed run.
    const purged = await db.formTemplates.purgeE2e('fb-e2e');
    if (purged) console.log(` Purged ${purged} stale fb-e2e form(s) from a prior run.`);
    await db.close();
  }

  console.log(`${line}\n`);
}

export default main;
