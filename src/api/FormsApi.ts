import { APIRequestContext, APIResponse, expect } from '@playwright/test';
import { FormStructure } from '../data/form-structure';
import { fetchCsrfToken, encodeForm, JSON_HEADERS, FORM_HEADERS } from '../utils/http';
import { titleFromSlug } from '../utils/slug';

export interface FormSettings {
  accessible_using_url?: 0 | 1;
  multi_submission?: 0 | 1;
  login_required?: 0 | 1;
  edit_response?: 0 | 1;
  isAnonymous?: 0 | 1;
  review?: 0 | 1;
  scoring?: 0 | 1;
  redirect_method?: 'same_page' | 'custom';
  redirect_url?: string;
  success_message?: string;
  submit_btn_txt?: string;
}

export interface CreateFormOptions {
  slug: string;
  title?: string;
  structure: FormStructure;
  settings?: FormSettings;
}

export interface CreatedForm {
  id: number;
  slug: string;
  /** `/submit/{slug}` path — always rebuild against baseURL, do not trust the app's absolute url. */
  submitPath: string;
}

/**
 * Direct HTTP client for the form-builder `web` routes.
 * Used to arrange test data fast and to probe server-side behaviour (negative tests).
 */
export class FormsApi {
  private token: string | null = null;

  constructor(private readonly ctx: APIRequestContext) {}

  private async csrf(): Promise<string> {
    if (!this.token) this.token = await fetchCsrfToken(this.ctx, '/');
    return this.token;
  }

  /** Mirrors the JSON payload that `form-builder-v3.js#publish()` posts. */
  buildPayload(opts: CreateFormOptions): Record<string, unknown> {
    const s = opts.settings ?? {};
    return {
      title: opts.title ?? titleFromSlug(opts.slug),
      slug: opts.slug,
      form_structure: JSON.stringify(opts.structure),
      is_public: 1,
      form_type: 'survey',
      is_registration_form: 0,
      student_type_builder: '',
      student_type_builder_display: '',
      isAnonymous: s.isAnonymous ?? 0,
      accessible_using_url: s.accessible_using_url ?? 1,
      multi_submission: s.multi_submission ?? 0,
      login_required: s.login_required ?? 0,
      edit_response: s.edit_response ?? 0,
      scoring: s.scoring ?? 0,
      review: s.review ?? 0,
      isDynamicUrl: 0,
      allowed_old_phase: 0,
      redirect_method: s.redirect_method ?? 'same_page',
      success_message: s.success_message ?? 'Form Submitted Successfully!',
      submit_btn_txt: s.submit_btn_txt ?? 'Submit',
      redirect_url: s.redirect_url ?? '',
      parameter_name: [],
      weightage: [],
    };
  }

  /** Raw create — returns the response untouched (for negative/validation tests). */
  async createRaw(payload: Record<string, unknown>): Promise<APIResponse> {
    const token = await this.csrf();
    return this.ctx.post('/forms', {
      headers: { ...JSON_HEADERS, 'X-CSRF-TOKEN': token },
      data: payload,
    });
  }

  /** Happy-path create. Asserts 2xx + status:1 and returns identifiers. */
  async createForm(opts: CreateFormOptions): Promise<CreatedForm> {
    const res = await this.createRaw(this.buildPayload(opts));
    expect(res.ok(), `POST /forms failed: ${res.status()} ${await res.text()}`).toBeTruthy();
    const body = (await res.json()) as { status: number; id: number; slug: string; url?: string };
    expect(body.status).toBe(1);
    return { id: body.id, slug: body.slug, submitPath: `/submit/${body.slug}` };
  }

  /** Publish / unpublish via POST /form-status (the wizard's "Publish now" + dashboard toggle). */
  async setPublished(formId: number, active: 0 | 1 | number = 1): Promise<void> {
    const token = await this.csrf();
    const res = await this.ctx.post('/form-status', {
      headers: { ...FORM_HEADERS, 'X-CSRF-TOKEN': token },
      data: encodeForm({ _token: token, form_id: formId, active }),
    });
    expect(res.ok(), `POST /form-status failed: ${res.status()} ${await res.text()}`).toBeTruthy();
    const body = (await res.json()) as { status: string };
    expect(body.status).toBe('success');
  }

  async publish(formId: number): Promise<void> {
    return this.setPublished(formId, 1);
  }

  async unpublish(formId: number): Promise<void> {
    return this.setPublished(formId, 0);
  }

  async archive(formId: number): Promise<APIResponse> {
    const token = await this.csrf();
    return this.ctx.post(`/forms/${formId}`, {
      headers: { ...FORM_HEADERS, 'X-CSRF-TOKEN': token },
      data: encodeForm({ _token: token }),
    });
  }

  async unarchive(formId: number): Promise<APIResponse> {
    const token = await this.csrf();
    return this.ctx.post(`/forms-unarchive/${formId}`, {
      headers: { ...FORM_HEADERS, 'X-CSRF-TOKEN': token },
      data: encodeForm({ _token: token }),
    });
  }

  /** The dashboard's JSON feed: GET /forms (XHR). */
  async listForms(filter?: 'all' | 'published' | 'unpublished' | 'archived'): Promise<{
    data: Array<Record<string, unknown>>;
    counts: Record<string, number>;
  }> {
    const q = filter ? `?filter=${filter}` : '';
    const res = await this.ctx.get(`/forms${q}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
    expect(res.ok()).toBeTruthy();
    return res.json();
  }
}
