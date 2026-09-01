import { Locator, expect } from '@playwright/test';

/**
 * The "Field options" panel (desktop `#sideOptionsBody`). Operates on whichever
 * field is currently selected on the canvas. All builder option selectors are
 * centralised here.
 */
export class FieldOptionsPanel {
  constructor(private readonly root: Locator) {}

  private input(cls: string): Locator {
    return this.root.locator(`.${cls}`);
  }

  async expectOpen(): Promise<void> {
    await expect(this.root).toBeVisible();
    // "Element settings" is the panel's fixed header for every field type (for a
    // Description field TinyMCE hides the raw .o-desc textarea, so don't key on a control).
    await expect(this.root).toContainText('Element settings');
  }

  async setLabel(value: string): Promise<void> {
    await this.input('o-label').fill(value);
  }

  async setPlaceholder(value: string): Promise<void> {
    await this.input('o-ph').fill(value);
  }

  async setRequired(required: boolean): Promise<void> {
    await this.input('o-req').setChecked(required);
  }

  async setMinChars(n: number | string): Promise<void> {
    await this.input('o-min').fill(String(n));
  }

  async setMaxChars(n: number | string): Promise<void> {
    await this.input('o-max').fill(String(n));
  }

  async setMinValue(n: number | string): Promise<void> {
    await this.input('o-minval').fill(String(n));
  }

  async setMaxValue(n: number | string): Promise<void> {
    await this.input('o-maxval').fill(String(n));
  }

  async setAllowedChars(opts: { numbers?: boolean; special?: boolean; space?: boolean }): Promise<void> {
    if (opts.numbers !== undefined) await this.input('o-num').setChecked(opts.numbers);
    if (opts.special !== undefined) await this.input('o-sp').setChecked(opts.special);
    if (opts.space !== undefined) await this.input('o-space').setChecked(opts.space);
  }

  async setDateRange(start: string, end: string): Promise<void> {
    await this.input('o-start').fill(start);
    await this.input('o-end').fill(end);
  }

  option(index: number): Locator {
    return this.root.locator(`.o-opt[data-i="${index}"]`);
  }

  async setOption(index: number, value: string): Promise<void> {
    await this.option(index).fill(value);
  }

  async addOption(value?: string): Promise<void> {
    const before = await this.root.locator('.o-opt').count();
    await this.input('o-optadd').click();
    await expect(this.root.locator('.o-opt')).toHaveCount(before + 1);
    if (value !== undefined) await this.setOption(before, value);
  }

  async removeOption(index: number): Promise<void> {
    await this.root.locator(`.o-optdel[data-i="${index}"]`).click();
  }

  async setOptions(values: string[]): Promise<void> {
    const current = await this.root.locator('.o-opt').count();
    for (let i = current; i < values.length; i++) await this.addOption();
    for (let i = 0; i < values.length; i++) await this.setOption(i, values[i]);
  }

  async duplicateField(): Promise<void> {
    await this.input('o-dup').click();
  }

  async removeField(): Promise<void> {
    await this.input('o-del').click();
  }
}
