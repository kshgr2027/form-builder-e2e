import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from '../BasePage';
import { FieldOptionsPanel } from './FieldOptionsPanel';

/** Palette labels === `FIELDS` array in form-builder-v3.js (data-field attribute). */
export type PaletteField =
  | 'Text input'
  | 'Text area'
  | 'Number input'
  | 'Email input'
  | 'Phone input'
  | 'Dropdown'
  | 'Radio buttons'
  | 'Checkboxes'
  | 'Date picker'
  | 'Title'
  | 'Description'
  | 'New line'
  | 'Hidden field'
  | 'Page break';

export type SettingKey = 'anonymous' | 'url' | 'multi' | 'login' | 'edit' | 'review' | 'scoring';

/**
 * The 4-step Form Builder wizard served at `/` (form.blade.php + form-builder-v3.js).
 */
export class FormBuilderPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  // ── shell ────────────────────────────────────────────────────────────────
  readonly heading = this.page.locator('#pageTitle');
  readonly titleInput = this.page.locator('#formTitle');
  readonly titleCount = this.page.locator('#titleCount');
  readonly titleError = this.page.locator('.wiz-step[data-step="1"] .invalid-feedback');
  readonly canvas = this.page.locator('#canvas');
  readonly fieldCards = this.page.locator('#canvas .field-card');
  readonly optionsPanel = new FieldOptionsPanel(this.page.locator('#sideOptionsBody'));
  readonly shareLink = this.page.locator('#shareLink');
  readonly publishNowButton = this.page.locator('#publishNow');
  readonly publishBadge = this.page.locator('#publishBadge');

  private navNext = this.page.locator('#wizFoot [data-nav="next"]');
  private navBack = this.page.locator('#wizFoot [data-nav="back"]');

  async open(): Promise<void> {
    await this.gotoPath('/');
    await expect(this.step(1)).toBeVisible();
  }

  step(n: 1 | 2 | 3 | 4): Locator {
    return this.page.locator(`.wiz-step[data-step="${n}"]:not(.d-none)`);
  }

  async expectOnStep(n: 1 | 2 | 3 | 4): Promise<void> {
    await expect(this.step(n)).toBeVisible();
  }

  async next(): Promise<void> {
    await this.navNext.click();
  }

  async back(): Promise<void> {
    await this.navBack.click();
  }

  // ── step 1 ───────────────────────────────────────────────────────────────
  async setTitle(title: string): Promise<void> {
    await this.titleInput.fill(title);
  }

  // ── step 2 ───────────────────────────────────────────────────────────────
  card(index: number): Locator {
    return this.fieldCards.nth(index);
  }

  /** Re-open the "Add fields" panel — the builder auto-switches to "Field options" after each add. */
  async openFieldsPanel(): Promise<void> {
    const fieldsPanel = this.page.locator('[data-pbody="fields"]');
    if (await fieldsPanel.isHidden().catch(() => true)) {
      await this.page.locator('label[for="ptab-fields"]').click();
    }
    await expect(fieldsPanel).toBeVisible();
  }

  /** Add a field; returns its canvas index. Leaves its options panel open. */
  async addField(type: PaletteField): Promise<number> {
    await this.openFieldsPanel();
    const before = await this.fieldCards.count();
    await this.page.locator(`#palette .pal-item[data-field="${type}"]`).click();
    await expect(this.fieldCards).toHaveCount(before + 1);
    await this.optionsPanel.expectOpen();
    return before;
  }

  async selectField(index: number): Promise<void> {
    await this.card(index).locator('[data-act="edit"]').click();
    await this.optionsPanel.expectOpen();
  }

  async moveFieldUp(index: number): Promise<void> {
    await this.card(index).locator('[data-act="up"]').click();
  }

  async moveFieldDown(index: number): Promise<void> {
    await this.card(index).locator('[data-act="down"]').click();
  }

  async duplicateField(index: number): Promise<void> {
    await this.card(index).locator('[data-act="dup"]').click();
  }

  async deleteField(index: number): Promise<void> {
    const before = await this.fieldCards.count();
    await this.card(index).locator('[data-act="del"]').click();
    await expect(this.fieldCards).toHaveCount(before - 1);
  }

  cardLabel(index: number): Locator {
    return this.card(index).locator('.fw-semibold').first();
  }

  // ── step 3 ───────────────────────────────────────────────────────────────
  async setSetting(key: SettingKey, on: boolean): Promise<void> {
    await this.page.locator(`#${key}-${on ? 'yes' : 'no'}`).check();
  }

  async setSuccessMessage(msg: string): Promise<void> {
    await this.page.locator('#msg').fill(msg);
  }

  async setSubmitButtonText(text: string): Promise<void> {
    await this.page.locator('#submitText').fill(text);
  }

  async setRedirect(mode: 'Same page' | 'Custom URL'): Promise<void> {
    await this.page.locator('#redirect').selectOption({ label: mode });
  }

  // ── navigation helpers ───────────────────────────────────────────────────
  async goToBuilderStep(): Promise<void> {
    await this.expectOnStep(1);
    await this.next();
    await this.expectOnStep(2);
  }

  async goToSettingsStep(): Promise<void> {
    await this.expectOnStep(2);
    await this.next();
    await this.expectOnStep(3);
  }

  /**
   * Click "Create form" on step 3, wait for POST /forms, land on step 4.
   * Returns the created form's slug (parsed from the share link).
   */
  async createForm(): Promise<{ slug: string; shareUrl: string }> {
    await this.expectOnStep(3);
    const [resp] = await Promise.all([
      this.page.waitForResponse((r) => r.url().endsWith('/forms') && r.request().method() === 'POST'),
      this.next(),
    ]);
    expect(resp.ok(), `POST /forms failed: ${resp.status()} ${await resp.text()}`).toBeTruthy();
    const body = (await resp.json()) as { status: number; slug: string; url: string };
    expect(body.status).toBe(1);
    await this.expectOnStep(4);
    const shareUrl = (await this.shareLink.inputValue()) || body.url;
    return { slug: body.slug, shareUrl };
  }

  /** Step 4: "Publish now" -> POST /form-status. */
  async publishNow(): Promise<void> {
    const [resp] = await Promise.all([
      this.page.waitForResponse((r) => r.url().includes('/form-status') && r.request().method() === 'POST'),
      this.publishNowButton.click(),
    ]);
    expect(resp.ok()).toBeTruthy();
    await expect(this.publishBadge).toHaveText(/Published/i);
  }
}
