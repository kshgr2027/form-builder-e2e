import { loadEnv } from './src/utils/env';
import { Db } from './src/db/db';

/**
 * Safety net: remove anything this suite created that a per-test fixture missed
 * (crash, Ctrl-C, DB not available at test time). Matches `slug LIKE 'fb-e2e-%'`.
 */
async function main(): Promise<void> {
  const env = loadEnv();
  if (!env.hasDb || !env.db) return;

  let db: Db;
  try {
    db = await Db.connect(env.db);
  } catch {
    return;
  }
  const removed = await db.formTemplates.purgeE2e('fb-e2e').catch(() => 0);
  if (removed) console.log(`\n[teardown] purged ${removed} residual fb-e2e form(s).`);
  await db.close();
}

export default main;
