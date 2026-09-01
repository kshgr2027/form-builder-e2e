import { RowDataPacket } from 'mysql2/promise';
import { Db, decodeMaybeDoubleEncoded } from '../db';
import { FormStructure } from '../../data/form-structure';

export interface FormTemplateRow {
  id: number;
  title: string;
  slug: string;
  is_public: number;
  multi_submission: number | null;
  login_required: number | null;
  edit_response: number | null;
  active: number | null;
  is_published: number;
  isEverPublished: number;
  accessible_using_url: number | null;
  isAnonymous: number | null;
  is_registration_form: number | null;
  is_dynamic_url: number | null;
  review: number;
  scoring: number | null;
  redirect_method: string | null;
  redirect_url: string | null;
  success_message: string | null;
  submit_btn_txt: string;
  unique_string: string | null;
  form_structure_raw: string;
}

const COLS = `
  id, title, slug, is_public, multi_submission, login_required, edit_response, active,
  is_published, isEverPublished, accessible_using_url, isAnonymous, is_registration_form,
  is_dynamic_url, review, scoring, redirect_method, redirect_url, success_message,
  submit_btn_txt, unique_string, form_structure AS form_structure_raw
`;

export class FormTemplateRepo {
  constructor(private readonly db: Db) {}

  async findBySlug(slug: string): Promise<FormTemplateRow | null> {
    const rows = await this.db.query<(FormTemplateRow & RowDataPacket)[]>(
      `SELECT ${COLS} FROM form_templates WHERE slug = :slug LIMIT 1`,
      { slug },
    );
    return rows[0] ?? null;
  }

  async findById(id: number): Promise<FormTemplateRow | null> {
    const rows = await this.db.query<(FormTemplateRow & RowDataPacket)[]>(
      `SELECT ${COLS} FROM form_templates WHERE id = :id LIMIT 1`,
      { id },
    );
    return rows[0] ?? null;
  }

  /** Decoded `form_structure` (handles the double-encoding quirk). */
  async structureBySlug(slug: string): Promise<FormStructure | null> {
    const row = await this.findBySlug(slug);
    if (!row) return null;
    return decodeMaybeDoubleEncoded<FormStructure>(row.form_structure_raw);
  }

  /** Directly set flags the builder UI doesn't expose in this cut-down build. */
  async setFlags(id: number, flags: Partial<Record<keyof FormTemplateRow, number | string | null>>): Promise<void> {
    const keys = Object.keys(flags);
    if (keys.length === 0) return;
    const setClause = keys.map((k) => `\`${k}\` = :${k}`).join(', ');
    await this.db.execute(`UPDATE form_templates SET ${setClause} WHERE id = :id`, { ...flags, id });
  }

  async deleteById(id: number): Promise<void> {
    await this.db.execute(`DELETE FROM form_submissions WHERE form_template_id = :id`, { id });
    await this.db.execute(`DELETE FROM form_templates WHERE id = :id`, { id });
  }

  /** Safety net used by global teardown. */
  async purgeE2e(prefix: string): Promise<number> {
    const like = `${prefix}-%`;
    await this.db.execute(
      `DELETE fs FROM form_submissions fs
       JOIN form_templates ft ON ft.id = fs.form_template_id
       WHERE ft.slug LIKE :like`,
      { like },
    );
    const res = await this.db.execute(`DELETE FROM form_templates WHERE slug LIKE :like`, { like });
    return res.affectedRows ?? 0;
  }
}
