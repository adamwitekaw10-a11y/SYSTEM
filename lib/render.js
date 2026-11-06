import { escapeHtml } from "./escape.js";

export { escapeHtml };

const NAV = [
  { href: "/", label: "Strona główna" },
  { href: "/o-firmie", label: "O firmie" },
  { href: "/oferta", label: "Oferta" },
  { href: "/cennik", label: "Cennik" },
  { href: "/zdalnaksiegowosc", label: "Zdalna księgowość" },
  { href: "/kontakt", label: "Kontakt" },
];

function layout({ title, description, active, main, session, csrf, flash }) {
  const nav = NAV.map((item) => {
    const current = item.href === active ? ' aria-current="page"' : "";
    return `<a href="${item.href}"${current}>${escapeHtml(item.label)}</a>`;
  }).join("");

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
  <link rel="stylesheet" href="/css/style.css?v=3">
</head>
<body>
  <a class="skip" href="#tresc">Przejdź do treści</a>
  ${adminBar}
  <div class="topbar">
    <div class="wrap topbar-inner">
      <p>ul. Bora-Komorowskiego 22/1, 33-300 Nowy Sącz</p>
      <p>Godziny otwarcia: Poniedziałek - Piątek 09:00 - 16:00</p>
    </div>
  </div>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="logo" href="/">
        <img src="/img/logo_small.jpg" width="108" height="66" alt="Biuro Rachunkowe AS">
      </a>
      <div class="header-contact">
        <p>Skontaktuj się z nami</p>
        <p>
          <a href="tel:+48530770208">tel: 530 770 208</a>
          <a href="tel:+48185412217">tel: 18 541 22 17</a>
        </p>
      </div>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu">Menu</button>
      <nav id="menu" class="nav">${nav}</nav>
    </div>
  </header>
  ${flashHtml}
  <main id="tresc">
    ${main}
  </main>
  <footer class="site-footer">
    <div class="wrap">
      <p>Biuro Rachunkowe AS<br>
      ul. Bora-Komorowskiego 22 lok. 1<br>
      33-300 Nowy Sącz<br>
      tel. <a href="tel:+48185412217">18 541 22 17</a><br>
      kom. <a href="tel:+48530770208">530 770 208</a><br>
      e-mail <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a></p>
      <p class="footer-edit"><a href="/logowanie">Edycja cennika</a></p>
    </div>
  </footer>
  <script src="/js/site.js?v=2" defer></script>
</body>
</html>`;
}

function pageShell(opts) {
  return layout(opts);
}

const HOME_DESCRIPTION =
  "Księgowość firm. Rzetelna obsługa księgowa, kadry i płace, rozliczenia PIT. Usługi księgowe, rozliczenia podatkowe. Zapraszamy Biuro Rachunkowe AS Nowy Sącz.";

const SLIDES = [
  {
    image: "/img/lrghero-financial-advisor-team-training.jpg",
    title: "Doświadczenie",
    text: "Zapewniamy skuteczne rozwiązania wielu branżom",
    more: "/o-firmie",
  },
  {
    image: "/img/Joe-Ida-Article.jpg",
    title: "Indywidualny doradca",
    text: "Bezpośredni kontakt z zaufanym, dedykowanym księgowym",
    more: "/indywidualny-doradca",
  },
  {
    image: "/img/shutterstock_77448604.jpg",
    title: "Wsparcie",
    text: "Wskażemy Ci najlepszą drogę.",
    more: "/wsparcie",
  },
  {
    image: "/img/woman-690036_960_720.jpg",
    title: "Profesjonalizm",
    text: "Ty rozwijasz swój biznes - my księgujemy!",
    more: "/profesjonalizm",
  },
];

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

export function renderHome(ctx) {
  const slides = SLIDES.map((slide, index) => {
    const active = index === 0 ? " is-active" : "";
    return `<article class="slide${active}">
      <img src="${slide.image}" alt="">
      <div class="slide-copy">
        <h2>${escapeHtml(slide.title)}</h2>
        <p>${escapeHtml(slide.text)}</p>
        <p class="slide-actions">
          <a class="button" href="${slide.more}">Więcej</a>
          <a class="button button-ghost" href="/kontakt">Kontakt</a>
        </p>
      </div>
    </article>`;
  }).join("");

  const tabs = SLIDES.map((slide, index) => {
    const selected = index === 0 ? "true" : "false";
    return `<button class="slider-tab" type="button" aria-selected="${selected}">
      <span>${index + 1}</span> ${escapeHtml(slide.title)}
    </button>`;
  }).join("");

  const benefits = BENEFITS.map((item) => `<li>${escapeHtml(item)}</li>`).join("");

  const main = `<section class="hero" data-slider>
      <h1 class="visually-hidden">Biuro rachunkowe AS — Nowy Sącz</h1>
      <div class="slides">${slides}</div>
      <div class="slider-tabs" role="tablist">${tabs}</div>
    </section>
    <section class="benefits">
      <div class="wrap">
        <h2>Zobacz, co zyskujesz, decydując się na Biuro Rachunkowe AS:</h2>
        <ul>${benefits}</ul>
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
    body: `<div class="split">
      <div>
        <p>Nasze Biuro Rachunkowe AS, zlokalizowane w sercu Nowego Sącza, to renomowany partner w świecie finansów, oferujący kompleksowe usługi księgowe zarówno dla lokalnych przedsiębiorstw, jak i osób prywatnych. Nasza oferta skrojona jest na miarę potrzeb każdego klienta, niezależnie od profilu i skali jego działalności. Dzięki naszej elastyczności i bogatemu doświadczeniu, jesteśmy w stanie dostosować się do specyficznych wymagań każdego przedsiębiorcy, zapewniając rozwiązania, które najlepiej służą jego interesom.</p>
        <p>Wybierając współpracę z naszym biurem rachunkowym w Nowym Sączu, zyskujesz cenny czas, który możesz poświęcić na rozwój własnego biznesu. Nasz zespół, składający się z wykwalifikowanych ekspertów, jest zawsze gotowy, by sprostać Twoim oczekiwaniom i potrzebom, oferując indywidualne podejście oraz pełne zaangażowanie w każdym aspekcie współpracy.</p>
        <p>Jesteśmy dumni z posiadania licencji Ministerstwa Finansów nr 61564/2013, co jest potwierdzeniem naszej wiedzy i profesjonalizmu. Dodatkowo, dbając o bezpieczeństwo naszych klientów, posiadamy ubezpieczenie OC na kwotę 10 000 euro, które chroni przed ewentualnymi nieprzewidzianymi zdarzeniami.</p>
        <p>Biuro Rachunkowe AS to nie tylko usługi księgowe, ale również partner, na którego możesz polegać, planując przyszłość swojego biznesu w Nowym Sączu i okolicach. Zapraszamy do współpracy.</p>
      </div>
      <img src="/img/office-1209640_1920.jpg" alt="">
    </div>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

function shellFrom(page) {
  return {
    title: page.title,
    description: page.description,
    active: page.active,
    main: `<article class="wrap prose">
      <p class="crumbs"><a href="/">Strona główna</a> / ${escapeHtml(page.active === "/zdalnaksiegowosc" ? "Zdalna księgowość" : page.title.split(" - ")[0])}</p>
      <h1>${escapeHtml(page.title.split(" - ")[0])}</h1>
      ${page.body}
    </article>`,
  };
}

export function renderOffer(ctx) {
  const page = subpage({
    title: "Oferta",
    description:
      "Księgowość, kadry i płace oraz rozliczenia PIT. Biuro Rachunkowe AS w Nowym Sączu.",
    active: "/oferta",
    body: `<p class="lead">Nasze usługi:</p>
      <nav class="inpage" aria-label="Usługi">
        <a href="#ksiegowosc">Księgowość</a>
        <a href="#kadry">Kadry i płace</a>
        <a href="#pit">Rozliczenia PIT</a>
      </nav>
      <section id="ksiegowosc">
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
      <section id="kadry">
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
      <section id="pit">
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
      </section>`,
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
    body: `<h2 class="section-label">Cennik:</h2>
      ${list}
      <p>Przedstawione powyżej ceny są kwotami netto, do których należy doliczyć podatek VAT.</p>
      <p>Wskazane ceny minimalne dotyczą księgowania dokumentacji o liczbie do pięciu miesięcznie. Wynagrodzenie za nasze usługi negocjowane jest indywidualnie z każdym Klientem na etapie podpisywania umowy w zależności od formy prowadzonej księgowości, liczby księgowanych dokumentów, rodzaju transakcji, liczby zatrudnionych pracowników.</p>
      ${editor}`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderRemote(ctx) {
  const page = subpage({
    title: "Zdalna księgowość",
    description:
      "Zdalna księgowość Biura Rachunkowego AS w Nowym Sączu. Umowa i rozliczenia bez wychodzenia z domu.",
    active: "/zdalnaksiegowosc",
    body: `<p>W Biurze Rachunkowym AS w Nowym Sączu rozumiemy, jak ważna jest elastyczność oraz dostosowanie do dynamicznie zmieniającego się świata. Dlatego oferujemy kompleksowe usługi księgowe, realizowane w pełni zdalnie. Nasza zdalna księgowość to gwarancja wygody, bezpieczeństwa i profesjonalizmu, dzięki któremu możesz skupić się na rozwijaniu swojej działalności, nie martwiąc się o sprawy księgowe.</p>
      <p>Prosty i wygodny proces współpracy:</p>
      <p><strong>Kontakt i ustalenie zakresu usług:</strong> Skontaktuj się z nami, aby ustalić, jak możemy Ci pomóc. Wspólnie zdefiniujemy zakres potrzebnych prac księgowych i kadrowych.</p>
      <p><strong>Umowa bez wychodzenia z domu:</strong> Proces podpisania umowy odbywa się zdalnie, za pośrednictwem e-maila. Wystarczy podpisana i przesłana skanem umowa, abyśmy mogli rozpocząć współpracę.</p>
      <p><strong>Dedykowany księgowy:</strong> Przydzielamy Twojej firmie dedykowanego specjalistę, który będzie odpowiadać na Twoje pytania i prowadzić Twoje sprawy księgowe. Kontakt z księgowym możliwy jest w godzinach pracy naszego biura.</p>
      <p><strong>Informacje o rozliczeniach prosto na Twój e-mail:</strong> Wszelkie rozliczenia, kwoty podatków, czy wysokość składek ZUS, przekazujemy bezpośrednio na Twój adres e-mail.</p>
      <p>Dzięki naszym zdalnym usługom księgowym, zarządzanie finansami Twojej firmy staje się łatwiejsze niż kiedykolwiek. Bez potrzeby wychodzenia z domu, masz pewność, że wszystkie Twoje sprawy księgowe i kadrowe są w najlepszych rękach.</p>
      <p>Biuro Rachunkowe AS w Nowym Sączu to Twój partner w zakresie zdalnej księgowości. Zapewniamy pełną obsługę i wsparcie, dopasowane do indywidualnych potrzeb Twojej firmy. Skontaktuj się z nami, aby dowiedzieć się więcej o naszych usługach i rozpocząć wygodną współpracę.</p>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderContact(ctx) {
  const page = subpage({
    title: "Kontakt",
    description:
      "Biuro Rachunkowe AS Aneta Stanik, ul. Bora-Komorowskiego 22/1, 33-300 Nowy Sącz. Tel. 18 541 22 17, 530 770 208, biuro@biuro-as.pl.",
    active: "/kontakt",
    body: `<div class="contact-grid">
      <div>
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
    body: `<p>${ADVISOR_TEXT}</p><p><a class="button" href="/oferta">Nasza oferta</a></p>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderSupport(ctx) {
  const page = subpage({
    title: "Wsparcie",
    description: "Wsparcie księgowe Biura Rachunkowego AS w Nowym Sączu.",
    active: "/wsparcie",
    body: `<p>${ADVISOR_TEXT}</p><p><a class="button" href="/oferta">Nasza oferta</a></p>`,
  });
  return pageShell({ ...ctx, ...shellFrom(page) });
}

export function renderProfessional(ctx) {
  const page = subpage({
    title: "Profesjonalizm",
    description: "Profesjonalna obsługa księgowa Biura Rachunkowego AS.",
    active: "/profesjonalizm",
    body: `<p>Stosujemy sprawdzone systemy obsługi księgowej, zawsze indywidualnie dostosowane do konkretnych potrzeb klienta. Nasze motto: „Ty rozwijasz swój biznes – my księgujemy!”</p>
      <p><a class="button" href="/oferta">Nasza oferta</a></p>`,
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
