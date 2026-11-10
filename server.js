import { createServer } from "node:http";
import { readFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { openDatabase } from "./lib/db.js";
import {
  clearFailures,
  clientAddress,
  createSession,
  credentialsMatch,
  csrfOk,
  destroySession,
  ensureSession,
  originOk,
  rateLimited,
  recordFailure,
  sessionCookie,
} from "./lib/auth.js";
import { cleanText, pageError, readForm, redirect, securityHeaders, send } from "./lib/http.js";
import {
  renderAbout,
  renderAdvisor,
  renderContact,
  renderCookies,
  renderHome,
  renderLogin,
  renderOffer,
  renderPrices,
  renderPrivacy,
  renderProfessional,
  renderRemote,
  renderSupport,
} from "./lib/render.js";

const root = path.dirname(fileURLToPath(import.meta.url));
const publicDir = path.join(root, "public");
const db = openDatabase(process.env.DATA_PATH || path.join(root, "data", "cennik.sqlite"));

const TYPES = {
  ".css": "text/css; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".png": "image/png",
  ".webp": "image/webp",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
  ".woff2": "font/woff2",
  ".txt": "text/plain; charset=utf-8",
  ".xml": "application/xml; charset=utf-8",
};

const pages = {
  "/": renderHome,
  "/o-firmie": renderAbout,
  "/oferta": renderOffer,
  "/zdalnaksiegowosc": renderRemote,
  "/kontakt": renderContact,
  "/indywidualny-doradca": renderAdvisor,
  "/wsparcie": renderSupport,
  "/profesjonalizm": renderProfessional,
  "/polityka-prywatnosci": renderPrivacy,
  "/polityka-cookies": renderCookies,
};

function normalizePath(pathname) {
  let value = pathname;
  try {
    value = decodeURIComponent(pathname);
  } catch {
    return null;
  }
  if (value.includes("\0")) return null;
  if (value.length > 1 && value.endsWith("/")) value = value.slice(0, -1);
  return value;
}

function publicFile(urlPath) {
  const rel = urlPath === "/favicon.ico" ? "/img/ico.jpg" : urlPath;
  const target = path.resolve(publicDir, `.${rel}`);
  if (target !== publicDir && !target.startsWith(publicDir + path.sep)) return null;
  const ext = path.extname(target).toLowerCase();
  if (!TYPES[ext]) return null;
  return { target, type: urlPath === "/favicon.ico" ? "image/jpeg" : TYPES[ext] };
}

function takeFlash(session) {
  const message = session.flash || "";
  session.flash = "";
  return message;
}

function viewContext(session, flash) {
  return {
    session,
    csrf: session.csrf,
    flash,
  };
}

async function serveStatic(req, res, file) {
  try {
    const body = await readFile(file.target);
    res.writeHead(200, {
      ...securityHeaders(req),
      "Content-Type": file.type,
      "Cache-Control": "public, max-age=86400",
      "Content-Length": body.length,
    });
    res.end(body);
  } catch {
    send(req, res, 404, pageError("Nie znaleziono", "Nie ma takiego pliku."));
  }
}

function requireMutations(req, res, session, form) {
  if (!originOk(req) || !csrfOk(session, form.csrf)) {
    send(req, res, 403, pageError("Odmowa", "Żądanie zostało odrzucone."));
    return false;
  }
  if (session.user !== "admin") {
    send(req, res, 403, pageError("Wymagane logowanie", "Zaloguj się, aby zmieniać cennik."));
    return false;
  }
  return true;
}

function priceFields(form) {
  return {
    name: cleanText(form.name, 160),
    price: cleanText(form.price, 80),
  };
}

async function handle(req, res) {
  const url = new URL(req.url || "/", `http://${req.headers.host || "localhost"}`);
  const pathname = normalizePath(url.pathname);
  if (!pathname) {
    send(req, res, 400, pageError("Błędny adres", "Nie udało się odczytać adresu."));
    return;
  }

  if (req.method === "GET" || req.method === "HEAD") {
    const file = publicFile(pathname);
    if (file) {
      if (req.method === "HEAD") {
        res.writeHead(200, { ...securityHeaders(req), "Content-Type": file.type });
        res.end();
        return;
      }
      await serveStatic(req, res, file);
      return;
    }
  }

  if (req.method !== "GET" && req.method !== "HEAD" && req.method !== "POST") {
    send(req, res, 405, pageError("Niedozwolona metoda", "Ta metoda nie jest obsługiwana."));
    return;
  }

  const bound = ensureSession(req);
  const setCookie = bound.created ? { "Set-Cookie": sessionCookie(bound.id, req) } : {};
  const { session } = bound;

  if (pathname === "/kontakt-2") {
    redirect(req, res, "/kontakt", setCookie);
    return;
  }

  if (req.method === "POST") {
    let form;
    try {
      form = await readForm(req);
    } catch {
      send(req, res, 400, pageError("Błędne żądanie", "Nie udało się odczytać formularza."), setCookie);
      return;
    }

    if (pathname === "/logowanie") {
      if (!originOk(req) || !csrfOk(session, form.csrf)) {
        send(req, res, 403, pageError("Odmowa", "Żądanie zostało odrzucone."), setCookie);
        return;
      }
      const ip = clientAddress(req);
      if (rateLimited(ip)) {
        session.flash = "Zbyt wiele prób logowania. Spróbuj ponownie później.";
        redirect(req, res, "/logowanie", setCookie);
        return;
      }
      if (!credentialsMatch(form.username, form.password)) {
        recordFailure(ip);
        session.flash = "Nieprawidłowa nazwa użytkownika lub hasło.";
        redirect(req, res, "/logowanie", setCookie);
        return;
      }
      clearFailures(ip);
      destroySession(bound.id);
      const next = createSession("admin");
      redirect(req, res, "/cennik", { "Set-Cookie": sessionCookie(next.id, req) });
      return;
    }

    if (pathname === "/wyloguj") {
      if (!originOk(req) || !csrfOk(session, form.csrf)) {
        send(req, res, 403, pageError("Odmowa", "Żądanie zostało odrzucone."), setCookie);
        return;
      }
      destroySession(bound.id);
      redirect(req, res, "/", { "Set-Cookie": sessionCookie("", req, { clear: true }) });
      return;
    }

    if (pathname === "/cennik") {
      if (!requireMutations(req, res, session, form)) return;
      const fields = priceFields(form);
      if (!fields.name || !fields.price) {
        session.flash = "Uzupełnij nazwę i cenę.";
        redirect(req, res, "/cennik", setCookie);
        return;
      }
      db.create(fields.name, fields.price);
      session.flash = "Dodano pozycję cennika.";
      redirect(req, res, "/cennik", setCookie);
      return;
    }

    const update = pathname.match(/^\/cennik\/(\d+)$/);
    if (update && req.method === "POST") {
      if (!requireMutations(req, res, session, form)) return;
      const fields = priceFields(form);
      if (!fields.name || !fields.price) {
        session.flash = "Uzupełnij nazwę i cenę.";
        redirect(req, res, "/cennik", setCookie);
        return;
      }
      const ok = db.update(Number(update[1]), fields.name, fields.price);
      session.flash = ok ? "Zapisano pozycję cennika." : "Nie znaleziono pozycji.";
      redirect(req, res, "/cennik", setCookie);
      return;
    }

    const remove = pathname.match(/^\/cennik\/(\d+)\/usun$/);
    if (remove) {
      if (!requireMutations(req, res, session, form)) return;
      const ok = db.remove(Number(remove[1]));
      session.flash = ok ? "Usunięto pozycję cennika." : "Nie znaleziono pozycji.";
      redirect(req, res, "/cennik", setCookie);
      return;
    }

    send(req, res, 404, pageError("Nie znaleziono", "Nie ma takiej strony."), setCookie);
    return;
  }

  const flash = takeFlash(session);
  if (pathname === "/logowanie") {
    if (session.user === "admin") {
      redirect(req, res, "/cennik", setCookie);
      return;
    }
    const body = renderLogin(viewContext(session, ""), { error: flash });
    send(req, res, 200, body, setCookie);
    return;
  }

  if (pathname === "/cennik") {
    const body = renderPrices(viewContext(session, flash), db.list());
    send(req, res, 200, body, setCookie);
    return;
  }

  const render = pages[pathname];
  if (!render) {
    send(req, res, 404, pageError("Nie znaleziono", "Nie ma takiej strony."), setCookie);
    return;
  }
  send(req, res, 200, render(viewContext(session, flash)), setCookie);
}

const port = Number(process.env.PORT ?? 3000);
const host = process.env.HOST || "127.0.0.1";

const server = createServer((req, res) => {
  handle(req, res).catch((error) => {
    console.error(error);
    if (!res.headersSent) {
      send(req, res, 500, pageError("Błąd", "Coś poszło nie tak. Spróbuj ponownie."));
    } else {
      res.end();
    }
  });
});

server.listen(port, host, () => {
  const address = server.address();
  console.log(`Biuro AS: http://${host}:${address.port}`);
});
