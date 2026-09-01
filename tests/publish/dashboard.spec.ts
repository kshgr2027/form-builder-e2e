import { test, expect, requireDb } from '../../src/fixtures/test';
import { minimalTextForm } from '../../src/data/form-structures';
import { titleFromSlug } from '../../src/utils/slug';

test.describe('Dashboard — publish toggle & filters', () => {
  // The dashboard list is one shared, globally-filtered DOM surface: two tests
  // toggling publish + switching filters at the same time repaint each other's
  // view. Run this file's tests one at a time. They remain fully independent —
  // each provisions its own form and cleans it up.
  test.describe.configure({ mode: 'serial' });

  test('TC-32 toggling the dashboard publish switch flips is_published; isEverPublished latches', async ({
    forms,
    dashboardPage,
    db,
  }) => {
    requireDb(db);
    const form = await forms.create({ structure: minimalTextForm(), publish: false, hint: 'dash-toggle' });
    const title = titleFromSlug(form.slug);

    expect((await db.formTemplates.findById(form.id))!.is_published).toBe(0);

    await dashboardPage.open();
    await dashboardPage.togglePublish(title, true);

    let row = await db.formTemplates.findById(form.id);
    expect(row!.is_published).toBe(1);
    expect(row!.isEverPublished).toBe(1);

    await dashboardPage.togglePublish(title, false);
    row = await db.formTemplates.findById(form.id);
    expect(row!.is_published).toBe(0);
    expect(row!.isEverPublished, 'isEverPublished must not reset').toBe(1);
  });

  test('TC-32b a form is re-categorised between the Draft and Published filters when toggled', async ({
    forms,
    dashboardPage,
    formsApi,
    db,
  }) => {
    requireDb(db);
    const form = await forms.create({ structure: minimalTextForm(), publish: false, hint: 'dash-counts' });
    const title = titleFromSlug(form.slug);

    // As a draft: listed under "unpublished", not under "published". (Per-form — parallel-safe.)
    await dashboardPage.open();
    await dashboardPage.applyFilter('unpublished');
    await dashboardPage.expectListed(title, true);
    await dashboardPage.applyFilter('published');
    await dashboardPage.expectListed(title, false);

    // Toggle from the "all" view so the row stays visible for the toggle's own re-check.
    await dashboardPage.applyFilter('all');
    await dashboardPage.togglePublish(title, true);

    // Now published: the reverse.
    await dashboardPage.applyFilter('published');
    await dashboardPage.expectListed(title, true);
    await dashboardPage.applyFilter('unpublished');
    await dashboardPage.expectListed(title, false);

    // The count aggregates stay internally consistent: published + unpublished === all.
    const { counts } = await formsApi.listForms();
    expect(counts.published + counts.unpublished).toBe(counts.all);
  });
});
