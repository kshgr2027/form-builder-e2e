import { test } from '@playwright/test';

/**
 * Tag a test that documents a KNOWN, still-open defect.
 *
 * The test asserts the *actual* (buggy) behaviour so the suite stays green and
 * acts as a regression tripwire: if the app is fixed, the assertion flips and
 * the test fails, prompting us to update it + close the defect.
 *
 * Usage: knownDefect('DEF-01', 'Optional email field is rendered as required');
 * Then add `@known-defect` to the test title so it can be filtered.
 */
export function knownDefect(id: string, summary: string): void {
  test.info().annotations.push({ type: 'known-defect', description: `${id}: ${summary}` });
}
