import { test, expect } from '../../src/fixtures/test';
import { uniqueSlug, titleFromSlug } from '../../src/utils/slug';
import * as f from '../../src/data/fields';

/** One-field filler for `createRaw` negative tests where the structure is not the subject. */
const oneField = () => [f.text({ name: 't', label: 'T' })];

test.describe('Form Builder — creation', () => {
  test('TC-01 create a form through the wizard (happy path) + persists', async ({ builderPage, forms, db }, testInfo) => {
    const title = titleFromSlug(uniqueSlug(testInfo, 'create-happy'));

    await builderPage.open();
    await builderPage.setTitle(title);
    await builderPage.goToBuilderStep();
    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('Full name');
    await builderPage.optionsPanel.setRequired(true);
    await builderPage.goToSettingsStep();

    const { slug, shareUrl } = await builderPage.createForm();
    expect(slug).toMatch(/^fb-e2e-create-happy-/);
    expect(shareUrl).toContain(`/submit/${slug}`);

    if (db) {
      const id = await forms.idBySlug(slug);
      forms.adopt(id);
      const row = await db.formTemplates.findById(id);
      expect(row!.title).toBe(title);
      const structure = await db.formTemplates.structureBySlug(slug);
      expect(structure!.some((el) => el.label === 'Full name' && el.type === 'text' && el.required)).toBe(true);
    }
  });

  test('TC-02 blank title blocks step 1 (client)', async ({ builderPage }) => {
    await builderPage.open();
    await builderPage.setTitle('');
    await builderPage.next();
    await builderPage.expectOnStep(1);
    await expect(builderPage.titleInput).toHaveClass(/is-invalid/);
  });

  test('TC-03 title is capped at 200 characters', async ({ builderPage }) => {
    await builderPage.open();
    await builderPage.setTitle('a'.repeat(250));
    await expect(builderPage.titleInput).toHaveValue('a'.repeat(200));
    await expect(builderPage.titleCount).toHaveText('200');
  });

  test('TC-04 server rejects a title with illegal characters (422)', async ({ formsApi }) => {
    const res = await formsApi.createRaw(
      formsApi.buildPayload({
        slug: 'fb-e2e-badtitle-' + Date.now().toString(36),
        title: 'Question 1: choose A/B!',
        structure: oneField(),
      }),
    );
    expect(res.status()).toBe(422);
    expect(await res.text()).toMatch(/title/i);
  });

  test('TC-05 a form with zero fields is rejected (422)', async ({ formsApi }) => {
    const res = await formsApi.createRaw({
      ...formsApi.buildPayload({
        slug: 'fb-e2e-empty-' + Date.now().toString(36),
        title: 'Empty form ' + Date.now().toString(36),
        structure: [],
      }),
      form_structure: '[]',
    });
    expect(res.status()).toBe(422);
    expect(await res.text()).toMatch(/at least one field/i);
  });

  test('TC-06 duplicate slug is rejected (422)', async ({ formsApi, forms, db }) => {
    const slug = 'fb-e2e-dupe-' + Date.now().toString(36);
    const payload = formsApi.buildPayload({ slug, title: titleFromSlug(slug), structure: oneField() });

    const first = await formsApi.createRaw(payload);
    expect(first.ok()).toBeTruthy();
    if (db) forms.adopt(((await first.json()) as { id: number }).id);

    const second = await formsApi.createRaw(payload);
    expect(second.status()).toBe(422);
    expect(await second.text()).toMatch(/already in use/i);
  });
});
