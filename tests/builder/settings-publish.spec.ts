import { test, expect, requireDb } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import { uniqueSlug, titleFromSlug } from '../../src/utils/slug';
import { minimalTextForm } from '../../src/data/form-structures';
import * as f from '../../src/data/fields';

test.describe('Form settings & publish', () => {
  test('TC-29 custom success message is configured, persisted and returned on submit', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    const message = 'Thanks - your response is saved!';
    const form = await forms.create({
      structure: [f.text({ name: 'full_name', label: 'Name', required: true })],
      settings: { success_message: message },
      hint: 'set-msg',
    });

    // Persisted on the template
    if (db) expect((await db.formTemplates.findById(form.id))!.success_message).toBe(message);

    // The server echoes the custom message back on submit (AJAX response — deterministic;
    // the toastr toast that would render it in-browser is covered by TC-15's E2E submit).
    const res = await submissionsApi.submit(form.slug, { full_name: 'Someone' });
    expect(res.ok()).toBeTruthy();
    expect((await res.json()).message).toBe(message);
  });

  test('TC-30 custom submit button text renders on the public form', async ({ forms, publicFormPage }) => {
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { submit_btn_txt: 'Send my answers' },
      hint: 'set-btn',
    });

    await publicFormPage.open(form.slug);
    await expect(publicFormPage.submitButton).toHaveText('Send my answers');
  });

  test('TC-39 @known-defect custom redirect is configured server-side but never fires client-side (DEF-15)', async ({
    forms,
    publicFormPage,
    db,
  }) => {
    knownDefect('DEF-15', 'post-submit success script throws (toastr loaded before jQuery); the redirect setTimeout after it never runs');
    const form = await forms.create({
      structure: [f.text({ name: 'full_name', label: 'Name', required: true })],
      settings: { redirect_method: 'custom', redirect_url: '/forms' },
      hint: 'set-redirect',
    });

    // Server side is correct: the setting is persisted...
    if (db) {
      const row = await db.formTemplates.findById(form.id);
      expect(row!.redirect_method).toBe('custom');
      expect(row!.redirect_url).toBe('/forms');
    }

    await publicFormPage.open(form.slug);
    await publicFormPage.fillField('full_name', 'Someone');
    await publicFormPage.submit();

    // ...and the confirmation page even ships the redirect script...
    await publicFormPage.expectSubmitted();
    expect(await publicFormPage.page.content()).toMatch(/window\.location\.href\s*=\s*["']\\?\/forms["']/);

    // ...but the browser never actually navigates (the script throws before the setTimeout).
    await expect(publicFormPage.page.waitForURL('**/forms', { timeout: 4_000 })).rejects.toThrow();
    await expect(publicFormPage.page).toHaveURL(/\/submit\//);
  });

  test('TC-31 publish via the wizard "Publish now" button sets is_published + isEverPublished', async ({
    builderPage,
    forms,
    db,
  }, testInfo) => {
    await builderPage.open();
    await builderPage.setTitle(titleFromSlug(uniqueSlug(testInfo, 'pub-wizard')));
    await builderPage.goToBuilderStep();
    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('Field one');
    await builderPage.goToSettingsStep();

    const { slug } = await builderPage.createForm();
    await builderPage.publishNow();

    if (db) {
      const id = await forms.idBySlug(slug);
      forms.adopt(id);
      const row = await db.formTemplates.findById(id);
      expect(row!.is_published).toBe(1);
      expect(row!.isEverPublished).toBe(1);
    }
  });

  test('TC-31b a form published via the wizard appears under the dashboard "Published" filter', async ({
    builderPage,
    dashboardPage,
    forms,
    db,
  }, testInfo) => {
    requireDb(db);
    const title = titleFromSlug(uniqueSlug(testInfo, 'pub-dash'));

    await builderPage.open();
    await builderPage.setTitle(title);
    await builderPage.goToBuilderStep();
    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('Field one');
    await builderPage.goToSettingsStep();
    const { slug } = await builderPage.createForm();
    await builderPage.publishNow();
    forms.adopt(await forms.idBySlug(slug));

    await dashboardPage.open();
    await dashboardPage.applyFilter('published');
    await dashboardPage.searchFor(title);
    await dashboardPage.expectListed(title, true);
    expect(await dashboardPage.statusOf(title)).toMatch(/Published/i);
  });
});
