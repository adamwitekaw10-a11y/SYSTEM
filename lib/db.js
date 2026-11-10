import { mkdirSync } from "node:fs";
import path from "node:path";
import { DatabaseSync } from "node:sqlite";

const SEED = [
  ["Podatkowa Księga Przychodów i Rozchodów", "od 200 zł"],
  ["Ryczałt", "od 200 zł"],
  ["Ewidencja VAT", "50 zł"],
  ["ZUS właściciela", "50 zł"],
  ["Kadry i Płace", "50 zł / osoba"],
  ["Księgi Handlowe", "od 800 zł"],
];

export function openDatabase(filePath) {
  mkdirSync(path.dirname(filePath), { recursive: true });
  const db = new DatabaseSync(filePath);
  db.exec(`
    PRAGMA journal_mode = WAL;
    PRAGMA foreign_keys = ON;
    CREATE TABLE IF NOT EXISTS meta (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS price_items (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      price TEXT NOT NULL,
      position INTEGER NOT NULL
    );
  `);

  const seeded = db.prepare("SELECT value FROM meta WHERE key = ?").get("seeded");
  if (!seeded) {
    const insert = db.prepare(
      "INSERT INTO price_items (name, price, position) VALUES (?, ?, ?)",
    );
    db.exec("BEGIN");
    SEED.forEach((row, index) => insert.run(row[0], row[1], index + 1));
    db.prepare("INSERT INTO meta (key, value) VALUES (?, ?)").run("seeded", "1");
    db.exec("COMMIT");
  }

  return {
    list() {
      return db
        .prepare(
          "SELECT id, name, price, position FROM price_items ORDER BY position ASC, id ASC",
        )
        .all();
    },
    create(name, price) {
      const row = db.prepare("SELECT COALESCE(MAX(position), 0) AS maxPos FROM price_items").get();
      const info = db
        .prepare("INSERT INTO price_items (name, price, position) VALUES (?, ?, ?)")
        .run(name, price, Number(row.maxPos) + 1);
      return Number(info.lastInsertRowid);
    },
    update(id, name, price) {
      const info = db
        .prepare("UPDATE price_items SET name = ?, price = ? WHERE id = ?")
        .run(name, price, id);
      return info.changes === 1;
    },
    remove(id) {
      const info = db.prepare("DELETE FROM price_items WHERE id = ?").run(id);
      return info.changes === 1;
    },
  };
}
