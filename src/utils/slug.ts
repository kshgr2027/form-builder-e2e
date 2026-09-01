import type { TestInfo } from '@playwright/test';

/**
 * Every test owns its data. Slugs (and form titles) are prefixed so the
 * global teardown can purge everything this suite created, and so parallel
 * workers never collide.
 */
export const E2E_PREFIX = 'fb-e2e';

let counter = 0;

/** Unique, DB-safe slug bound to the current test + worker. */
export function uniqueSlug(testInfo: TestInfo, hint = 'form'): string {
  const safeHint = hint.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'form';
  const worker = testInfo.workerIndex;
  const stamp = Date.now().toString(36);
  const n = (counter++).toString(36);
  return `${E2E_PREFIX}-${safeHint}-w${worker}-${stamp}${n}`;
}

/**
 * A form title the server will accept.
 * Server rule: /^[A-Za-z0-9 \-_.,&()']+$/  — so: letters, digits, space, - _ . , & ( ) '
 */
export function titleFromSlug(slug: string): string {
  return slug.replace(/-/g, ' ');
}

export function isE2eSlug(slug: string | null | undefined): boolean {
  return !!slug && slug.startsWith(`${E2E_PREFIX}-`);
}
