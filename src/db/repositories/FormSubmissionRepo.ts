import { RowDataPacket } from 'mysql2/promise';
import { Db } from '../db';

export interface FormSubmissionRow {
  id: number;
  form_template_id: number;
  userid: number | null;
  review_status: string | null;
  approval_status: number | null;
  created_at: string | null;
  submission_data: Record<string, unknown>;
}

interface RawRow extends RowDataPacket {
  id: number;
  form_template_id: number;
  userid: number | null;
  review_status: string | null;
  approval_status: number | null;
  created_at: string | null;
  submission_data: string;
}

function hydrate(r: RawRow): FormSubmissionRow {
  let data: Record<string, unknown> = {};
  try {
    // Submissions are single-encoded (Eloquent `array` cast on an actual array).
    const parsed = JSON.parse(r.submission_data);
    data = typeof parsed === 'string' ? JSON.parse(parsed) : parsed;
  } catch {
    data = {};
  }
  return {
    id: r.id,
    form_template_id: r.form_template_id,
    userid: r.userid,
    review_status: r.review_status,
    approval_status: r.approval_status,
    created_at: r.created_at,
    submission_data: data,
  };
}

export class FormSubmissionRepo {
  constructor(private readonly db: Db) {}

  async countForTemplate(templateId: number): Promise<number> {
    const rows = await this.db.query<RowDataPacket[]>(
      `SELECT COUNT(*) AS c FROM form_submissions WHERE form_template_id = :id`,
      { id: templateId },
    );
    return Number(rows[0]?.c ?? 0);
  }

  async allForTemplate(templateId: number): Promise<FormSubmissionRow[]> {
    const rows = await this.db.query<RawRow[]>(
      `SELECT id, form_template_id, userid, review_status, approval_status, created_at, submission_data
       FROM form_submissions WHERE form_template_id = :id ORDER BY id ASC`,
      { id: templateId },
    );
    return rows.map(hydrate);
  }

  async latestForTemplate(templateId: number): Promise<FormSubmissionRow | null> {
    const rows = await this.db.query<RawRow[]>(
      `SELECT id, form_template_id, userid, review_status, approval_status, created_at, submission_data
       FROM form_submissions WHERE form_template_id = :id ORDER BY id DESC LIMIT 1`,
      { id: templateId },
    );
    return rows[0] ? hydrate(rows[0]) : null;
  }

  async deleteForTemplate(templateId: number): Promise<void> {
    await this.db.execute(`DELETE FROM form_submissions WHERE form_template_id = :id`, { id: templateId });
  }
}
