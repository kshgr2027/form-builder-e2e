import { test, expect, requireDb } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import { minimalTextForm } from '../../src/data/form-structures';

/**
 * Access control on the public form. GET enforces four guards
 * (exists / active / accessible_using_url); POST enforces none — DEF-03.
 */
test.describe('Public form — access control', () => {
  test('TC-33 unknown slug returns 404', async ({ submissionsApi }) => {
    const res = await submissionsApi.view('fb-e2e-does-not-exist-zzz');
    expect(res.status()).toBe(404);
  });

  test('TC-34 GET is 403 when accessible_using_url = 0', async ({ forms, submissionsApi }) => {
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { accessible_using_url: 0 },
      hint: 'acc-off',
    });
    const res = await submissionsApi.view(form.slug);
    expect(res.status()).toBe(403);
  });

  test('TC-35 GET is 403 for an archived form (active = 0)', async ({ forms, submissionsApi, db }) => {
    requireDb(db);
    const form = await forms.create({ structure: minimalTextForm(), hint: 'acc-archived' });
    await db.formTemplates.setFlags(form.id, { active: 0 });

    const res = await submissionsApi.view(form.slug);
    expect(res.status()).toBe(403);
  });

  test('TC-36 @known-defect unpublished form (is_published = 0) is still viewable AND submittable (DEF-03)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-03', 'show()/store() ignore is_published; store() ignores every access guard');
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { accessible_using_url: 1 },
      publish: false,
      hint: 'acc-unpub',
    });
    if (db) expect((await db.formTemplates.findById(form.id))!.is_published).toBe(0);

    const view = await submissionsApi.view(form.slug);
    expect(view.status()).toBe(200); // expected: 403/blocked

    const post = await submissionsApi.submit(form.slug, { full_name: 'Anon' });
    expect(post.ok()).toBeTruthy(); // expected: blocked
    if (db) expect(await db.formSubmissions.countForTemplate(form.id)).toBe(1);
  });

  test('TC-37 @known-defect POST /submit accepts a submission for a not-accessible form (DEF-03)', async ({
    forms,
    submissionsApi,
    db,
  }) => {
    knownDefect('DEF-03', 'GET guard (accessible_using_url) is not mirrored on POST');
    const form = await forms.create({
      structure: minimalTextForm(),
      settings: { accessible_using_url: 0 },
      hint: 'acc-off-post',
    });

    // Confirm the GET page is blocked...
    expect((await submissionsApi.view(form.slug)).status()).toBe(403);
    // ...but the POST still stores a row.
    const post = await submissionsApi.submit(form.slug, { full_name: 'Anon' });
    expect(post.ok()).toBeTruthy();
    if (db) expect(await db.formSubmissions.countForTemplate(form.id)).toBe(1);
  });
});
