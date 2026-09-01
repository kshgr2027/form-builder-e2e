import type { APIRequestContext } from '@playwright/test';

/**
 * Helpers for talking to Laravel directly (CSRF-protected `web` routes).
 *
 * Flow: GET a page in the SAME APIRequestContext so the `laravel_session`
 * cookie is stored, scrape the `<meta name="csrf-token">` value, then send it
 * as `X-CSRF-TOKEN` (and/or `_token` in the body) on the POST.
 */

const META_CSRF_RE = /<meta\s+name=["']csrf-token["']\s+content=["']([^"']+)["']/i;

export async function fetchCsrfToken(ctx: APIRequestContext, fromPath: string): Promise<string> {
  const res = await ctx.get(fromPath);
  if (!res.ok()) {
    throw new Error(`Could not load ${fromPath} to read CSRF token (HTTP ${res.status()})`);
  }
  const html = await res.text();
  const m = html.match(META_CSRF_RE);
  if (!m) {
    throw new Error(`No <meta name="csrf-token"> found on ${fromPath}`);
  }
  return m[1];
}

export type FormValue = string | number | boolean | null | undefined | Array<string | number>;

/**
 * URL-encode a body, expanding arrays to repeated `key[]=v` pairs
 * (Playwright's `form:` option cannot express repeated keys).
 */
export function encodeForm(fields: Record<string, FormValue>): string {
  const parts: string[] = [];
  const push = (k: string, v: string | number | boolean) =>
    parts.push(`${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);

  for (const [key, value] of Object.entries(fields)) {
    if (value === null || value === undefined) continue;
    if (Array.isArray(value)) {
      const arrKey = key.endsWith('[]') ? key : `${key}[]`;
      if (value.length === 0) push(arrKey, '');
      else value.forEach((v) => push(arrKey, v));
    } else {
      push(key, value);
    }
  }
  return parts.join('&');
}

export const JSON_HEADERS = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
};

export const FORM_HEADERS = {
  'Content-Type': 'application/x-www-form-urlencoded',
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
};
