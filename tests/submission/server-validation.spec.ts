import { test, expect } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import * as f from '../../src/data/fields';
import {
  contactForm,
  numberRangeForm,
  fixedOptionsForm,
  dateRangeForm,
  constrainedTextForm,
} from '../../src/data/form-structures';

/**
 * Server-side validation coverage for POST /submit/{slug}, driven directly via
 * HTTP so client-side JS is out of the picture. Several of these document
 * confirmed gaps in FormSubmissionController::generateValidationRules().
 */
test.describe('Submission — server-side validation', () => {
  test('TC-17 invalid email is rejected (422)', async ({ forms, submissionsApi }) => {
    const form = await forms.create({ structure: contactForm(), hint: 'srv-email' });

    const res = await submissionsApi.submit(form.slug, {
      full_name: 'X',
      email: 'not-an-email',
      topic: 'Sales',
      contact_pref: 'Email',
      message: 'hi',
    });

    expect(res.status()).toBe(422);
    expect(await res.text()).toMatch(/email/i);
  });

  test('TC-25 required checkbox group missing is rejected (422)', async ({ forms, submissionsApi }) => {
    const form = await forms.create({
      structure: [f.checkbox({ name: 'agree', label: 'Agree to terms', required: true, options: ['Yes'] })],
      hint: 'srv-checkbox',
    });

    const res = await submissionsApi.submit(form.slug, {});
    expect(res.status()).toBe(422);
  });

  test('TC-20 @known-defect phone accepts non-10-digit / non-numeric values (DEF-06)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-06', 'tel/number fields have no server-side format or range validation');
    const form = await forms.create({ structure: contactForm(), hint: 'srv-phone' });

    const res = await submissionsApi.submit(form.slug, {
      full_name: 'X',
      email: 'x@example.com',
      phone: 'not-a-phone',
      topic: 'Sales',
      contact_pref: 'Email',
      message: 'hi',
    });

    // Actual (buggy) behaviour: accepted.
    expect(res.ok()).toBeTruthy();
    if (db) {
      const row = await db.formSubmissions.latestForTemplate(form.id);
      expect(row!.submission_data.phone).toBe('not-a-phone');
    }
  });

  test('TC-22 @known-defect number field accepts out-of-range & non-numeric (DEF-06)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-06', 'number field: no numeric/min/max rule server-side');
    const form = await forms.create({ structure: numberRangeForm(), hint: 'srv-number' }); // quantity 1..10

    const outOfRange = await submissionsApi.submit(form.slug, { quantity: '999999' });
    const notANumber = await submissionsApi.submit(form.slug, { quantity: 'abc' });

    expect(outOfRange.ok()).toBeTruthy();
    expect(notANumber.ok()).toBeTruthy();
    if (db) {
      const rows = await db.formSubmissions.allForTemplate(form.id);
      expect(rows.map((r) => r.submission_data.quantity)).toEqual(['999999', 'abc']);
    }
  });

  test('TC-24 @known-defect select accepts a value outside the configured options (DEF-07)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-07', 'select/radio value is not whitelisted against options server-side');
    const form = await forms.create({ structure: fixedOptionsForm(), hint: 'srv-select' }); // Red/Green/Blue

    const res = await submissionsApi.submit(form.slug, { colour: 'purple' });

    expect(res.ok()).toBeTruthy();
    if (db) {
      const row = await db.formSubmissions.latestForTemplate(form.id);
      expect(row!.submission_data.colour).toBe('purple');
    }
  });

  test('TC-08b @known-defect date field has no server-side date / range validation (DEF-08)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-08', 'date field falls through to a bare required/nullable rule — no `date`, no range check');
    // Field configured to only allow dates in 2026.
    const form = await forms.create({ structure: dateRangeForm(), hint: 'srv-date' });

    const notADate = await submissionsApi.submit(form.slug, { event_date: 'not-a-date' });
    const beforeRange = await submissionsApi.submit(form.slug, { event_date: '2000-01-01' });

    // Expected (if enforced): 422 for both. Actual (buggy): accepted and stored verbatim.
    expect(notADate.ok()).toBeTruthy();
    expect(beforeRange.ok()).toBeTruthy();

    if (db) {
      const rows = await db.formSubmissions.allForTemplate(form.id);
      expect(rows.map((r) => r.submission_data.event_date)).toEqual(['not-a-date', '2000-01-01']);
    }
  });

  test('TC-23 @known-defect text min/max length & letters-only are not enforced (DEF-02)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-02', 'text field constraints dropped by renderer AND absent server-side');
    // Same field as the client-side TC-23: "code", 5-10 chars, letters only.
    const form = await forms.create({ structure: constrainedTextForm(), hint: 'srv-text' });

    const tooShort = await submissionsApi.submit(form.slug, { code: 'ab' });
    const tooLong = await submissionsApi.submit(form.slug, { code: 'x'.repeat(400) });
    const hasDigits = await submissionsApi.submit(form.slug, { code: 'abc123' });

    expect(tooShort.ok()).toBeTruthy();
    expect(tooLong.ok()).toBeTruthy();
    expect(hasDigits.ok()).toBeTruthy();

    if (db) {
      expect(await db.formSubmissions.countForTemplate(form.id)).toBe(3);
    }
  });
});
