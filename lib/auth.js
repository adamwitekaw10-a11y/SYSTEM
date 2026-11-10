import { randomBytes, scryptSync, timingSafeEqual } from "node:crypto";

const USERNAME = "admin";
const SESSION_MS = 8 * 60 * 60 * 1000;
const WINDOW_MS = 15 * 60 * 1000;
const MAX_ATTEMPTS = 8;

function passwordMaterial() {
  const fromEnv = process.env.ADMIN_PASSWORD;
  if (typeof fromEnv === "string" && fromEnv.length > 0) return fromEnv;
  throw new Error(
    "ADMIN_PASSWORD nie jest ustawione. Ustaw hasło przed startem, np.: ADMIN_PASSWORD='…' npm start",
  );
}

const salt = randomBytes(16);
const passwordHash = scryptSync(passwordMaterial(), salt, 64);

function passwordMatches(input) {
  const candidate = scryptSync(String(input ?? ""), salt, 64);
  return timingSafeEqual(passwordHash, candidate);
}

function usernameMatches(input) {
  const expected = Buffer.from(USERNAME);
  const given = Buffer.from(String(input ?? ""));
  if (expected.length !== given.length) {
    timingSafeEqual(expected, expected);
    return false;
  }
  return timingSafeEqual(expected, given);
}

export function credentialsMatch(username, password) {
  const passOk = passwordMatches(password);
  const userOk = usernameMatches(username);
  return passOk && userOk;
}

const sessions = new Map();
const attempts = new Map();

function firstForwardedIp(header) {
  if (typeof header !== "string") return null;
  const first = header.split(",")[0].trim();
  if (!first || first.length > 128) return null;
  if (/[\s\0]/.test(first)) return null;
  return first;
}

export function clientAddress(req) {
  if (process.env.TRUST_PROXY === "1") {
    const forwarded = firstForwardedIp(req.headers["x-forwarded-for"]);
    if (forwarded) return forwarded;
  }
  return req.socket?.remoteAddress || "unknown";
}

export function rateLimited(ip) {
  const entry = attempts.get(ip);
  if (!entry) return false;
  if (Date.now() > entry.resetAt) {
    attempts.delete(ip);
    return false;
  }
  return entry.count >= MAX_ATTEMPTS;
}

export function recordFailure(ip) {
  const now = Date.now();
  const entry = attempts.get(ip);
  if (!entry || now > entry.resetAt) {
    attempts.set(ip, { count: 1, resetAt: now + WINDOW_MS });
    return;
  }
  entry.count += 1;
}

export function clearFailures(ip) {
  attempts.delete(ip);
}

function parseCookies(req) {
  const header = req.headers.cookie;
  const out = {};
  if (!header) return out;
  for (const part of header.split(";")) {
    const index = part.indexOf("=");
    if (index === -1) continue;
    const key = part.slice(0, index).trim();
    const value = part.slice(index + 1).trim();
    if (key) out[key] = value;
  }
  return out;
}

export function createSession(user = null) {
  const id = randomBytes(32).toString("hex");
  const session = {
    csrf: randomBytes(32).toString("hex"),
    user,
    created: Date.now(),
    flash: "",
  };
  sessions.set(id, session);
  return { id, session };
}

export function destroySession(id) {
  if (id) sessions.delete(id);
}

export function readSession(req) {
  const id = parseCookies(req).sid;
  if (!id || !/^[a-f0-9]{64}$/.test(id)) return null;
  const session = sessions.get(id);
  if (!session) return null;
  if (Date.now() - session.created > SESSION_MS) {
    sessions.delete(id);
    return null;
  }
  return { id, session };
}

export function ensureSession(req) {
  const existing = readSession(req);
  if (existing) return { ...existing, created: false };
  const fresh = createSession(null);
  return { ...fresh, created: true };
}

export function isHttps(req) {
  if (process.env.TRUST_PROXY === "1") {
    const proto = req.headers["x-forwarded-proto"];
    if (typeof proto === "string" && proto.split(",")[0].trim() === "https") return true;
  }
  return Boolean(req.socket?.encrypted);
}

export function sessionCookie(id, req, { clear = false } = {}) {
  const parts = [
    `sid=${clear ? "" : id}`,
    "HttpOnly",
    "SameSite=Lax",
    "Path=/",
    `Max-Age=${clear ? 0 : Math.floor(SESSION_MS / 1000)}`,
  ];
  if (isHttps(req)) parts.push("Secure");
  return parts.join("; ");
}

export function csrfOk(session, token) {
  const expected = Buffer.from(session.csrf);
  const given = Buffer.from(String(token ?? ""));
  if (expected.length !== given.length) return false;
  return timingSafeEqual(expected, given);
}

export function originOk(req) {
  const origin = req.headers.origin;
  if (!origin) return true;
  let url;
  try {
    url = new URL(origin);
  } catch {
    return false;
  }
  const host = req.headers.host;
  return Boolean(host) && url.host === host;
}
