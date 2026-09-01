import { test, expect, requireDb } from '../../src/fixtures/test';
import { uniqueSlug, titleFromSlug } from '../../src/utils/slug';
import type { PaletteField } from '../../src/pages/builder/FormBuilderPage';

const IN_SCOPE: Array<{ palette: PaletteField; serverType: string }> = [
  { palette: 'Text input', serverType: 'text' },
  { palette: 'Text area', serverType: 'textarea' },
  { palette: 'Number input', serverType: 'number' },
  { palette: 'Email input', serverType: 'email' },
  { palette: 'Phone input', serverType: 'tel' },
  { palette: 'Dropdown', serverType: 'select' },
  { palette: 'Radio buttons', serverType: 'radio' },
  { palette: 'Checkboxes', serverType: 'checkbox' },
  { palette: 'Date picker', serverType: 'date' },
  { palette: 'Title', serverType: 'title' },
  { palette: 'Description', serverType: 'description' },
  { palette: 'New line', serverType: 'new_line' },
  { palette: 'Hidden field', serverType: 'hidden_field' },
  { palette: 'Page break', serverType: 'page_break' },
];

test.describe('Form Builder — field management', () => {
  test('TC-07 add one of every in-scope field type; all land in form_structure', async ({
    builderPage,
    forms,
    db,
  }, testInfo) => {
    requireDb(db);
    await builderPage.open();
    await builderPage.setTitle(titleFromSlug(uniqueSlug(testInfo, 'fields-all')));
    await builderPage.goToBuilderStep();

    for (const { palette } of IN_SCOPE) {
      await builderPage.addField(palette);
    }
    await expect(builderPage.fieldCards).toHaveCount(IN_SCOPE.length);

    await builderPage.goToSettingsStep();
    const { slug } = await builderPage.createForm();
    forms.adopt(await forms.idBySlug(slug));

    const structure = await db.formTemplates.structureBySlug(slug);
    const types = structure!.map((e) => e.type);
    for (const { serverType } of IN_SCOPE) {
      expect(types, `structure should contain a "${serverType}" element`).toContain(serverType);
    }
  });

  test('TC-10 duplicate a field inserts a copy directly below it', async ({ builderPage, forms, db }, testInfo) => {
    await builderPage.open();
    await builderPage.setTitle(titleFromSlug(uniqueSlug(testInfo, 'fields-dup')));
    await builderPage.goToBuilderStep();

    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('Alpha');
    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('Beta');

    await builderPage.duplicateField(0);
    await expect(builderPage.fieldCards).toHaveCount(3);
    await expect(builderPage.cardLabel(1)).toContainText('Alpha');
    await expect(builderPage.cardLabel(2)).toContainText('Beta');

    if (db) {
      await builderPage.goToSettingsStep();
      const { slug } = await builderPage.createForm();
      forms.adopt(await forms.idBySlug(slug));
      const s = await db.formTemplates.structureBySlug(slug);
      const names = s!.filter((e) => e.type === 'text').map((e) => e.name);
      expect(new Set(names).size, 'duplicated field must get a distinct name').toBe(names.length);
    }
  });

  test('TC-11 delete a field removes it from the canvas and structure', async ({ builderPage, forms, db }, testInfo) => {
    await builderPage.open();
    await builderPage.setTitle(titleFromSlug(uniqueSlug(testInfo, 'fields-del')));
    await builderPage.goToBuilderStep();

    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('KeepMe');
    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('DeleteMe');

    await builderPage.deleteField(1);
    await expect(builderPage.fieldCards).toHaveCount(1);
    await expect(builderPage.cardLabel(0)).toContainText('KeepMe');

    if (db) {
      await builderPage.goToSettingsStep();
      const { slug } = await builderPage.createForm();
      forms.adopt(await forms.idBySlug(slug));
      const s = await db.formTemplates.structureBySlug(slug);
      expect(s!.map((e) => e.label)).toContain('KeepMe');
      expect(s!.map((e) => e.label)).not.toContain('DeleteMe');
    }
  });

  test('TC-12 reorder fields with the arrow controls; order persists', async ({ builderPage, forms, db }, testInfo) => {
    requireDb(db);
    await builderPage.open();
    await builderPage.setTitle(titleFromSlug(uniqueSlug(testInfo, 'fields-order')));
    await builderPage.goToBuilderStep();

    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('First');
    await builderPage.addField('Text input');
    await builderPage.optionsPanel.setLabel('Second');

    await builderPage.moveFieldDown(0);
    await expect(builderPage.cardLabel(0)).toContainText('Second');
    await expect(builderPage.cardLabel(1)).toContainText('First');

    await builderPage.goToSettingsStep();
    const { slug } = await builderPage.createForm();
    forms.adopt(await forms.idBySlug(slug));

    const s = await db.formTemplates.structureBySlug(slug);
    const textLabels = s!.filter((e) => e.type === 'text').map((e) => e.label);
    expect(textLabels).toEqual(['Second', 'First']);
  });
});
