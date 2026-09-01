import * as dotenv from 'dotenv';
import * as path from 'path';

dotenv.config({ path: path.resolve(__dirname, '../../.env') });

export interface DbConfig {
  host: string;
  port: number;
  database: string;
  user: string;
  password: string;
}

export interface Env {
  baseUrl: string;
  db: DbConfig | null;
  hasDb: boolean;
  startApp: boolean;
  appDir: string;
  phpBin: string;
}

function readDbConfig(): DbConfig | null {
  const { DB_HOST, DB_DATABASE, DB_USERNAME } = process.env;
  // Password may legitimately be empty; host/db/user are the signal that DB checks are wanted.
  if (!DB_HOST || !DB_DATABASE || !DB_USERNAME) return null;
  return {
    host: DB_HOST,
    port: Number(process.env.DB_PORT ?? 3306),
    database: DB_DATABASE,
    user: DB_USERNAME,
    password: process.env.DB_PASSWORD ?? '',
  };
}

let cached: Env | undefined;

export function loadEnv(): Env {
  if (cached) return cached;

  const baseUrl = (process.env.BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/+$/, '');
  const db = readDbConfig();

  cached = {
    baseUrl,
    db,
    hasDb: db !== null,
    startApp: process.env.PW_START_APP === '1',
    appDir: process.env.PW_APP_DIR ?? '../lms-assessment',
    phpBin: process.env.PW_PHP_BIN ?? 'php',
  };
  return cached;
}

export const env = loadEnv();
