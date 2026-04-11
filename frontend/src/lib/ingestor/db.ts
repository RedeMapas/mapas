import BetterSqlite3 from 'better-sqlite3'

type Database = BetterSqlite3.Database
import { resolve } from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const DB_PATH = process.env['INGESTOR_DB_PATH']
  ?? resolve(__filename, '../../../../data/ingestor.db')

let _db: Database | null = null

export function getDb(): Database {
  if (!_db) {
    _db = new BetterSqlite3(DB_PATH)
    _db.exec('PRAGMA journal_mode = WAL')
    _db.exec('PRAGMA foreign_keys = ON')
    migrate(_db)
  }
  return _db
}

function migrate(db: Database) {
  db.exec(`
    CREATE TABLE IF NOT EXISTS venue_cache (
      id              INTEGER PRIMARY KEY AUTOINCREMENT,
      mapas_space_id  INTEGER NOT NULL UNIQUE,
      name            TEXT NOT NULL,
      normalized_name TEXT NOT NULL,
      city            TEXT NOT NULL,
      lat             REAL,
      lng             REAL,
      synced_at       TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS events (
      id                INTEGER PRIMARY KEY AUTOINCREMENT,
      platform          TEXT NOT NULL DEFAULT 'sympla',
      external_id       TEXT NOT NULL,
      source_url        TEXT NOT NULL,
      title             TEXT NOT NULL,
      subtitle          TEXT,
      description_short TEXT,
      description_long  TEXT,
      start_at          TEXT NOT NULL,
      end_at            TEXT NOT NULL,
      price             TEXT,
      language          TEXT,
      tags              TEXT,
      proprietario      TEXT,
      avatar_url        TEXT,
      links             TEXT,
      mapas_space_id    INTEGER,
      match_score       INTEGER NOT NULL DEFAULT 0,
      match_status      TEXT NOT NULL DEFAULT 'pending'
                          CHECK (match_status IN ('matched', 'suggested', 'pending')),
      match_note        TEXT,
      review_status     TEXT NOT NULL DEFAULT 'pending'
                          CHECK (review_status IN ('pending', 'auto_approved', 'approved', 'rejected')),
      import_status     TEXT NOT NULL DEFAULT 'pending'
                          CHECK (import_status IN ('pending', 'exported')),
      raw_json          TEXT,
      synced_at         TEXT NOT NULL,
      UNIQUE (platform, external_id)
    );

    CREATE TABLE IF NOT EXISTS crawl_runs (
      id             INTEGER PRIMARY KEY AUTOINCREMENT,
      platform       TEXT NOT NULL DEFAULT 'sympla',
      status         TEXT NOT NULL DEFAULT 'running'
                       CHECK (status IN ('running', 'done', 'error')),
      events_fetched INTEGER NOT NULL DEFAULT 0,
      events_new     INTEGER NOT NULL DEFAULT 0,
      started_at     TEXT NOT NULL,
      finished_at    TEXT,
      error_message  TEXT
    );

    CREATE TABLE IF NOT EXISTS export_runs (
      id           INTEGER PRIMARY KEY AUTOINCREMENT,
      exported_at  TEXT NOT NULL,
      event_count  INTEGER NOT NULL DEFAULT 0
    );

    CREATE INDEX IF NOT EXISTS idx_events_review_status  ON events (review_status);
    CREATE INDEX IF NOT EXISTS idx_events_import_status  ON events (import_status);
    CREATE INDEX IF NOT EXISTS idx_events_match_status   ON events (match_status);
    CREATE INDEX IF NOT EXISTS idx_events_platform_extid ON events (platform, external_id);
  `)
}
