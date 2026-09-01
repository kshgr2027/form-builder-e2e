import { test as base, expect, APIRequestContext } from '@playwright/test';
import { Env, loadEnv } from '../utils/env';
import { Db } from '../db/db';
import { FormsApi, FormSettings, CreatedForm } from '../api/FormsApi';
import { SubmissionsApi } from '../api/SubmissionsApi';
import { FormStructure } from '../data/form-structure';
import { uniqueSlug } from '../utils/slug';
import { FormBuilderPage } from '../pages/builder/FormBuilderPage';
import { FormsDashboardPage } from '../pages/dashboard/FormsDashboardPage';
import { PublicFormPage } from '../pages/public/PublicFormPage';

export interface CreateTestFormOptions {
  structure: FormStructure;
  settings?: FormSettings;
  /** default: true */
  publish?: boolean;
  hint?: string;
}

export interface TestForm extends CreatedForm {
  id: number;
  structure: FormStructure;
  uniqueString?: string;
}

export interface FormsFactory {
  create(opts: CreateTestFormOptions): Promise<TestForm>;
  /** Register a form created another way (e.g. through the builder UI) for cleanup. */
  adopt(id: number): void;
  /** Look up a form id by slug (requires DB). */
  idBySlug(slug: string): Promise<number>;
}

type WorkerFixtures = {
  env: Env;
  db: Db | null;
};

type TestFixtures = {
  api: APIRequestContext;
  formsApi: FormsApi;
  submissionsApi: SubmissionsApi;
  forms: FormsFactory;
  builderPage: FormBuilderPage;
  dashboardPage: FormsDashboardPage;
  publicFormPage: PublicFormPage;
};

export const test = base.extend<TestFixtures, WorkerFixtures>({
  env: [async ({}, use) => { await use(loadEnv()); }, { scope: 'worker' }],

  db: [
    async ({ env }, use) => {
      if (!env.hasDb || !env.db) {
        await use(null);
        return;
      }
      const db = await Db.connect(env.db);
      await use(db);
      await db.close();
    },
    { scope: 'worker' },
  ],

  // A dedicated APIRequestContext so cookies/CSRF are isolated per test.
  api: async ({ playwright, env }, use) => {
    const ctx = await playwright.request.newContext({ baseURL: env.baseUrl, ignoreHTTPSErrors: true });
    await use(ctx);
    await ctx.dispose();
  },

  formsApi: async ({ api }, use) => {
    await use(new FormsApi(api));
  },

  submissionsApi: async ({ api }, use) => {
    await use(new SubmissionsApi(api));
  },

  forms: async ({ formsApi, db }, use, testInfo) => {
    const created: number[] = [];

    const factory: FormsFactory = {
      async create(opts) {
        const slug = uniqueSlug(testInfo, opts.hint);
        const c = await formsApi.createForm({ slug, structure: opts.structure, settings: opts.settings });
        created.push(c.id);
        if (opts.publish ?? true) await formsApi.publish(c.id);

        let uniqueString: string | undefined;
        if (db) uniqueString = (await db.formTemplates.findById(c.id))?.unique_string ?? undefined;

        return { ...c, structure: opts.structure, uniqueString };
      },
      adopt(id) {
        if (!created.includes(id)) created.push(id);
      },
      async idBySlug(slug) {
        if (!db) throw new Error('forms.idBySlug requires a DB connection');
        const row = await db.formTemplates.findBySlug(slug);
        if (!row) throw new Error(`No form_templates row for slug ${slug}`);
        return row.id;
      },
    };

    await use(factory);

    // Cleanup: prefer a hard DB delete; fall back to archiving via API.
    for (const id of created) {
      try {
        if (db) await db.formTemplates.deleteById(id);
        else await formsApi.archive(id);
      } catch {
        /* best effort */
      }
    }
  },

  builderPage: async ({ page }, use) => { await use(new FormBuilderPage(page)); },
  dashboardPage: async ({ page }, use) => { await use(new FormsDashboardPage(page)); },
  publicFormPage: async ({ page }, use) => { await use(new PublicFormPage(page)); },
});

export { expect };

/** Skip a test (or block) when no DB is configured. */
export function requireDb(db: Db | null): asserts db is Db {
  test.skip(db === null, 'DB not configured (set DB_* in .env) — persistence assertions skipped');
}
