import { test, expect, requireDb } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import { minimalTextForm } from '../../src/data/form-structures';

/**
 * The dashboard exposes a "Short form link" (`/s/{unique_string}`) for every
 * form. FormSubmissionController::short_link_show() references an undefined
 * `$role`, so the route 500s for any active form (needs APP_DEBUG=true to
 * surface as a 500 rather than a generic error page).
 */
test.describe('Short link', () => {
  test('TC-38 @known-defect GET /s/{unique_string} returns HTTP 500 (DEF-05)', async ({ forms, submissionsApi, db }) => {
    requireDb(db); // we need the unique_string, which the API response does not include
    knownDefect('DEF-05', 'short_link_show() uses undefined $role -> 500 for any active form');

    const form = await forms.create({ structure: minimalTextForm(), hint: 'short-link' });
    const uniqueString = (await db.formTemplates.findById(form.id))!.unique_string;
    expect(uniqueString, 'form should have a unique_string').toBeTruthy();

    const res = await submissionsApi.viewShortLink(uniqueString!);
    expect(res.status()).toBe(500); // expected: 200 and the form renders
  });
});
