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
      endereco        TEXT,
      cep             TEXT,
      telefone        TEXT,
      email           TEXT,
      site            TEXT,
      acessibilidade  INTEGER NOT NULL DEFAULT 0,
      lat             REAL,
      lng             REAL,
      synced_at       TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS events (
      id                    INTEGER PRIMARY KEY AUTOINCREMENT,
      platform              TEXT NOT NULL DEFAULT 'sympla',
      external_id           TEXT NOT NULL,
      source_url            TEXT NOT NULL,
      title                 TEXT NOT NULL,
      subtitle              TEXT,
      description_short     TEXT,
      description_long      TEXT,
      start_at              TEXT NOT NULL,
      end_at                TEXT NOT NULL,
      start_time            TEXT,
      end_time              TEXT,
      price                 TEXT,
      language              TEXT,
      tags                  TEXT,
      proprietario          TEXT,
      avatar_url            TEXT,
      links                 TEXT,
      venue_name            TEXT,
      venue_address         TEXT,
      venue_city            TEXT,
      telefone              TEXT,
      email                 TEXT,
      site                  TEXT,
      cep                   TEXT,
      acessibilidade        INTEGER NOT NULL DEFAULT 0,
      classificacao_etaria  TEXT,
      latitude              REAL,
      longitude             REAL,
      mapas_space_id        INTEGER,
      match_score           INTEGER NOT NULL DEFAULT 0,
      match_status          TEXT NOT NULL DEFAULT 'pending'
                              CHECK (match_status IN ('matched', 'suggested', 'pending')),
      match_note            TEXT,
      review_status         TEXT NOT NULL DEFAULT 'pending'
                              CHECK (review_status IN ('pending', 'auto_approved', 'approved', 'rejected')),
      import_status         TEXT NOT NULL DEFAULT 'pending'
                              CHECK (import_status IN ('pending', 'exported', 'imported', 'failed')),
      raw_json              TEXT,
      synced_at             TEXT NOT NULL,
      UNIQUE (platform, external_id)
    );

    CREATE TABLE IF NOT EXISTS languages (
      id    INTEGER PRIMARY KEY AUTOINCREMENT,
      nome  TEXT NOT NULL UNIQUE
    );

    CREATE TABLE IF NOT EXISTS event_languages (
      event_id    INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
      language_id INTEGER NOT NULL REFERENCES languages(id) ON DELETE CASCADE,
      PRIMARY KEY (event_id, language_id)
    );

    CREATE TABLE IF NOT EXISTS seals (
      id           INTEGER PRIMARY KEY AUTOINCREMENT,
      external_id  INTEGER,
      nome         TEXT NOT NULL,
      descricao    TEXT,
      UNIQUE (external_id)
    );

    CREATE TABLE IF NOT EXISTS event_seals (
      event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
      seal_id  INTEGER NOT NULL REFERENCES seals(id) ON DELETE CASCADE,
      PRIMARY KEY (event_id, seal_id)
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

  const columns = db.prepare("PRAGMA table_info(events)").all() as Array<{ name: string }>
  const colNames = new Set(columns.map(c => c.name))

  if (!colNames.has('start_time')) {
    db.exec(`ALTER TABLE events ADD COLUMN start_time TEXT`)
  }
  if (!colNames.has('end_time')) {
    db.exec(`ALTER TABLE events ADD COLUMN end_time TEXT`)
  }
  if (!colNames.has('venue_name')) {
    db.exec(`ALTER TABLE events ADD COLUMN venue_name TEXT`)
  }
  if (!colNames.has('venue_address')) {
    db.exec(`ALTER TABLE events ADD COLUMN venue_address TEXT`)
  }
  if (!colNames.has('venue_city')) {
    db.exec(`ALTER TABLE events ADD COLUMN venue_city TEXT`)
  }
  if (!colNames.has('telefone')) {
    db.exec(`ALTER TABLE events ADD COLUMN telefone TEXT`)
  }
  if (!colNames.has('email')) {
    db.exec(`ALTER TABLE events ADD COLUMN email TEXT`)
  }
  if (!colNames.has('site')) {
    db.exec(`ALTER TABLE events ADD COLUMN site TEXT`)
  }
  if (!colNames.has('cep')) {
    db.exec(`ALTER TABLE events ADD COLUMN cep TEXT`)
  }
  if (!colNames.has('acessibilidade')) {
    db.exec(`ALTER TABLE events ADD COLUMN acessibilidade INTEGER NOT NULL DEFAULT 0`)
  }
  if (!colNames.has('classificacao_etaria')) {
    db.exec(`ALTER TABLE events ADD COLUMN classificacao_etaria TEXT`)
  }
  if (!colNames.has('latitude')) {
    db.exec(`ALTER TABLE events ADD COLUMN latitude REAL`)
  }
  if (!colNames.has('longitude')) {
    db.exec(`ALTER TABLE events ADD COLUMN longitude REAL`)
  }

  const vcColumns = db.prepare("PRAGMA table_info(venue_cache)").all() as Array<{ name: string }>
  const vcColNames = new Set(vcColumns.map(c => c.name))

  if (!vcColNames.has('endereco')) {
    db.exec(`ALTER TABLE venue_cache ADD COLUMN endereco TEXT`)
  }
  if (!vcColNames.has('cep')) {
    db.exec(`ALTER TABLE venue_cache ADD COLUMN cep TEXT`)
  }
  if (!vcColNames.has('telefone')) {
    db.exec(`ALTER TABLE venue_cache ADD COLUMN telefone TEXT`)
  }
  if (!vcColNames.has('email')) {
    db.exec(`ALTER TABLE venue_cache ADD COLUMN email TEXT`)
  }
  if (!vcColNames.has('site')) {
    db.exec(`ALTER TABLE venue_cache ADD COLUMN site TEXT`)
  }
  if (!vcColNames.has('acessibilidade')) {
    db.exec(`ALTER TABLE venue_cache ADD COLUMN acessibilidade INTEGER NOT NULL DEFAULT 0`)
  }
}
