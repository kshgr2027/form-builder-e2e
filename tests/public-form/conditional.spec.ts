import { test, expect } from '../../src/fixtures/test';
import { conditionalForm } from '../../src/data/form-structures';

/**
 * Conditional visibility: `details` is shown & required only when
 * `need_details` == "Yes". Server (areConditionsMetServer) mirrors this.
 */
test.describe('Public form — conditional logic', () => {
  test('TC-26 conditional field hides by default, appears on match, toggles required', async ({
    forms,
    publicFormPage,
  }) => {
    const form = await forms.create({ structure: conditionalForm(), hint: 'cond' });
    await publicFormPage.open(form.slug);

    const details = publicFormPage.container('details');
    await expect(details).toBeHidden();

    await publicFormPage.fillField('need_details', 'Yes');
    await expect(details).toBeVisible();
    expect(await publicFormPage.isRequired('details')).toBe(true);

    await publicFormPage.fillField('need_details', 'No');
    await expect(details).toBeHidden();
  });

  test('TC-26b submitting with the condition unmet does not require the hidden field', async ({
    forms,
    publicFormPage,
    db,
  }) => {
    const form = await forms.create({ structure: conditionalForm(), hint: 'cond-submit' });
    await publicFormPage.open(form.slug);

    await publicFormPage.fillField('need_details', 'No');
    await publicFormPage.submit();
    await publicFormPage.expectSubmitted();

    if (db) {
      const row = await db.formSubmissions.latestForTemplate(form.id);
      expect(row!.submission_data.need_details).toBe('No');
    }
  });
});
