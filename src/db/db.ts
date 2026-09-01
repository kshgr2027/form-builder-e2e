import mysql, { Pool, RowDataPacket, ResultSetHeader } from 'mysql2/promise';
import { DbConfig } from '../utils/env';
import { FormTemplateRepo } from './repositories/FormTemplateRepo';
import { FormSubmissionRepo } from './repositories/FormSubmissionRepo';

/**
 * Thin DB access layer. SQL strings live ONLY in the repositories.
 * One worker-scoped pool per Playwright worker (see fixtures/test.ts).
 */
export class Db {
  readonly formTemplates: FormTemplateRepo;
  readonly formSubmissions: FormSubmissionRepo;

  private constructor(private readonly pool: Pool) {
    this.formTemplates = new FormTemplateRepo(this);
    this.formSubmissions = new FormSubmissionRepo(this);
  }

  static async connect(cfg: DbConfig): Promise<Db> {
    const pool = mysql.createPool({
      host: cfg.host,
      port: cfg.port,
      database: cfg.database,
      user: cfg.user,
      password: cfg.password,
      connectionLimit: 5,
      namedPlaceholders: true,
      dateStrings: true,
    });
    // Fail fast on misconfiguration rather than deep inside a test.
    const conn = await pool.getConnection();
    conn.release();
    return new Db(pool);
  }

  // mysql2's `namedPlaceholders` (object params) is not covered by its overloads,
  // so the call boundary is cast; callers stay fully typed.
  async query<T extends RowDataPacket[]>(sql: string, params?: Record<string, unknown> | unknown[]): Promise<T> {
    const [rows] = await this.pool.query<T>(sql, params as never);
    return rows;
  }

  async execute(sql: string, params?: Record<string, unknown> | unknown[]): Promise<ResultSetHeader> {
    const [res] = await this.pool.execute<ResultSetHeader>(sql, params as never);
    return res;
  }

  async close(): Promise<void> {
    await this.pool.end();
  }
}

/**
 * `form_templates.form_structure` is stored DOUBLE JSON-encoded (the Eloquent
 * `array` cast re-encodes the request's JSON string — see DEF-10). Decode until
 * we get an array.
 */
export function decodeMaybeDoubleEncoded<T = unknown>(raw: string | null): T | null {
  if (raw == null) return null;
  let value: unknown = raw;
  for (let i = 0; i < 3 && typeof value === 'string'; i++) {
    try {
      value = JSON.parse(value);
    } catch {
      return null;
    }
  }
  return value as T;
}
