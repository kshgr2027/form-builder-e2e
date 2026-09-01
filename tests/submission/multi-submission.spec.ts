import { test, expect, requireDb } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import { minimalTextForm } from '../../src/data/form-structures';

test.describe('Multiple submission setting', () => {
  test('TC-27 multi_submission = 1 allows repeat submissions', async ({ forms, submissionsApi, db }) => {
    requireDb(db);
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { multi_submission: 1 },
      hint: 'multi-on',
    });

    await submissionsApi.submit(form.slug, { full_name: 'A' });
    await submissionsApi.submit(form.slug, { full_name: 'B' });

    expect(await db.formSubmissions.countForTemplate(form.id)).toBe(2);
  });

  test('TC-28 @known-defect multi_submission = 0 is NOT enforced server-side (DEF-04)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    requireDb(db);
    knownDefect('DEF-04', 'store() creates a submission unconditionally; single-submission is a Blade-only check');
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { multi_submission: 0 },
      hint: 'multi-off',
    });

    const first = await submissionsApi.submit(form.slug, { full_name: 'A' });
    const second = await submissionsApi.submit(form.slug, { full_name: 'B' });

    expect(first.ok()).toBeTruthy();
    expect(second.ok()).toBeTruthy();
    // Expected (if enforced): 1. Actual: 2.
    expect(await db.formSubmissions.countForTemplate(form.id)).toBe(2);
  });

  test('TC-27b multi_submission = 0 hides the form on reload after first submit (UI gate)', async ({
    forms,
    submissionsApi,
    publicFormPage,
    db,
  }) => {
    requireDb(db);
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { multi_submission: 0 },
      hint: 'multi-off-ui',
    });

    await submissionsApi.submit(form.slug, { full_name: 'A' });

    await publicFormPage.openRaw(form.slug);
    // The Blade gate renders "Form already submitted!" and no form.
    await expect(publicFormPage.alreadySubmitted).toBeVisible();
  });
});
