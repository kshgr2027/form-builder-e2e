import { test, expect } from '../../src/fixtures/test';
import { knownDefect } from '../../src/utils/annotate';
import { optionalEmailForm, contactForm } from '../../src/data/form-structures';
import * as f from '../../src/data/fields';

test.describe('Public form — rendering', () => {
  test('TC-08 field label from the builder renders on the public form', async ({ forms, publicFormPage }) => {
    const form = await forms.create({
      structure: [f.text({ name: 'full_name', label: 'Your full legal name', required: true })],
      hint: 'render-label',
    });

    await publicFormPage.open(form.slug);
    await expect(publicFormPage.container('full_name')).toContainText('Your full legal name');
    await expect(publicFormPage.control('full_name')).toBeVisible();
  });

  test('TC-09 a required text field renders with the required attribute + star', async ({ forms, publicFormPage }) => {
    const form = await forms.create({
      structure: [
        f.text({ name: 'req_field', label: 'Required one', required: true }),
        f.text({ name: 'opt_field', label: 'Optional one', required: false }),
      ],
      hint: 'render-required',
    });

    await publicFormPage.open(form.slug);
    expect(await publicFormPage.isRequired('req_field')).toBe(true);
    expect(await publicFormPage.hasRequiredStar('req_field')).toBe(true);
    expect(await publicFormPage.isRequired('opt_field')).toBe(false);
  });

  test('TC-07b all in-scope field types render with the expected control', async ({ forms, publicFormPage }) => {
    const form = await forms.create({ structure: contactForm(), hint: 'render-types' });
    await publicFormPage.open(form.slug);

    await expect(publicFormPage.control('full_name')).toHaveAttribute('type', 'text');
    await expect(publicFormPage.control('email')).toHaveAttribute('type', 'email');
    await expect(publicFormPage.control('phone')).toHaveAttribute('type', 'tel');
    await expect(publicFormPage.control('age')).toHaveAttribute('type', 'number');
    await expect(publicFormPage.selectControl('topic')).toBeVisible();
    await expect(publicFormPage.radioOption('contact_pref', 'Email')).toBeVisible();
    await expect(publicFormPage.checkboxOption('interests', 'News')).toBeVisible();
    await expect(publicFormPage.textareaControl('message')).toBeVisible();
  });

  test('TC-18 @known-defect an OPTIONAL email field is rendered as required (DEF-01)', async ({
    forms,
    publicFormPage,
  }) => {
    knownDefect('DEF-01', 'form-renderer.js hardcodes required on email/tel regardless of the builder toggle');
    const form = await forms.create({ structure: optionalEmailForm(), hint: 'render-def01' });

    await publicFormPage.open(form.slug);

    // Builder said required: false, but the renderer forces it.
    expect(await publicFormPage.isRequired('email')).toBe(true);
    expect(await publicFormPage.hasRequiredStar('email')).toBe(true);

    // And submitting without it is blocked client-side.
    await publicFormPage.fillField('full_name', 'Only name provided');
    await publicFormPage.submitExpectingClientBlock();
  });

  test('TC-40 @known-defect an <img onerror> payload in a field label executes on the public form (DEF-11)', async ({
    forms,
    publicFormPage,
  }) => {
    knownDefect('DEF-11', 'form-renderer.js interpolates element.label into innerHTML unescaped — onerror handlers run');

    // Non-destructive proof-of-concept: the handler only sets a window flag.
    const payload = '<img src=x onerror="window.__xssProof=1">';
    const form = await forms.create({
      structure: [
        f.text({ name: 'probe', label: payload, required: false }),
        f.text({ name: 'real', label: 'Real field', required: true }),
      ],
      hint: 'render-def11',
    });

    await publicFormPage.open(form.slug);

    // 1. The label was rendered as HTML, not escaped text: a real <img> element appears.
    await expect(publicFormPage.labelImages('probe')).toHaveCount(1);
    // 2. Its onerror handler executed (broken src -> error -> handler).
    await expect
      .poll(() => publicFormPage.windowValue<number>('__xssProof'), {
        message: 'onerror handler from the field label should have run',
        timeout: 5000,
      })
      .toBe(1);
    // 3. The rest of the form still rendered.
    await expect(publicFormPage.control('real')).toBeVisible();
  });
});
