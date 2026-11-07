import { escapeHtml } from "./escape.js";

export function securityHeaders(req) {
  const headers = {
    "X-Content-Type-Options": "nosniff",
    "X-Frame-Options": "DENY",
    "Referrer-Policy": "strict-origin-when-cross-origin",
    "Content-Security-Policy": [
      "default-src 'self'",
      "img-src 'self'",
      "style-src 'self'",
      "script-src 'self'",
      "frame-src https://www.google.com",
      "base-uri 'self'",
      "form-action 'self'",
      "frame-ancestors 'none'",
      "object-src 'none'",
    ].join("; "),
    "Permissions-Policy": "camera=(), microphone=(), geolocation=()",
    "Cross-Origin-Opener-Policy": "same-origin",
    "X-Permitted-Cross-Domain-Policies": "none",
  };
  if (req.socket?.encrypted || (process.env.TRUST_PROXY === "1" && String(req.headers["x-forwarded-proto"] || "").startsWith("https"))) {
    headers["Strict-Transport-Security"] = "max-age=31536000; includeSubDomains";
  }
  return headers;
}

export function send(req, res, status, body, extra = {}) {
  const headers = {
    ...securityHeaders(req),
    "Content-Type": "text/html; charset=utf-8",
    "Cache-Control": "no-store",
    ...extra,
  };
  res.writeHead(status, headers);
  res.end(body);
}

export function redirect(req, res, location, extra = {}) {
  const headers = {
    ...securityHeaders(req),
    Location: location,
    "Cache-Control": "no-store",
    ...extra,
  };
  res.writeHead(303, headers);
  res.end();
}

export function readForm(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    req.on("data", (chunk) => {
      size += chunk.length;
      if (size > 32_000) {
        reject(Object.assign(new Error("too large"), { status: 413 }));
        req.destroy();
        return;
      }
      chunks.push(chunk);
    });
    req.on("error", reject);
    req.on("end", () => {
      const params = new URLSearchParams(Buffer.concat(chunks).toString("utf8"));
      const data = {};
      for (const [key, value] of params) data[key] = value;
      resolve(data);
    });
  });
}

export function cleanText(value, max) {
  const text = String(value ?? "").replace(/\s+/g, " ").trim();
  if (!text || text.length > max) return null;
  if (/[\u0000-\u001F\u007F]/.test(text)) return null;
  return text;
}

export function pageError(title, message) {
  return `<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${escapeHtml(title)}</title>
  <link rel="stylesheet" href="/css/style.css?v=35">
</head>
<body>
  <main class="wrap narrow">
    <h1>${escapeHtml(title)}</h1>
    <p>${escapeHtml(message)}</p>
    <p><a href="/">Wróć na stronę główną</a></p>
  </main>
</body>
</html>`;
}
