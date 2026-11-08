import { escapeHtml } from "./escape.js";

export { escapeHtml };

const NAV = [
  { href: "/", label: "Strona główna" },
  { href: "/o-firmie", label: "O firmie" },
  {
    href: "/oferta",
    label: "Oferta",
    children: [
      { href: "/oferta#ksiegowosc", label: "Księgowość" },
      { href: "/oferta#kadry", label: "Kadry i płace" },
      { href: "/oferta#pit", label: "Rozliczenia PIT" },
    ],
  },
  { href: "/cennik", label: "Cennik" },
  { href: "/zdalnaksiegowosc", label: "Zdalna księgowość" },
  { href: "/kontakt", label: "Kontakt" },
];

function renderNav(active) {
  return NAV.map((item) => {
    const current = item.href === active ? ' aria-current="page"' : "";
    if (item.children?.length) {
      const submenu = item.children
        .map(
          (child) =>
            `<a href="${escapeHtml(child.href)}">${escapeHtml(child.label)}</a>`,
        )
        .join("");
      return `<div class="nav-item has-submenu">
        <a class="nav-parent" href="${item.href}"${current} aria-haspopup="true" aria-expanded="false">
          ${escapeHtml(item.label)}
          <span class="nav-chevron" aria-hidden="true">▾</span>
        </a>
        <div class="nav-submenu">
          <div class="nav-submenu-panel" role="menu">${submenu}</div>
        </div>
      </div>`;
    }
    return `<a href="${item.href}"${current}>${escapeHtml(item.label)}</a>`;
  }).join("");
}

function layout({ title, description, active, main, session, csrf, flash }) {
  const nav = renderNav(active);

  const adminBar = session?.user
    ? `<div class="admin-bar">
        <p>Zalogowano. Edycja cennika jest włączona.</p>
        <a href="/cennik">Cennik</a>
        <form method="post" action="/wyloguj">
          <input type="hidden" name="csrf" value="${escapeHtml(csrf)}">
          <button type="submit">Wyloguj</button>
        </form>
      </div>`
    : "";

  const flashHtml = flash
    ? `<p class="flash" role="status">${escapeHtml(flash)}</p>`
    : "";

  return `<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${escapeHtml(title)}</title>
  <meta name="description" content="${escapeHtml(description)}">
  <link rel="icon" href="/img/ico.jpg" type="image/jpeg">
  <link rel="apple-touch-icon" href="/img/ico.jpg">
  <link rel="stylesheet" href="/css/style.css?v=48">
</head>
<body>
  <a class="skip" href="#tresc">Przejdź do treści</a>
  ${adminBar}
  <div class="topbar">
    <div class="wrap topbar-inner">
      <p class="top-item">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5h2.6l1.2 3-1.9 1.1a12.2 12.2 0 0 0 6.1 6.1l1.1-1.9 3 1.2v2.6a1.7 1.7 0 0 1-1.7 1.7A13.4 13.4 0 0 1 4.9 5.2a1.7 1.7 0 0 1 1.7-1.7z"/></svg>
        <a href="tel:+48185412217">18 541 22 17</a>
        <a href="tel:+48530770208">530 770 208</a>
      </p>
      <p class="top-item">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2.8a6.2 6.2 0 0 0-6.2 6.2c0 4.6 6.2 11.2 6.2 11.2s6.2-6.6 6.2-11.2A6.2 6.2 0 0 0 12 2.8zm0 8.3a2.2 2.2 0 1 1 0-4.4 2.2 2.2 0 0 1 0 4.4z"/></svg>
        Nowy Sącz, ul. Bora-Komorowskiego 22/1
      </p>
      <p class="top-item">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 8.1V12l2.5 1.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Poniedziałek – Piątek: 09:00 – 16:00
      </p>
    </div>
  </div>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="/">
        <img src="/img/logo_small.jpg" width="120" height="72" alt="Biuro Rachunkowe AS">
      </a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu">Menu</button>
      <nav id="menu" class="nav">${nav}</nav>
    </div>
  </header>
  ${flashHtml}
  <main id="tresc">
    ${main}
  </main>
  <footer class="site-footer">
    <div class="wrap footer-inner">
      <div class="footer-brand">
        <p class="footer-name">Biuro Rachunkowe AS</p>
        <p>ul. Bora-Komorowskiego 22 lok. 1<br>
        33-300 Nowy Sącz</p>
      </div>
      <div class="footer-contact">
        <p>tel. <a href="tel:+48185412217">18 541 22 17</a><br>
        kom. <a href="tel:+48530770208">530 770 208</a><br>
        e-mail <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a></p>
      </div>
      ${session?.user ? `<p class="footer-edit"><a href="/cennik">Edycja cennika</a></p>` : ""}
    </div>
  </footer>
  <script src="/js/site.js?v=12" defer></script>
</body>
</html>`;
}

function pageShell(opts) {
  return layout(opts);
}

const HOME_DESCRIPTION =
  "Księgowość firm. Rzetelna obsługa księgowa, kadry i płace, rozliczenia PIT. Usługi księgowe, rozliczenia podatkowe. Zapraszamy Biuro Rachunkowe AS Nowy Sącz.";

const SERVICE_ICONS = {
  /* dwa stosy: równe ściany lewa+prawa, elipsy monet */
  books: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <ellipse cx="15.5" cy="16.5" rx="6.2" ry="2.6"/>
      <path d="M9.3 16.5v13.5M21.7 16.5v13.5"/>
      <ellipse cx="15.5" cy="21" rx="6.2" ry="2.6"/>
      <ellipse cx="15.5" cy="25.5" rx="6.2" ry="2.6"/>
      <ellipse cx="15.5" cy="30" rx="6.2" ry="2.6"/>
      <ellipse cx="32.5" cy="12" rx="6.6" ry="2.7"/>
      <path d="M25.9 12v19.5M39.1 12v19.5"/>
      <ellipse cx="32.5" cy="17" rx="6.6" ry="2.7"/>
      <ellipse cx="32.5" cy="22" rx="6.6" ry="2.7"/>
      <ellipse cx="32.5" cy="27" rx="6.6" ry="2.7"/>
      <ellipse cx="32.5" cy="31.5" rx="6.6" ry="2.7"/>
    </svg>`,
  /* dwie sylwetki: lewa bliżej, prawa lekko z tyłu */
  people: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="18.5" cy="14.5" r="6.2"/>
      <path d="M7.5 37.5c1.4-7.6 5.8-11.2 11-11.2s9.6 3.6 11 11.2"/>
      <circle cx="32.8" cy="16" r="5.4"/>
      <path d="M24.2 37.5c1.2-6.2 4.9-9.2 8.6-9.2s7.4 3 8.6 9.2"/>
    </svg>`,
  /* szersza kartka PIT, mały % */
  doc: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <path d="M9.5 6.5h20.5L39 15.2V41.5H9.5z"/>
      <path d="M29.5 6.8V15.5H39"/>
      <path d="M15 20h12M15 25.5h9.5"/>
      <circle cx="30.5" cy="28" r="1.45"/>
      <circle cx="34.6" cy="34.4" r="1.45"/>
      <path d="M29.6 35.5L35.6 27"/>
    </svg>`,
  /* prosty laptop: jeden prostokąt + linia podstawy */
  laptop: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <rect x="9" y="11" width="30" height="18" rx="2"/>
      <path d="M5.5 33h37"/>
    </svg>`,
};

const HIGHLIGHT_ICONS = {
  /* Doświadczenie – medal */
  experience: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="24" cy="19" r="10.5"/>
      <path d="M24 11.5l2 4.1 4.5.7-3.3 3.2.8 4.5L24 21.8l-4 2.2.8-4.5-3.3-3.2 4.5-.7z"/>
      <path d="M17.5 29l-2.2 10.5 8.7-4.2 8.7 4.2L30.5 29"/>
    </svg>`,
  /* Indywidualny doradca – jedna osoba */
  advisor: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="24" cy="15" r="7"/>
      <path d="M10 38.5c1.8-9 6.8-13.5 14-13.5s12.2 4.5 14 13.5"/>
    </svg>`,
  /* Wsparcie – koło ratunkowe */
  support: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="24" cy="24" r="14"/>
      <circle cx="24" cy="24" r="6"/>
      <path d="M24 10v6M24 32v6M10 24h6M32 24h6"/>
    </svg>`,
  /* Profesjonalizm – teczka */
  professionalism: `<svg viewBox="0 0 48 48" aria-hidden="true">
      <rect x="8" y="16" width="32" height="22" rx="2.5"/>
      <path d="M18 16v-3.5c0-1.4 1.1-2.5 2.5-2.5h7c1.4 0 2.5 1.1 2.5 2.5V16"/>
      <path d="M8 26h32"/>
      <path d="M21.5 24.5h5"/>
    </svg>`,
};

const SERVICES = [
  { href: "/oferta#ksiegowosc", label: "Księgowość", icon: "books" },
  { href: "/oferta#kadry", label: "Kadry i płace", icon: "people" },
  { href: "/oferta#pit", label: "Rozliczenia PIT", icon: "doc" },
  { href: "/zdalnaksiegowosc", label: "Zdalna księgowość", icon: "laptop" },
];

const HIGHLIGHTS = [
  { icon: "experience", title: "Doświadczenie", text: "Wieloletnia praktyka i zaufanie klientów." },
  { icon: "advisor", title: "Indywidualny doradca", text: "Dedykowany księgowy zawsze pod ręką." },
  { icon: "support", title: "Wsparcie", text: "Wskażemy Ci najlepszą drogę." },
  { icon: "professionalism", title: "Profesjonalizm", text: "Ty rozwijasz biznes — my księgujemy." },
];

/* Exact copy from https://www.biuro-as.pl/ homepage */
const BENEFITS = [
  "oszczędzasz czas: zamiast zajmować się dokumentami możesz skupić się na rozwoju swojego biznesu,",
  "wszystkie Twoje dokumenty są weryfikowane,",
  "reprezentujemy naszych klientów przed Urzędem Skarbowym i ZUS",
  "twoja księgowość będzie prowadzona w sposób systematyczny i uporządkowany,",
  "gotowy odpowiedzieć na wszelkie pytania dedykowany księgowy będzie zawsze pod ręką,",
  "poprowadzimy ewidencję środków trwałych, rozliczymy Twoje podatki i złożymy terminowo konieczne deklaracje,",
  "przygotujemy analizy kosztów i przychodów i na bieżąco będziemy Cię informować o stanie i rozwoju Twojego przedsiębiorstwa,",
  "informacja to podstawa, staramy się odpowiedzieć na e-maile jeszcze tego samego dnia!",
  "nasze ceny zawierają kompleksową obsługę nie ma „haczyków” – sprawdź nas!",
];

const BENEFIT_CHECK = `<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M7.5 12.2l3 3 6-6.2"/></svg>`;

export function renderHome(ctx) {
  const services = SERVICES.map(
    (item) => `<a class="offer-card" href="${item.href}">
      <span class="offer-icon">${SERVICE_ICONS[item.icon]}</span>
      <span class="offer-label">${escapeHtml(item.label)}</span>
      <span class="offer-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h12M13 6l6 6-6 6"/></svg></span>
    </a>`,
  ).join("");

  const highlights = HIGHLIGHTS.map(
    (item) => `<article class="highlight">
      <div class="highlight-icon">${HIGHLIGHT_ICONS[item.icon]}</div>
      <div class="highlight-copy">
        <h3>${escapeHtml(item.title)}</h3>
        <p>${escapeHtml(item.text)}</p>
      </div>
    </article>`,
  ).join("");

  const benefits = BENEFITS.map(
    (item) => `<li class="benefit-card">
      <span class="benefit-icon">${BENEFIT_CHECK}</span>
      <p>${escapeHtml(item)}</p>
    </li>`,
  ).join("");

  const main = `<section class="hero">
      <img class="hero-photo" src="/img/hero-office.jpg" width="1920" height="1080" alt="">
      <div class="hero-shade" aria-hidden="true"></div>
      <div class="hero-blob hero-blob-left" aria-hidden="true"></div>
      <div class="hero-blob hero-blob-right" aria-hidden="true"></div>
      <div class="wrap hero-inner">
        <div class="hero-copy">
          <h1 class="hero-title">
            <span class="hero-title-top">Kompleksowa księgowość</span>
            <span class="hero-title-accent">dla Twojego biznesu</span>
          </h1>
          <p class="hero-actions">
            <a class="button button-hero" href="/oferta">Poznaj ofertę <span aria-hidden="true">→</span></a>
            <a class="button button-line button-hero" href="/kontakt">
              <svg class="btn-phone" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5h2.6l1.2 3-1.9 1.1a12.2 12.2 0 0 0 6.1 6.1l1.1-1.9 3 1.2v2.6a1.7 1.7 0 0 1-1.7 1.7A13.4 13.4 0 0 1 4.9 5.2a1.7 1.7 0 0 1 1.7-1.7z"/></svg>
              Skontaktuj się
            </a>
          </p>
        </div>
      </div>
    </section>
    <section class="offer-section">
      <div class="wrap offer-layout">
        <div class="offer-intro">
          <p class="eyebrow">Nasza oferta</p>
          <h2>Wszystko, czego potrzebuje Twoja firma</h2>
          <p>Kompleksowa obsługa księgowa, kadrowo-płacowa i podatkowa, dopasowana do wielkości i specyfiki Twojej działalności.</p>
        </div>
        <div class="offer-cards">${services}</div>
      </div>
    </section>
    <section class="highlights" aria-label="Wyróżniki">
      <svg class="highlights-arch" viewBox="0 0 1440 96" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0 0 H1440 V28 Q720 96 0 28 Z"></path>
      </svg>
      <div class="wrap highlights-grid">${highlights}</div>
    </section>
    <section class="benefits" aria-labelledby="benefits-heading">
      <div class="wrap benefits-layout">
        <figure class="benefits-media">
          <img src="/img/benefits-portrait.jpg" width="404" height="539" alt="" loading="lazy" decoding="async">
        </figure>
        <div class="benefits-content">
          <div class="benefits-head">
            <p class="eyebrow">Korzyści</p>
            <h2 id="benefits-heading">ZOBACZ, CO ZYSKUJESZ, DECYDUJĄC SIĘ NA Biuro Rachunkowe AS:</h2>
          </div>
          <ul class="benefit-grid">${benefits}</ul>
        </div>
      </div>
    </section>`;

  return pageShell({
    ...ctx,
    title: "Księgowość Biuro AS - Biuro Rachunkowe Nowy Sącz",
    description: HOME_DESCRIPTION,
    active: "/",
    main,
  });
}

function subpage({ title, description, active, body }) {
  return { title: `${title} - Biuro rachunkowe AS Nowy Sącz - Księgowość`, description, active, body };
}

export function renderAbout(ctx) {
  const page = subpage({
    title: "O firmie",
    description:
      "Biuro Rachunkowe AS w Nowym Sączu. Licencja Ministerstwa Finansów nr 61564/2013. Ubezpieczenie OC na kwotę 10 000 euro.",
    active: "/o-firmie",
    body: `<div class="page-split">
      <div class="page-copy">
        <p>Nasze Biuro Rachunkowe AS, zlokalizowane w sercu Nowego Sącza, to renomowany partner w świecie finansów, oferujący kompleksowe usługi księgowe zarówno dla lokalnych przedsiębiorstw, jak i osób prywatnych. Nasza oferta skrojona jest na miarę potrzeb każdego klienta, niezależnie od profilu i skali jego działalności. Dzięki naszej elastyczności i bogatemu doświadczeniu, jesteśmy w stanie dostosować się do specyficznych wymagań każdego przedsiębiorcy, zapewniając rozwiązania, które najlepiej służą jego interesom.</p>
        <p>Wybierając współpracę z naszym biurem rachunkowym w Nowym Sączu, zyskujesz cenny czas, który możesz poświęcić na rozwój własnego biznesu. Nasz zespół, składający się z wykwalifikowanych ekspertów, jest zawsze gotowy, by sprostać Twoim oczekiwaniom i potrzebom, oferując indywidualne podejście oraz pełne zaangażowanie w każdym aspekcie współpracy.</p>
        <p>Jesteśmy dumni z posiadania licencji Ministerstwa Finansów nr 61564/2013, co jest potwierdzeniem naszej wiedzy i profesjonalizmu. Dodatkowo, dbając o bezpieczeństwo naszych klientów, posiadamy ubezpieczenie OC na kwotę 10 000 euro, które chroni przed ewentualnymi nieprzewidzianymi zdarzeniami.</p>
        <p>Biuro Rachunkowe AS to nie tylko usługi księgowe, ale również partner, na którego możesz polegać, planując przyszłość swojego biznesu w Nowym Sączu i okolicach. Zapraszamy do współpracy.</p>
        <p class="hero-actions">
          <a class="button" href="/oferta">Oferta <span aria-hidden="true">→</span></a>
          <a class="button button-line" href="/kontakt">Kontakt</a>
        </p>
      </div>
      ${pageMedia("/img/office-1209640_1920.jpg", { width: 1920, height: 1281 })}
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

function crumbLabel(page) {
  if (page.active === "/zdalnaksiegowosc") return "Zdalna księgowość";
  return page.title.split(" - ")[0];
}

function pageMedia(src, { width = 1200, height = 800, className = "page-side-media" } = {}) {
  return `<figure class="${className}">
    <img src="${src}" width="${width}" height="${height}" alt="" loading="lazy" decoding="async">
  </figure>`;
}

function shellFrom(page) {
  const title = page.title.split(" - ")[0];
  const crumb = crumbLabel(page);
  return {
    title: page.title,
    description: page.description,
    active: page.active,
    main: `<section class="page-hero">
      <div class="page-hero-blobs" aria-hidden="true">
        <span class="page-hero-blob page-hero-blob-a"></span>
        <span class="page-hero-blob page-hero-blob-b"></span>
      </div>
      <div class="wrap page-hero-inner">
        <p class="crumbs"><a href="/">Strona główna</a> / ${escapeHtml(crumb)}</p>
        <p class="eyebrow">Biuro Rachunkowe AS</p>
        <h1>${escapeHtml(title)}</h1>
      </div>
    </section>
    <section class="subpage-body">
      <div class="wrap subpage-panel">
        ${page.body}
      </div>
    </section>`,
  };
}

export function renderOffer(ctx) {
  const page = subpage({
    title: "Oferta",
    description:
      "Księgowość, kadry i płace oraz rozliczenia PIT. Biuro Rachunkowe AS w Nowym Sączu.",
    active: "/oferta",
    body: `<p class="lead">Nasze usługi:</p>
      <div class="offer-tabs" data-offer-tabs>
      <div class="offer-tiles" role="tablist" aria-label="Usługi">
        <button class="offer-tile is-active" type="button" role="tab" id="tab-ksiegowosc" aria-controls="ksiegowosc" aria-selected="true" data-offer-tab="ksiegowosc">
          <span class="offer-tile-icon">${SERVICE_ICONS.books}</span>
          <span class="offer-tile-label">Księgowość</span>
        </button>
        <button class="offer-tile" type="button" role="tab" id="tab-kadry" aria-controls="kadry" aria-selected="false" tabindex="-1" data-offer-tab="kadry">
          <span class="offer-tile-icon">${SERVICE_ICONS.people}</span>
          <span class="offer-tile-label">Kadry i płace</span>
        </button>
        <button class="offer-tile" type="button" role="tab" id="tab-pit" aria-controls="pit" aria-selected="false" tabindex="-1" data-offer-tab="pit">
          <span class="offer-tile-icon">${SERVICE_ICONS.doc}</span>
          <span class="offer-tile-label">Rozliczenia PIT</span>
        </button>
      </div>
      <section class="offer-block offer-panel is-active" id="ksiegowosc" role="tabpanel" aria-labelledby="tab-ksiegowosc" data-offer-panel="ksiegowosc">
        ${pageMedia("/img/banner-doswiadczenie.jpg", {
          width: 1280,
          height: 720,
          className: "offer-block-media offer-block-media--desk",
        })}
        <h2>Księgowość</h2>
        <p>Nasza oferta skierowana jest głównie do małych i średnich przedsiębiorstw. Uwalniamy Państwa od ciężaru prowadzenia księgowości, gwarantując dyskrecję, terminowość, sumienność i rzetelność zgodnie z obowiązującymi przepisami. Przejmując na siebie ciężar kontaktów z urzędami oszczędzamy Państwa czas i pieniądze.</p>
        <p>Biuro Rachunkowe AS oferuje kompleksową obsługę księgową dla małych i średnich podmiotów gospodarczych o różnym profilu działalności i formie prawnej.</p>
        <p>Świadczymy profesjonalne usługi księgowe w zakresie:</p>
        <ul>
          <li>prowadzenia pełnej księgowości (Księgi Handlowe),</li>
          <li>prowadzenia księgowości uproszczonej (Podatkowe Księgi Przychodów i Rozchodów, Ryczałt),</li>
          <li>Ewidencji środków trwałych i wyposażenia.</li>
          <li>Ewidencji zakupów i sprzedaży dla potrzeb podatku VAT,</li>
          <li>Przygotowania deklaracji podatkowych PIT, VAT, CIT, PFRON i innych,</li>
          <li>Przygotowania deklaracji dla ZUS i ich przesyłu elektronicznego,</li>
          <li>Sporządzania rocznych sprawozdań finansowych,</li>
          <li>Przygotowania rocznych rozliczeń podatkowych,</li>
          <li>Realizowanie indywidualnych potrzeb Klienta z zakresu księgowości,</li>
        </ul>
        <p>W ramach kompleksowej obsługi Biuro Rachunkowe AS zapewnia Klientom:</p>
        <ul>
          <li>reprezentowanie ich przed urzędami skarbowymi, ZUS, KRS, GUS, PFRON</li>
          <li>bezpłatną pomoc w założeniu działalności gospodarczej osobom fizycznym (wypełnianiu deklaracji zgłoszeniowych, wniosków rejestracyjnych)</li>
          <li>możliwość łączności z nami za pośrednictwem serwera</li>
          <li>dużą dyspozycyjność i indywidualne podejście do każdego klienta.</li>
        </ul>
        <p>Biuro przejmuje odpowiedzialność za terminowe przygotowanie wszelkich niezbędnych dokumentów do rozliczeń z ZUS-em, Urzędem Skarbowym, GUS-em, PFRON-em.</p>
      </section>
      <section class="offer-block offer-panel" id="kadry" role="tabpanel" aria-labelledby="tab-kadry" data-offer-panel="kadry">
        ${pageMedia("/img/kadry-banner.jpg", {
          width: 1600,
          height: 900,
          className: "offer-block-media offer-block-media--people",
        })}
        <h2>Kadry i płace</h2>
        <p>Dla tych z Państwa , którzy całość spraw związanych prowadzeniem kadr i płac chcą przekazać do obsługi przez biuro rachunkowe możemy zaoferować:</p>
        <ul>
          <li>kompleksową obsługę kadrowo-płacową,</li>
          <li>prowadzenie kart wynagrodzeń</li>
          <li>przygotowywanie dokumentów związanych z zatrudnieniem i rozwiązaniem stosunku pracy,</li>
          <li>prowadzenie akt osobowych pracowników,</li>
          <li>prowadzenie ewidencji badań wstępnych, okresowych, kontrolnych,</li>
          <li>ewidencjonowanie czasu pracy i nieobecności,</li>
          <li>obsługa zatrudnienia na podstawie umów cywilnoprawnych (przygotowywanie umów, rachunków),</li>
          <li>sporzadzenie list płac,</li>
          <li>sporządzanie i przekazywanie deklaracji rozliczeniowych do ZUS i US, PFRON</li>
          <li>sporzadzenie deklaracji i zaświadczeń dla pracowników,</li>
          <li>obliczanie należnych składek ubezpieczeniowych, zaliczek na podatek od wynagrodzeń i umów cywilnoprawnych,</li>
        </ul>
        <p>Usługi kadrowo-płacowe prowadzimy przy wsparciu licencjonowanych systemów informatycznych.</p>
      </section>
      <section class="offer-block offer-panel" id="pit" role="tabpanel" aria-labelledby="tab-pit" data-offer-panel="pit">
        ${pageMedia("/img/banner-doradca.jpg", {
          width: 1280,
          height: 720,
          className: "offer-block-media offer-block-media--docs",
        })}
        <h2>Rozliczenia PIT</h2>
        <p><strong>Rozliczanie dochodów za 2023 rok – zeznania roczne PIT-36, PIT-36L PIT-37 oraz PIT-28</strong></p>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th scope="col"></th>
                <th scope="col">Rozliczenie jednej osoby</th>
                <th scope="col">Rozliczenie wspólne małżonków<br>Rozliczenie wspólne z dzieckiem</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">Jeden dokument potwierdzający źródło dochodów – PIT-11</th>
                <td>60,00 zł</td>
                <td>80,00 zł</td>
              </tr>
              <tr>
                <th scope="row">PIT-36 / PIT-28 działalność gospodarcza, praca za granicą</th>
                <td>120,00 zł</td>
                <td>120,00 zł</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="note">Podane ceny są cenami brutto.</p>
        <p>Oferta dla naszych klientów:</p>
        <ul>
          <li>jeżeli zeznanie roczne opiera się tylko na wynikach z działalności gospodarczej, którą rozliczamy i nie występują żadne doliczenia, odliczenia i ulgi, to wówczas sporządzamy je w ramach obsługi księgowej firmy, bez żadnych dodatkowych opłat</li>
          <li>w pozostałych przypadkach obowiązują powyższe ceny pomniejszone o 10,00 zł</li>
        </ul>
      </section>
      </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

function priceEditor(items, csrf) {
  const rows = items
    .map(
      (item) => `<li class="editor-row">
        <form method="post" action="/cennik/${Number(item.id)}">
          <input type="hidden" name="csrf" value="${escapeHtml(csrf)}">
          <label>Nazwa <input name="name" required maxlength="160" value="${escapeHtml(item.name)}"></label>
          <label>Cena <input name="price" required maxlength="80" value="${escapeHtml(item.price)}"></label>
          <button type="submit">Zapisz</button>
        </form>
        <form method="post" action="/cennik/${Number(item.id)}/usun" data-confirm="Usunąć tę pozycję cennika?">
          <input type="hidden" name="csrf" value="${escapeHtml(csrf)}">
          <button type="submit" class="danger">Usuń</button>
        </form>
      </li>`,
    )
    .join("");

  return `<section class="editor">
    <h2>Edycja pozycji</h2>
    <p>Pozycja na stronie wyświetla się jako nazwa i cena w jednym wierszu. Zmiany są od razu widoczne dla odwiedzających.</p>
    <ul class="editor-list">${rows}</ul>
    <h3>Nowa pozycja</h3>
    <form class="editor-add" method="post" action="/cennik">
      <input type="hidden" name="csrf" value="${escapeHtml(csrf)}">
      <label>Nazwa <input name="name" required maxlength="160"></label>
      <label>Cena <input name="price" required maxlength="80"></label>
      <button type="submit">Dodaj pozycję</button>
    </form>
  </section>`;
}

export function renderPrices(ctx, items) {
  const list = items.length
    ? `<ul class="price-list">${items
        .map(
          (item) =>
            `<li><span>${escapeHtml(item.name)}</span> <strong>${escapeHtml(item.price)}</strong></li>`,
        )
        .join("")}</ul>`
    : `<p>Cennik jest obecnie pusty.</p>`;

  const editor = ctx.session?.user ? priceEditor(items, ctx.csrf) : "";

  const page = subpage({
    title: "Cennik",
    description:
      "Cennik usług księgowych Biura Rachunkowego AS. Ceny netto, negocjowane indywidualnie.",
    active: "/cennik",
    body: `<div class="page-split">
      <div class="page-copy price-panel">
        <h2 class="section-label">Cennik:</h2>
        ${list}
        <div class="price-notes">
          <p>Przedstawione powyżej ceny są kwotami netto, do których należy doliczyć podatek VAT.</p>
          <p>Wskazane ceny minimalne dotyczą księgowania dokumentacji o liczbie do pięciu miesięcznie. Wynagrodzenie za nasze usługi negocjowane jest indywidualnie z każdym Klientem na etapie podpisywania umowy w zależności od formy prowadzonej księgowości, liczby księgowanych dokumentów, rodzaju transakcji, liczby zatrudnionych pracowników.</p>
        </div>
        ${editor}
      </div>
      ${pageMedia("/img/hero-office.jpg", { width: 1280, height: 720 })}
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderRemote(ctx) {
  const page = subpage({
    title: "Zdalna księgowość",
    description:
      "Zdalna księgowość Biura Rachunkowego AS w Nowym Sączu. Umowa i rozliczenia bez wychodzenia z domu.",
    active: "/zdalnaksiegowosc",
    body: `<div class="page-split">
      <div class="page-copy">
        <div class="remote-intro">
          <p>W Biurze Rachunkowym AS w Nowym Sączu rozumiemy, jak ważna jest elastyczność oraz dostosowanie do dynamicznie zmieniającego się świata. Dlatego oferujemy kompleksowe usługi księgowe, realizowane w pełni zdalnie. Nasza zdalna księgowość to gwarancja wygody, bezpieczeństwa i profesjonalizmu, dzięki któremu możesz skupić się na rozwijaniu swojej działalności, nie martwiąc się o sprawy księgowe.</p>
          <p>Prosty i wygodny proces współpracy:</p>
        </div>
        <ol class="remote-steps">
          <li><p><strong>Kontakt i ustalenie zakresu usług:</strong> Skontaktuj się z nami, aby ustalić, jak możemy Ci pomóc. Wspólnie zdefiniujemy zakres potrzebnych prac księgowych i kadrowych.</p></li>
          <li><p><strong>Umowa bez wychodzenia z domu:</strong> Proces podpisania umowy odbywa się zdalnie, za pośrednictwem e-maila. Wystarczy podpisana i przesłana skanem umowa, abyśmy mogli rozpocząć współpracę.</p></li>
          <li><p><strong>Dedykowany księgowy:</strong> Przydzielamy Twojej firmie dedykowanego specjalistę, który będzie odpowiadać na Twoje pytania i prowadzić Twoje sprawy księgowe. Kontakt z księgowym możliwy jest w godzinach pracy naszego biura.</p></li>
          <li><p><strong>Informacje o rozliczeniach prosto na Twój e-mail:</strong> Wszelkie rozliczenia, kwoty podatków, czy wysokość składek ZUS, przekazujemy bezpośrednio na Twój adres e-mail.</p></li>
        </ol>
        <div class="remote-outro">
          <p>Dzięki naszym zdalnym usługom księgowym, zarządzanie finansami Twojej firmy staje się łatwiejsze niż kiedykolwiek. Bez potrzeby wychodzenia z domu, masz pewność, że wszystkie Twoje sprawy księgowe i kadrowe są w najlepszych rękach.</p>
          <p>Biuro Rachunkowe AS w Nowym Sączu to Twój partner w zakresie zdalnej księgowości. Zapewniamy pełną obsługę i wsparcie, dopasowane do indywidualnych potrzeb Twojej firmy. Skontaktuj się z nami, aby dowiedzieć się więcej o naszych usługach i rozpocząć wygodną współpracę.</p>
          <p class="hero-actions"><a class="button" href="/kontakt">Kontakt <span aria-hidden="true">→</span></a></p>
        </div>
      </div>
      ${pageMedia("/img/zdalna-ksiegowosc.jpg", { width: 900, height: 1200, className: "page-side-media page-side-media--remote" })}
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderContact(ctx) {
  const page = subpage({
    title: "Kontakt",
    description:
      "Biuro Rachunkowe AS Aneta Stanik, ul. Bora-Komorowskiego 22/1, 33-300 Nowy Sącz. Tel. 18 541 22 17, 530 770 208, biuro@biuro-as.pl.",
    active: "/kontakt",
    body: `${pageMedia("/img/1-6.jpg", {
      width: 1900,
      height: 1010,
      className: "page-banner page-banner--contact",
    })}
    <div class="contact-grid">
      <div class="page-copy">
        <p><strong>Biuro Rachunkowe AS</strong><br><em>Aneta Stanik</em></p>
        <p>Skontaktuj się z nami – Biuro Rachunkowe AS</p>
        <p>Szukasz niezawodnego partnera w zakresie księgowości w Nowym Sączu?<br>
        Biuro Rachunkowe AS jest tu, by Ci pomóc.<br>
        Zapraszamy do bezpośredniego kontaktu, aby omówić, w jaki sposób możemy wspierać Twój biznes.</p>
        <p><strong>Adres:</strong><br>
        Biuro Rachunkowe AS Aneta Stanik<br>
        ul. Bora-Komorowskiego 22/1<br>
        33-300 Nowy Sącz woj. Małopolskie</p>
        <p><strong>Kontakt telefoniczny:</strong><br>
        Telefon stacjonarny: <a href="tel:+48185412217">18 541 22 17</a><br>
        Telefon komórkowy: <a href="tel:+48530770208">530 770 208</a></p>
        <p>E-mail: <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a></p>
        <p>Nasz zespół ekspertów księgowych jest dostępny, aby odpowiedzieć na Twoje pytania i zaproponować rozwiązania dostosowane do potrzeb Twojego biznesu. Niezależnie od tego, czy potrzebujesz pomocy w prowadzeniu księgowości, czy wsparcia w zakresie doradztwa finansowego, Biuro Rachunkowe AS jest gotowe, aby Cię wspierać.</p>
        <p>Zachęcamy do kontaktu bez względu na formę – czy to osobiście, telefonicznie, czy przez e-mail. Jesteśmy tu, aby ułatwić Ci prowadzenie biznesu, oferując profesjonalne usługi księgowe na najwyższym poziomie.</p>
      </div>
      <iframe
        title="Mapa: Biuro Rachunkowe AS"
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2587.417126271306!2d20.733742315699747!3d49.5710201793638!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x5c4d388467052915!2sBiuro+Rachunkowe+AS!5e0!3m2!1spl!2spl!4v1492596107391"
        loading="lazy"
        referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

const ADVISOR_TEXT =
  "Naszą oferta jest szeroka, ale staramy się ją dostosować do potrzeb młodych i początkujących przedsiębiorców. W początkowym okresie istnienia firmy, przedsiębiorcy bardzo często nie współpracują z biurem księgowym, biorąc na siebie wszystkie dokumenty i zobowiązania, które wiążą się z istnieniem firmy. Niepotrzebnie! Jeśli jesteś właśnie w takiej sytuacji, to pokażemy Ci, jak wielkim odciążeniem jest doświadczony księgowy, który nie tylko pomoże Ci z „papierkową robotą”, ale odpowie na wszystkie pytania i wyjaśni nieścisłości.";

export function renderAdvisor(ctx) {
  const page = subpage({
    title: "Indywidualny doradca",
    description: "Indywidualny doradca w Biurze Rachunkowym AS w Nowym Sączu.",
    active: "/indywidualny-doradca",
    body: `<div class="page-split">
      <div class="page-copy feature-page">
        <p>${ADVISOR_TEXT}</p>
        <p class="hero-actions"><a class="button" href="/oferta">Nasza oferta <span aria-hidden="true">→</span></a></p>
      </div>
      ${pageMedia("/img/banner-doradca.jpg", { width: 1280, height: 720 })}
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderSupport(ctx) {
  const page = subpage({
    title: "Wsparcie",
    description: "Wsparcie księgowe Biura Rachunkowego AS w Nowym Sączu.",
    active: "/wsparcie",
    body: `<div class="page-split">
      <div class="page-copy feature-page">
        <p>${ADVISOR_TEXT}</p>
        <p class="hero-actions"><a class="button" href="/oferta">Nasza oferta <span aria-hidden="true">→</span></a></p>
      </div>
      ${pageMedia("/img/banner-wsparcie.jpg", { width: 1280, height: 720 })}
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderProfessional(ctx) {
  const page = subpage({
    title: "Profesjonalizm",
    description: "Profesjonalna obsługa księgowa Biura Rachunkowego AS.",
    active: "/profesjonalizm",
    body: `<div class="page-split">
      <div class="page-copy feature-page">
        <p>Stosujemy sprawdzone systemy obsługi księgowej, zawsze indywidualnie dostosowane do konkretnych potrzeb klienta. Nasze motto: „Ty rozwijasz swój biznes – my księgujemy!”</p>
        <p class="hero-actions"><a class="button" href="/oferta">Nasza oferta <span aria-hidden="true">→</span></a></p>
      </div>
      ${pageMedia("/img/shutterstock_77448604.jpg", { width: 1600, height: 799 })}
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderLogin(ctx, { error = "" } = {}) {
  const message = error ? `<p class="form-error" role="alert">${escapeHtml(error)}</p>` : "";
  const main = `<article class="wrap narrow">
    <h1>Edycja cennika</h1>
    <p>Zaloguj się, aby dodawać, zmieniać i usuwać pozycje cennika.</p>
    ${message}
    <form class="login-form" method="post" action="/logowanie">
      <input type="hidden" name="csrf" value="${escapeHtml(ctx.csrf)}">
      <label>Nazwa użytkownika
        <input name="username" autocomplete="username" required maxlength="80">
      </label>
      <label>Hasło
        <input name="password" type="password" autocomplete="current-password" required maxlength="200">
      </label>
      <button type="submit">Zaloguj się</button>
    </form>
  </article>`;
  return pageShell({
    ...ctx,
    title: "Logowanie - Biuro rachunkowe AS Nowy Sącz - Księgowość",
    description: "Logowanie do edycji cennika Biura Rachunkowego AS.",
    active: "",
    main,
  });
}
