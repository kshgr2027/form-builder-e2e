import { APIRequestContext, APIResponse } from '@playwright/test';
import { fetchCsrfToken, encodeForm, FormValue } from '../utils/http';

export type SubmissionData = Record<string, FormValue>;

/**
 * Direct HTTP client for the public submission endpoint.
 * Deliberately bypasses the browser so server-side validation / access-control
 * behaviour can be asserted without client-side JS in the way.
 */
export class SubmissionsApi {
  private token: string | null = null;

  constructor(private readonly ctx: APIRequestContext) {}

  /**
   * CSRF tokens are per-session, not per-page, so we read one from `/` (always
   * reachable) — the submit page itself may legitimately return 403 in a test.
   */
  private async csrf(): Promise<string> {
    if (!this.token) this.token = await fetchCsrfToken(this.ctx, '/');
    return this.token;
  }

  /** POST /submit/{slug}. `Accept: application/json` => JSON body back. Response returned untouched. */
  async submit(slug: string, data: SubmissionData, opts?: { withCsrf?: boolean; json?: boolean }): Promise<APIResponse> {
    const withCsrf = opts?.withCsrf ?? true;
    const json = opts?.json ?? true;
    const token = withCsrf ? await this.csrf() : '';

    const body = encodeForm({ ...(withCsrf ? { _token: token } : {}), ...data });
    return this.ctx.post(`/submit/${slug}`, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        ...(json ? { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } : {}),
        ...(withCsrf ? { 'X-CSRF-TOKEN': token } : {}),
      },
      data: body,
      maxRedirects: 0,
    });
  }

  /** GET /submit/{slug} — used to probe access control (403 / 404 / 200). */
  async view(slug: string): Promise<APIResponse> {
    return this.ctx.get(`/submit/${slug}`, { maxRedirects: 0 });
  }

  /** GET /s/{unique_string} — the dashboard "short link". */
  async viewShortLink(uniqueString: string): Promise<APIResponse> {
    return this.ctx.get(`/s/${uniqueString}`, { maxRedirects: 0 });
  }
}
