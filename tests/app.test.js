import { spawn } from "node:child_process";
import { mkdtemp, rm } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import test from "node:test";
import assert from "node:assert/strict";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const PASSWORD = "test-haslo-nie-do-publikacji";

function startServer(dataPath) {
  return new Promise((resolve, reject) => {
    const child = spawn(
      process.execPath,
      ["--disable-warning=ExperimentalWarning", "server.js"],
      {
        cwd: root,
        env: {
          ...process.env,
          HOST: "127.0.0.1",
          PORT: "0",
          ADMIN_PASSWORD: PASSWORD,
          DATA_PATH: dataPath,
        },
        stdio: ["ignore", "pipe", "pipe"],
      },
    );
    let buffer = "";
    let ready = false;
    const timer = setTimeout(() => {
      child.kill();
      reject(new Error(`serwer nie wstał: ${buffer}`));
    }, 8000);
    child.stdout.on("data", (chunk) => {
      buffer += chunk.toString();
      const match = buffer.match(/http:\/\/127\.0\.0\.1:(\d+)/);
      if (match) {
        ready = true;
        clearTimeout(timer);
        resolve({ child, base: `http://127.0.0.1:${match[1]}` });
      }
    });
    child.on("exit", (code) => {
      if (!ready && code) reject(new Error(`serwer zakończył się kodem ${code}: ${buffer}`));
    });
  });
}

async function bodyOf(response) {
  return response.text();
}

test("strony publiczne, cennik i edycja po zalogowaniu", async (t) => {
  const dir = await mkdtemp(path.join(tmpdir(), "biuro-as-"));
  const { child, base } = await startServer(path.join(dir, "cennik.sqlite"));
  t.after(async () => {
    child.kill();
    await rm(dir, { recursive: true, force: true });
  });

  const pages = [
    ["/", "Doświadczenie", "530 770 208", "logo_small.jpg", "Kompleksowa księgowość", "hero-office.jpg", "nie ma „haczyków”"],
    ["/o-firmie", "licencji Ministerstwa Finansów nr 61564/2013", "office-1209640_1920.jpg"],
    ["/oferta", "Księgi Handlowe", "60,00 zł", "Kadry i płace"],
    ["/cennik", "Podatkowa Księga Przychodów i Rozchodów", "od 200 zł", "kwotami netto"],
    ["/zdalnaksiegowosc", "Umowa bez wychodzenia z domu", "dedykowanego specjalistę"],
    ["/kontakt", "Aneta Stanik", "biuro@biuro-as.pl", "woj. Małopolskie"],
    ["/profesjonalizm", "sprawdzone systemy obsługi księgowej"],
    ["/wsparcie", "Naszą oferta jest szeroka"],
    ["/indywidualny-doradca", "papierkową robotą"],
  ];

  for (const [route, ...needles] of pages) {
    const response = await fetch(base + route);
    assert.equal(response.status, 200, route);
    assert.match(response.headers.get("x-content-type-options"), /nosniff/);
    assert.equal(response.headers.get("x-frame-options"), "DENY");
    assert.match(response.headers.get("referrer-policy"), /strict-origin/);
    const html = await bodyOf(response);
    for (const needle of needles) assert.ok(html.includes(needle), `${route} brak: ${needle}`);
    if (route === "/") {
      assert.match(html, /Poznaj ofertę[\s\S]*href="\/oferta"|href="\/oferta"[\s\S]*Poznaj ofertę/);
      assert.match(html, /href="\/oferta#ksiegowosc"/);
      assert.match(html, /Indywidualny doradca/);
      assert.match(html, /Wsparcie/);
      assert.match(html, /Profesjonalizm/);
      assert.match(html, /ZYSKUJESZ/);
      assert.equal(html.includes("data-slider"), false);
    }
    assert.equal(html.includes(PASSWORD), false);
    assert.equal(html.includes("dev-only-change-me"), false);
  }

  const logo = await fetch(base + "/img/logo_small.jpg");
  assert.equal(logo.status, 200);
  assert.match(logo.headers.get("content-type"), /image\/jpeg/);

  const favicon = await fetch(base + "/img/ico.jpg");
  assert.equal(favicon.status, 200);

  const slide = await fetch(base + "/img/woman-690036_960_720.jpg");
  assert.equal(slide.status, 200);

  const traversal = await fetch(base + "/css/../../server.js");
  assert.equal(traversal.status, 404);

  const loginPage = await fetch(base + "/logowanie");
  const loginHtml = await loginPage.text();
  const cookie = loginPage.headers.get("set-cookie") || "";
  const csrf = loginHtml.match(/name="csrf" value="([^"]+)"/)[1];
  assert.match(cookie, /HttpOnly/i);
  assert.match(cookie, /SameSite=Lax/i);
  assert.equal(/Secure/i.test(cookie), false);

  const bad = await fetch(base + "/logowanie", {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded",
      cookie,
      origin: base,
    },
    body: new URLSearchParams({ username: "admin", password: "zle", csrf }),
    redirect: "manual",
  });
  assert.equal(bad.status, 303);

  const noCsrf = await fetch(base + "/cennik", {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded",
      cookie,
      origin: base,
    },
    body: new URLSearchParams({ name: "X", price: "1 zł", csrf: "nie" }),
    redirect: "manual",
  });
  assert.equal(noCsrf.status, 403);

  const good = await fetch(base + "/logowanie", {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded",
      cookie,
      origin: base,
    },
    body: new URLSearchParams({ username: "admin", password: PASSWORD, csrf }),
    redirect: "manual",
  });
  assert.equal(good.status, 303);
  const adminCookie = good.headers.get("set-cookie");
  assert.match(adminCookie, /HttpOnly/i);

  const editor = await fetch(base + "/cennik", { headers: { cookie: adminCookie } });
  const editorHtml = await editor.text();
  assert.match(editorHtml, /Edycja pozycji/);
  const adminCsrf = editorHtml.match(/name="csrf" value="([^"]+)"/)[1];
  const id = editorHtml.match(/action="\/cennik\/(\d+)"/)[1];

  const payload = `<img src=x onerror=alert(1)>`;
  const saved = await fetch(base + `/cennik/${id}`, {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded",
      cookie: adminCookie,
      origin: base,
    },
    body: new URLSearchParams({
      csrf: adminCsrf,
      name: "Ryczałt",
      price: payload,
    }),
    redirect: "manual",
  });
  assert.equal(saved.status, 303);

  const created = await fetch(base + "/cennik", {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded",
      cookie: adminCookie,
      origin: base,
    },
    body: new URLSearchParams({
      csrf: adminCsrf,
      name: "x'); DROP TABLE price_items;--",
      price: "1 zł",
    }),
    redirect: "manual",
  });
  assert.equal(created.status, 303);

  const publicList = await fetch(base + "/cennik");
  const publicHtml = await publicList.text();
  assert.equal(publicHtml.includes("Edycja pozycji"), false);
  assert.equal(publicHtml.includes(payload), false);
  assert.match(publicHtml, /&lt;img src=x onerror=alert\(1\)&gt;/);
  assert.match(publicHtml, /DROP TABLE price_items/);
  assert.match(publicHtml, /Księgi Handlowe/);

  const foreign = await fetch(base + "/cennik", {
    method: "POST",
    headers: {
      "content-type": "application/x-www-form-urlencoded",
      cookie: adminCookie,
      origin: "https://evil.example",
    },
    body: new URLSearchParams({ csrf: adminCsrf, name: "Obca", price: "9 zł" }),
    redirect: "manual",
  });
  assert.equal(foreign.status, 403);
});
