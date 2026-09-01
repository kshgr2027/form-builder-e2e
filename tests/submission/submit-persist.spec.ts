import { test, expect, requireDb } from '../../src/fixtures/test';
import { contactForm } from '../../src/data/form-structures';

/**
 * Core E2E + persistence: a valid submission through the browser must land in
 * `form_submissions` with exactly the data entered.
 */
test.describe('Submission — positive & persistence', () => {
  test('TC-15 valid submission persists exact data to form_submissions', async ({ forms, publicFormPage, db }) => {
    requireDb(db);
    const form = await forms.create({ structure: contactForm(), hint: 'submit-persist' });

    const record = {
      full_name: 'Ada Lovelace',
      email: 'ada@example.com',
      phone: '9876543210',
      age: '31',
      topic: 'Support',
      contact_pref: 'Email',
      interests: ['News', 'Events'],
      message: 'Please call me back about my ticket.',
    };

    await publicFormPage.open(form.slug);
    await publicFormPage.fill(record);
    await publicFormPage.submit();
    await publicFormPage.expectSubmitted();

    const row = await db.formSubmissions.latestForTemplate(form.id);
    expect(row, 'a submission row should exist').not.toBeNull();
    expect(row!.userid).toBe(1); // hardcoded in FormSubmissionController::store()
    expect(row!.submission_data).toMatchObject({
      full_name: 'Ada Lovelace',
      email: 'ada@example.com',
      phone: '9876543210',
      age: '31',
      topic: 'Support',
      contact_pref: 'Email',
      message: 'Please call me back about my ticket.',
    });
  });

  test('TC-16 checkbox values persist as a JSON array', async ({ forms, publicFormPage, db }) => {
    requireDb(db);
    const form = await forms.create({ structure: contactForm(), hint: 'submit-checkbox' });

    await publicFormPage.open(form.slug);
    await publicFormPage.fill({
      full_name: 'Grace Hopper',
      email: 'grace@example.com',
      phone: '9876543210', // renderer force-requires tel (DEF-01), so it must be filled
      topic: 'Sales',
      contact_pref: 'Phone',
      message: 'hi',
      interests: ['News', 'Offers'],
    });
    await publicFormPage.submit();
    await publicFormPage.expectSubmitted();

    const row = await db.formSubmissions.latestForTemplate(form.id);
    expect(Array.isArray(row!.submission_data.interests)).toBe(true);
    expect(row!.submission_data.interests).toEqual(['News', 'Offers']);
  });

  test('TC-14b client-blocked submit creates no row', async ({ forms, publicFormPage, db }) => {
    requireDb(db);
    const form = await forms.create({ structure: contactForm(), hint: 'submit-blocked' });

    await publicFormPage.open(form.slug);
    // leave required fields empty
    await publicFormPage.submitExpectingClientBlock();

    expect(await db.formSubmissions.countForTemplate(form.id)).toBe(0);
  });
});
