import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from '../BasePage';
import { FormValue } from '../../utils/http';

export type PublicFormRecord = Record<string, FormValue>;

/**
 * The public submission page: GET/POST /submit/{slug}, rendered by form-renderer.js.
 * Fields are addressed by their `name` (stable, and the key used in submission_data);
 * `getByLabel` is unreliable here because the renderer omits `for`/`id` on several types.
 */
export class PublicFormPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  readonly form = this.page.locator('#formSubmission');
  readonly renderer = this.page.locator('#formRenderer');
  readonly submitButton = this.page.locator('#submitBtn');
  readonly alreadySubmitted = this.page.getByText('Form already submitted!', { exact: false });

  title(): Locator {
    return this.page.locator('.page-title');
  }

  async open(slug: string): Promise<void> {
    await this.openRaw(slug);
    await expect(this.form).toBeVisible();
    // renderForm() is async; wait until at least the submit button is in place.
    await expect(this.submitButton).toBeVisible();
  }

  /** Navigate without asserting the form is present (e.g. "already submitted" / 403 pages). */
  async openRaw(slug: string): Promise<void> {
    await this.gotoPath(`/submit/${slug}`);
  }

  /** The rendered wrapper `#container_<name>` — for visibility, label and label-HTML assertions. */
  container(name: string): Locator {
    return this.page.locator(`#container_${cssEscape(name)}`);
  }

  /** First control for a field, by `name` (or `name[]` for checkbox groups). */
  control(name: string): Locator {
    return this.page.locator(`#formSubmission [name="${name}"], #formSubmission [name="${name}[]"]`).first();
  }

  selectControl(name: string): Locator {
    return this.page.locator(`#formSubmission select[name="${name}"]`);
  }

  textareaControl(name: string): Locator {
    return this.page.locator(`#formSubmission textarea[name="${name}"]`);
  }

  radioOption(name: string, value: string): Locator {
    return this.page.locator(`#formSubmission input[type="radio"][name="${name}"][value="${cssAttr(value)}"]`);
  }

  checkboxOption(name: string, value: string): Locator {
    return this.page.locator(`#formSubmission input[type="checkbox"][name="${name}[]"][value="${cssAttr(value)}"]`);
  }

  /** `<img>` elements rendered inside a field's label/container (used by the XSS probe, TC-40). */
  labelImages(name: string): Locator {
    return this.container(name).locator('img');
  }

  /** Tag name (`input`|`select`|`textarea`) and input `type` of the rendered control. */
  async controlKind(name: string): Promise<{ tag: string; type: string | null }> {
    const el = this.control(name);
    const tag = (await el.evaluate((n) => n.tagName.toLowerCase())) as string;
    const type = await el.getAttribute('type');
    return { tag, type };
  }

  errorFor(name: string): Locator {
    return this.page.locator(`#error_${cssEscape(name)}`);
  }

  async isRequired(name: string): Promise<boolean> {
    return (await this.control(name).getAttribute('required')) !== null;
  }

  async hasRequiredStar(name: string): Promise<boolean> {
    return (await this.container(name).locator('span.text-danger', { hasText: '*' }).count()) > 0;
  }

  async fillField(name: string, value: FormValue): Promise<void> {
    const base = this.page.locator(`#formSubmission [name="${name}"]`);
    const arr = this.page.locator(`#formSubmission [name="${name}[]"]`);
    const isCheckbox = (await base.count()) === 0 && (await arr.count()) > 0;
    const target = isCheckbox ? arr : base;
    const first = target.first();

    const tag = (await first.evaluate((el) => el.tagName.toLowerCase())) as string;
    const type = (await first.getAttribute('type')) ?? '';

    if (tag === 'select') {
      await first.selectOption(String(value));
      return;
    }
    if (type === 'radio') {
      await this.page.locator(`#formSubmission [name="${name}"][value="${cssAttr(String(value))}"]`).check();
      return;
    }
    if (type === 'checkbox') {
      const values = Array.isArray(value) ? value : [value];
      for (const v of values) {
        await this.page.locator(`#formSubmission [name="${name}[]"][value="${cssAttr(String(v))}"]`).check();
      }
      return;
    }
    await first.fill(value == null ? '' : String(value));
  }

  async fill(record: PublicFormRecord): Promise<void> {
    for (const [name, value] of Object.entries(record)) {
      if (value === undefined) continue;
      await this.fillField(name, value);
    }
  }

  /** Click submit and (optionally) wait for the network round-trip. */
  async submit(opts: { expectRequest?: boolean } = {}): Promise<void> {
    const expectRequest = opts.expectRequest ?? true;
    if (expectRequest) {
      await Promise.all([
        this.page.waitForResponse((r) => r.url().includes('/submit/') && r.request().method() === 'POST'),
        this.submitButton.click(),
      ]);
    } else {
      await this.submitButton.click();
    }
  }

  /**
   * Click submit and assert the request never left the browser (client-side
   * validation blocked it). Fails fast if a POST /submit/ fires.
   */
  async submitExpectingClientBlock(): Promise<void> {
    let sawRequest = false;
    const listener = (r: import('@playwright/test').Request) => {
      if (r.url().includes('/submit/') && r.method() === 'POST') sawRequest = true;
    };
    this.page.on('request', listener);
    try {
      await this.submitButton.click();
      // Give the browser a real signal to settle on, not an arbitrary sleep:
      // the invalid field gets `.is-invalid` (setFieldMessage) when blocked.
      await expect(this.form.locator('.is-invalid').first()).toBeVisible();
      expect(sawRequest, 'expected client validation to block the submit, but POST /submit fired').toBe(false);
    } finally {
      this.page.off('request', listener);
    }
  }

  /**
   * A successful non-AJAX submit redirects back and re-renders the page. For a
   * single-submission form the server renders a durable "Form submitted!" heading;
   * for a multi-submission form only the (transient) toastr toast appears. Accept
   * either — the toast alone is timing-fragile.
   */
  async expectSubmitted(): Promise<void> {
    const heading = this.page.getByText('Form submitted!', { exact: false });
    await expect(heading.or(this.toast('success')).first()).toBeVisible({ timeout: 15_000 });
  }

  /**
   * After a submit on a form with `redirect_method = custom`, show.blade.php
   * navigates the browser to `redirect_url` ~2s later (a setTimeout). Poll the
   * URL for the change rather than sleeping.
   */
  async expectRedirectedTo(target: string | RegExp, timeoutMs = 15_000): Promise<void> {
    await expect(this.page).toHaveURL(target, { timeout: timeoutMs });
  }

  /** Read a value the page's own scripts may have set on `window` (used by the XSS probe). */
  async windowValue<T = unknown>(key: string): Promise<T | undefined> {
    return this.page.evaluate((k) => (window as unknown as Record<string, unknown>)[k], key) as Promise<T | undefined>;
  }
}

/** Field names are our own, alnum + underscore, so escaping is trivial — but be safe. */
function cssEscape(s: string): string {
  return s.replace(/[^a-zA-Z0-9_-]/g, (c) => `\\${c}`);
}
function cssAttr(s: string): string {
  return s.replace(/"/g, '\\"');
}
