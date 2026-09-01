import { Page, Locator } from '@playwright/test';

export abstract class BasePage {
  /** Exposed for occasional ad-hoc locators in specs; prefer page-object methods. */
  constructor(public readonly page: Page) {}

  protected async gotoPath(path: string): Promise<void> {
    await this.page.goto(path, { waitUntil: 'domcontentloaded' });
  }

  /** toastr toast text (used for success/error feedback across the app). */
  toast(kind: 'success' | 'error' | 'any' = 'any'): Locator {
    const cls = kind === 'any' ? '.toast' : `.toast-${kind}`;
    return this.page.locator(`#toast-container ${cls}`);
  }
}
