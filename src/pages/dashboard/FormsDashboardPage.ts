import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from '../BasePage';

export type DashboardFilter = 'all' | 'published' | 'unpublished' | 'archived';

/**
 * The forms dashboard at `/forms` (index_new.blade.php).
 *
 * The list is populated by an XHR to `GET /forms?filter=...`; every state-mutating
 * action re-fetches, and we wait on that response rather than on timers. The page
 * renders BOTH a card view (`#cardView`) and a table view (`#tableBody`) from the
 * same data — one is `d-none` — so every locator is scoped to one view.
 */
export class FormsDashboardPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  readonly loader = this.page.locator('#fbLoader');
  readonly search = this.page.locator('#fbSearch');
  readonly emptyState = this.page.locator('#emptyState');
  readonly cardView = this.page.locator('#cardView');
  readonly tableView = this.page.locator('#tableView');

  private filterPill(f: DashboardFilter): Locator {
    return this.page.locator(`.form-filter[data-filter="${f}"]`);
  }

  count(f: DashboardFilter): Locator {
    return this.page.locator(`.ft-count[data-count="${f}"]`);
  }

  /** Card (card view) for a form, matched case-insensitively via `data-name`. */
  cardItem(name: string): Locator {
    return this.cardView.locator(`.fb-item[data-name="${name.toLowerCase()}"]`);
  }

  /** Table row (`#tableBody`) for a form. */
  rowItem(name: string): Locator {
    return this.page.locator(`#tableBody .fb-item[data-name="${name.toLowerCase()}"]`);
  }

  async open(): Promise<void> {
    await Promise.all([this.waitForListLoad(), this.gotoPath('/forms')]);
  }

  /**
   * Wait for the `GET /forms` XHR triggered by a state change. The client repaints
   * the list in the ajax `success` handler right after; callers that assert on a
   * specific form use `expectListed()`, which polls and so absorbs that lag.
   */
  async waitForListLoad(): Promise<void> {
    await this.page
      .waitForResponse((r) => new URL(r.url()).pathname === '/forms' && r.request().method() === 'GET', {
        timeout: 15_000,
      })
      .catch(() => undefined);
  }

  async applyFilter(f: DashboardFilter): Promise<void> {
    // A filter change is a fresh view — drop any lingering search term first so the
    // rendered list matches the fetched payload.
    if ((await this.search.inputValue()).trim() !== '') await this.search.fill('');
    await Promise.all([this.waitForListLoad(), this.filterPill(f).click()]);
  }

  async useTableView(): Promise<void> {
    await this.page.locator('.vs-btn[data-view="table"]').click();
    await expect(this.tableView).toBeVisible();
  }

  /** Client-side filter (no network). Settles once the match or the empty state is present. */
  async searchFor(text: string): Promise<void> {
    await this.search.fill(text);
    await expect(async () => {
      const shown = (await this.cardItem(text).count()) + (await this.rowItem(text).count());
      const empty = await this.emptyState.isVisible();
      expect(shown > 0 || empty).toBe(true);
    }).toPass({ timeout: 5_000 });
  }

  async isListed(name: string): Promise<boolean> {
    return (await this.cardItem(name).count()) > 0;
  }

  /**
   * Poll for a specific form's presence/absence in the list. Immune to render lag
   * and to sibling workers (keys on this one form's `data-name` only).
   */
  async expectListed(name: string, present: boolean): Promise<void> {
    await expect
      .poll(() => this.isListed(name), { timeout: 8_000, message: `form "${name}" listed === ${present}` })
      .toBe(present);
  }

  /** Status ribbon text on the card ("Published" | "Draft" | "Archived"). */
  async statusOf(name: string): Promise<string> {
    const ribbon = this.cardItem(name).locator('.ribbon span').first();
    return (await ribbon.textContent())?.trim() ?? '';
  }

  /**
   * The visible publish switch in the row's first cell. Each row also has a second
   * `.fb-pub` input inside its "more" dropdown (`span.switch`), so scope to `label.switch`.
   */
  publishToggle(name: string): Locator {
    return this.rowItem(name).locator('label.switch input.fb-pub');
  }

  async isPublishToggleOn(name: string): Promise<boolean> {
    return this.publishToggle(name).isChecked();
  }

  /**
   * The switch `<input>` is visually hidden behind a `.slider` span, so we click
   * the wrapping `label.switch` in the table row. The change handler POSTs to
   * /form-status and re-fetches the list.
   */
  async togglePublish(name: string, on: boolean): Promise<void> {
    await this.useTableView();
    await this.searchFor(name);
    const input = this.publishToggle(name);
    await expect(input).toBeAttached();
    if ((await input.isChecked()) === on) return;
    await Promise.all([this.waitForListLoad(), this.rowItem(name).locator('td:first-child label.switch').click()]);
    await this.searchFor(name);
    await expect(this.publishToggle(name)).toBeChecked({ checked: on });
  }
}
