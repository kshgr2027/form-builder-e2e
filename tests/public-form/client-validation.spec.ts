import { test, expect } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import * as f from '../../src/data/fields';
import { numberRangeForm, constrainedTextForm } from '../../src/data/form-structures';

test.describe('Public form — client-side validation', () => {
  test('TC-14 a required field blocks submit and shows an inline error', async ({ forms, publicFormPage }) => {
    const form = await forms.create({
      structure: [f.text({ name: 'full_name', label: 'Full name', required: true })],
      hint: 'cv-required',
    });

    await publicFormPage.open(form.slug);
    await publicFormPage.submitExpectingClientBlock();
    // The app surfaces the browser's native validity message ("Please fill out this field.")
    // in its own error slot; the point is that submit is blocked and an error is shown.
    await expect(publicFormPage.errorFor('full_name')).toHaveText(/required|fill (out|in) this field/i);
  });

  test('TC-19 phone must be exactly 10 digits (client)', async ({ forms, publicFormPage }) => {
    const form = await forms.create({
      structure: [f.text({ name: 'full_name', label: 'Name', required: true }), f.tel({ name: 'phone', label: 'Phone' })],
      hint: 'cv-phone',
    });

    await publicFormPage.open(form.slug);
    await publicFormPage.fill({ full_name: 'X', phone: '12345' });
    await publicFormPage.submitExpectingClientBlock();
    await expect(publicFormPage.errorFor('phone')).toContainText(/10 digits/i);
  });

  test('TC-19b phone strips non-digit characters as you type', async ({ forms, publicFormPage }) => {
    const form = await forms.create({ structure: [f.tel({ name: 'phone', label: 'Phone' })], hint: 'cv-phone-strip' });
    await publicFormPage.open(form.slug);
    await publicFormPage.control('phone').fill('98a7b6c5d4e3');
    await expect(publicFormPage.control('phone')).toHaveValue('9876543');
  });

  test('TC-21 number outside the configured range shows an inline error (client)', async ({ forms, publicFormPage }) => {
    const form = await forms.create({ structure: numberRangeForm(), hint: 'cv-number' }); // quantity 1..10
    await publicFormPage.open(form.slug);
    await publicFormPage.control('quantity').fill('50');
    await publicFormPage.control('quantity').blur();
    // App shows the native range message ("Value must be less than or equal to 10.")
    // or its own "between 1 and 10" text depending on which handler wins.
    await expect(publicFormPage.errorFor('quantity')).toContainText(
      /between 1 and 10|less than or equal to 10|greater than or equal to 1/i,
    );
  });

  test('TC-25 required checkbox group, nothing selected -> inline error, submit blocked', async ({
    forms,
    publicFormPage,
  }) => {
    const form = await forms.create({
      structure: [f.checkbox({ name: 'agree', label: 'Accept', required: true, options: ['Terms', 'Privacy'] })],
      hint: 'cv-checkbox',
    });
    await publicFormPage.open(form.slug);
    await publicFormPage.submitExpectingClientBlock();
    await expect(publicFormPage.errorFor('agree')).toContainText(/select at least one|required/i);
  });

  test('TC-23 @known-defect text min/max chars are not enforced on the public form (DEF-02)', async ({
    forms,
    publicFormPage,
    db,
  }) => {
    knownDefect('DEF-02', 'renderer omits minlength/maxlength/pattern/allowed-chars for type=text');
    const form = await forms.create({ structure: constrainedTextForm(), hint: 'cv-def02' }); // code 5..10, letters only

    await publicFormPage.open(form.slug);
    const code = publicFormPage.control('code');
    // The input carries no maxlength/minlength/pattern/data-allowchars at all.
    await expect(code).not.toHaveAttribute('maxlength', /.+/);
    await expect(code).not.toHaveAttribute('minlength', /.+/);
    await expect(code).not.toHaveAttribute('pattern', /.+/);

    // A 2-char, digit-containing value sails straight through.
    await code.fill('a1');
    await publicFormPage.submit();
    await publicFormPage.expectSubmitted();
    if (db) {
      const row = await db.formSubmissions.latestForTemplate(form.id);
      expect(row!.submission_data.code).toBe('a1');
    }
  });
});
