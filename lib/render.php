<?php
declare(strict_types=1);

const SITE_NAME = 'Biuro Rachunkowe AS';

function site_url_cfg(): string
{
    static $url = null;
    if ($url === null) {
        global $APP_CONFIG;
        $url = rtrim((string) ($APP_CONFIG['site_url'] ?? 'https://www.biuro-as.pl'), '/');
    }
    return $url;
}

function absolute_url(string $path): string
{
    if ($path === '' || $path === '/') {
        return site_url_cfg() . '/';
    }
    return site_url_cfg() . (str_starts_with($path, '/') ? $path : '/' . $path);
}

function nav_items(): array
{
    return [
        ['href' => '/', 'label' => 'Strona główna'],
        ['href' => '/o-firmie', 'label' => 'O firmie'],
        [
            'href' => '/oferta',
            'label' => 'Oferta',
            'children' => [
                ['href' => '/oferta#ksiegowosc', 'label' => 'Księgowość'],
                ['href' => '/oferta#kadry', 'label' => 'Kadry i płace'],
                ['href' => '/oferta#pit', 'label' => 'Rozliczenia PIT'],
            ],
        ],
        ['href' => '/cennik', 'label' => 'Cennik'],
        ['href' => '/zdalnaksiegowosc', 'label' => 'Zdalna księgowość'],
        ['href' => '/kontakt', 'label' => 'Kontakt'],
    ];
}

function local_business_json(): string
{
    $site = site_url_cfg();
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'AccountingService',
        'name' => SITE_NAME,
        'alternateName' => 'Biuro Rachunkowe AS Aneta Stanik',
        'url' => $site,
        'image' => $site . '/img/logo_small.jpg',
        'telephone' => ['+48 18 541 22 17', '+48 530 770 208'],
        'email' => 'biuro@biuro-as.pl',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'ul. Bora-Komorowskiego 22/1',
            'addressLocality' => 'Nowy Sącz',
            'postalCode' => '33-300',
            'addressRegion' => 'Małopolskie',
            'addressCountry' => 'PL',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => 49.57102,
            'longitude' => 20.73374,
        ],
        'openingHoursSpecification' => [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '09:00',
            'closes' => '16:00',
        ],
        'areaServed' => 'Nowy Sącz',
        'priceRange' => '$$',
    ];
    return str_replace('<', '\\u003c', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function render_nav(string $active): string
{
    $html = '';
    foreach (nav_items() as $item) {
        $current = ($item['href'] === $active) ? ' aria-current="page"' : '';
        if (!empty($item['children'])) {
            $submenu = '';
            foreach ($item['children'] as $child) {
                $submenu .= '<a href="' . e($child['href']) . '">' . e($child['label']) . '</a>';
            }
            $html .= '<div class="nav-item has-submenu">
        <a class="nav-parent" href="' . e($item['href']) . '"' . $current . ' aria-haspopup="true" aria-expanded="false">
          ' . e($item['label']) . '
          <span class="nav-chevron" aria-hidden="true">▾</span>
        </a>
        <div class="nav-submenu">
          <div class="nav-submenu-panel" role="menu">' . $submenu . '</div>
        </div>
      </div>';
        } else {
            $html .= '<a href="' . e($item['href']) . '"' . $current . '>' . e($item['label']) . '</a>';
        }
    }
    return $html;
}

function render_cookie_banner(): string
{
    return '<div class="cookie-banner" data-cookie-banner hidden>
    <div class="wrap cookie-banner-inner">
      <p>Używamy niezbędnego cookie sesji (<code>sid</code>) do działania formularzy i logowania. Mapę Google na stronie kontaktu ładujemy dopiero po Twoim kliknięciu. Szczegóły: <a href="/polityka-prywatnosci">polityka prywatności</a> i <a href="/polityka-cookies">polityka cookies</a>.</p>
      <button type="button" class="button cookie-banner-accept" data-cookie-accept>Rozumiem</button>
    </div>
  </div>';
}

function render_footer_cta(string $active): string
{
    if ($active !== '/') {
        return '';
    }
    return '<section class="footer-cta" aria-labelledby="footer-cta-heading">
    <div class="wrap footer-cta-inner">
      <div class="footer-cta-copy">
        <p class="eyebrow">Kontakt</p>
        <h2 id="footer-cta-heading">Porozmawiajmy o księgowości Twojej firmy</h2>
        <p>Umów rozmowę z Biurem Rachunkowym AS w Nowym Sączu — pomożemy dobrać zakres obsługi do Twojej działalności.</p>
      </div>
      <div class="footer-cta-actions">
        <a class="button" href="/kontakt">Skontaktuj się <span aria-hidden="true">→</span></a>
      </div>
    </div>
  </section>';
}

function render_site_footer(?string $sessionUser): string
{
    $menuLinks = '';
    foreach (nav_items() as $item) {
        $menuLinks .= '<li><a href="' . e($item['href']) . '">' . e($item['label']) . '</a></li>';
    }
    $shortcuts = [
        ['href' => '/oferta#ksiegowosc', 'label' => 'Księgowość'],
        ['href' => '/oferta#kadry', 'label' => 'Kadry i płace'],
        ['href' => '/oferta#pit', 'label' => 'Rozliczenia PIT'],
        ['href' => '/zdalnaksiegowosc', 'label' => 'Zdalna księgowość'],
        ['href' => '/cennik', 'label' => 'Cennik'],
    ];
    $shortcutLinks = '';
    foreach ($shortcuts as $item) {
        $shortcutLinks .= '<li><a href="' . e($item['href']) . '">' . e($item['label']) . '</a></li>';
    }
    $editLink = $sessionUser
        ? '<p class="footer-edit"><a href="/admin">Panel edycji</a></p>'
        : '';
    $year = date('Y');

    return '<footer class="site-footer">
    <div class="wrap footer-inner">
      <div class="footer-brand">
        <p class="footer-name">Biuro Rachunkowe AS</p>
        <p>ul. Bora-Komorowskiego 22 lok. 1<br>
        33-300 Nowy Sącz</p>
        <p class="footer-hours">Poniedziałek – Piątek: 09:00 – 16:00</p>
      </div>
      <nav class="footer-col" aria-label="Menu">
        <p class="footer-heading">Menu</p>
        <ul class="footer-links">' . $menuLinks . '</ul>
      </nav>
      <nav class="footer-col" aria-label="Skróty">
        <p class="footer-heading">Skróty</p>
        <ul class="footer-links">' . $shortcutLinks . '</ul>
      </nav>
      <div class="footer-col footer-contact">
        <p class="footer-heading">Dane kontaktowe</p>
        <p>tel. <a href="tel:+48185412217">18 541 22 17</a><br>
        kom. <a href="tel:+48530770208">530 770 208</a><br>
        e-mail <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a></p>
        ' . $editLink . '
      </div>
    </div>
    <div class="footer-bottom">
      <div class="wrap footer-bottom-inner">
        <p>© ' . $year . ' Biuro Rachunkowe AS · Nowy Sącz</p>
        <p class="footer-legal">
          <a href="/polityka-prywatnosci">Polityka prywatności</a>
          <span aria-hidden="true">·</span>
          <a href="/polityka-cookies">Polityka cookies</a>
        </p>
      </div>
    </div>
  </footer>';
}
function layout(array $opts): string
{
    $title = (string) $opts['title'];
    $description = (string) $opts['description'];
    $active = (string) ($opts['active'] ?? '');
    $main = (string) $opts['main'];
    $sessionUser = $opts['session_user'] ?? null;
    $csrf = (string) ($opts['csrf'] ?? '');
    $flash = (string) ($opts['flash'] ?? '');
    $nav = render_nav($active);
    $canonical = absolute_url($active !== '' ? $active : '/');
    $ogImage = site_url_cfg() . '/img/logo_small.jpg';
    $jsonLd = local_business_json();

    $adminBar = $sessionUser
        ? '<div class="admin-bar">
        <p>Zalogowano</p>
        <a href="/admin">Panel edycji</a>
        <form method="post" action="/wyloguj">
          <input type="hidden" name="csrf" value="' . e($csrf) . '">
          <button type="submit">Wyloguj</button>
        </form>
      </div>'
        : '';

    $flashHtml = $flash !== ''
        ? '<p class="flash" role="status">' . e($flash) . '</p>'
        : '';

    return '<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>' . e($title) . '</title>
  <meta name="description" content="' . e($description) . '">
  <link rel="canonical" href="' . e($canonical) . '">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pl_PL">
  <meta property="og:site_name" content="' . e(SITE_NAME) . '">
  <meta property="og:title" content="' . e($title) . '">
  <meta property="og:description" content="' . e($description) . '">
  <meta property="og:url" content="' . e($canonical) . '">
  <meta property="og:image" content="' . e($ogImage) . '">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="' . e($title) . '">
  <meta name="twitter:description" content="' . e($description) . '">
  <link rel="icon" href="/img/ico.jpg" type="image/jpeg">
  <link rel="apple-touch-icon" href="/img/ico.jpg">
  <link rel="stylesheet" href="/css/style.css?v=64">
  <script type="application/ld+json">' . $jsonLd . '</script>
</head>
<body>
  <a class="skip" href="#tresc">Przejdź do treści</a>
  ' . $adminBar . '
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
      <nav id="menu" class="nav">' . $nav . '</nav>
    </div>
  </header>
  ' . $flashHtml . '
  <main id="tresc">
    ' . $main . '
  </main>
  ' . render_footer_cta($active) . '
  ' . render_site_footer(is_string($sessionUser) ? $sessionUser : null) . '
  ' . render_cookie_banner() . '
  <script src="/js/site.js?v=19" defer></script>
</body>
</html>';
}

function page_media(string $src, array $opts = []): string
{
    $width = (int) ($opts['width'] ?? 1200);
    $height = (int) ($opts['height'] ?? 800);
    $className = (string) ($opts['className'] ?? 'page-side-media');
    return '<figure class="' . e($className) . '">
    <img src="' . e($src) . '" width="' . $width . '" height="' . $height . '" alt="" loading="lazy" decoding="async">
  </figure>';
}

function crumb_label(string $active, string $title): string
{
    if ($active === '/zdalnaksiegowosc') {
        return 'Zdalna księgowość';
    }
    return explode(' - ', $title)[0];
}

function shell_from(array $page): array
{
    $titleShort = explode(' - ', $page['title'])[0];
    $crumb = crumb_label($page['active'], $page['title']);
    return [
        'title' => $page['title'],
        'description' => $page['description'],
        'active' => $page['active'],
        'main' => '<section class="page-hero">
      <div class="page-hero-blobs" aria-hidden="true">
        <span class="page-hero-blob page-hero-blob-a"></span>
        <span class="page-hero-blob page-hero-blob-b"></span>
      </div>
      <div class="wrap page-hero-inner">
        <p class="crumbs"><a href="/">Strona główna</a> / ' . e($crumb) . '</p>
        <p class="eyebrow">Biuro Rachunkowe AS</p>
        <h1>' . e($titleShort) . '</h1>
      </div>
    </section>
    <section class="subpage-body">
      <div class="wrap subpage-panel">
        ' . $page['body'] . '
      </div>
    </section>',
    ];
}

function subpage(string $title, string $description, string $active, string $body): array
{
    return [
        'title' => $title . ' - Biuro rachunkowe AS Nowy Sącz - Księgowość',
        'description' => $description,
        'active' => $active,
        'body' => $body,
    ];
}

function service_icons(): array
{
    return [
        'books' => '<svg viewBox="0 0 48 48" aria-hidden="true">
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
    </svg>',
        'people' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="18.5" cy="14.5" r="6.2"/>
      <path d="M7.5 37.5c1.4-7.6 5.8-11.2 11-11.2s9.6 3.6 11 11.2"/>
      <circle cx="32.8" cy="16" r="5.4"/>
      <path d="M24.2 37.5c1.2-6.2 4.9-9.2 8.6-9.2s7.4 3 8.6 9.2"/>
    </svg>',
        'doc' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <path d="M9.5 6.5h20.5L39 15.2V41.5H9.5z"/>
      <path d="M29.5 6.8V15.5H39"/>
      <path d="M15 20h12M15 25.5h9.5"/>
      <circle cx="30.5" cy="28" r="1.45"/>
      <circle cx="34.6" cy="34.4" r="1.45"/>
      <path d="M29.6 35.5L35.6 27"/>
    </svg>',
        'laptop' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <rect x="9" y="11" width="30" height="18" rx="2"/>
      <path d="M5.5 33h37"/>
    </svg>',
    ];
}

function highlight_icons(): array
{
    return [
        'experience' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="24" cy="19" r="10.5"/>
      <path d="M24 11.5l2 4.1 4.5.7-3.3 3.2.8 4.5L24 21.8l-4 2.2.8-4.5-3.3-3.2 4.5-.7z"/>
      <path d="M17.5 29l-2.2 10.5 8.7-4.2 8.7 4.2L30.5 29"/>
    </svg>',
        'advisor' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="24" cy="15" r="7"/>
      <path d="M10 38.5c1.8-9 6.8-13.5 14-13.5s12.2 4.5 14 13.5"/>
    </svg>',
        'support' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <circle cx="24" cy="24" r="14"/>
      <circle cx="24" cy="24" r="6"/>
      <path d="M24 10v6M24 32v6M10 24h6M32 24h6"/>
    </svg>',
        'professionalism' => '<svg viewBox="0 0 48 48" aria-hidden="true">
      <rect x="8" y="16" width="32" height="22" rx="2.5"/>
      <path d="M18 16v-3.5c0-1.4 1.1-2.5 2.5-2.5h7c1.4 0 2.5 1.1 2.5 2.5V16"/>
      <path d="M8 26h32"/>
      <path d="M21.5 24.5h5"/>
    </svg>',
    ];
}
function render_home(array $ctx): string
{
    $icons = service_icons();
    $hicons = highlight_icons();
    $services = [
        ['href' => '/oferta#ksiegowosc', 'label' => 'Księgowość', 'icon' => 'books'],
        ['href' => '/oferta#kadry', 'label' => 'Kadry i płace', 'icon' => 'people'],
        ['href' => '/oferta#pit', 'label' => 'Rozliczenia PIT', 'icon' => 'doc'],
        ['href' => '/zdalnaksiegowosc', 'label' => 'Zdalna księgowość', 'icon' => 'laptop'],
    ];
    $highlights = [
        ['icon' => 'experience', 'title' => 'Doświadczenie', 'text' => 'Wieloletnia praktyka i zaufanie klientów.'],
        ['icon' => 'advisor', 'title' => 'Indywidualny doradca', 'text' => 'Dedykowany księgowy zawsze pod ręką.'],
        ['icon' => 'support', 'title' => 'Wsparcie', 'text' => 'Wskażemy Ci najlepszą drogę.'],
        ['icon' => 'professionalism', 'title' => 'Profesjonalizm', 'text' => 'Ty rozwijasz biznes — my księgujemy.'],
    ];
    $benefits = [
        'oszczędzasz czas: zamiast zajmować się dokumentami możesz skupić się na rozwoju swojego biznesu,',
        'wszystkie Twoje dokumenty są weryfikowane,',
        'reprezentujemy naszych klientów przed Urzędem Skarbowym i ZUS',
        'twoja księgowość będzie prowadzona w sposób systematyczny i uporządkowany,',
        'gotowy odpowiedzieć na wszelkie pytania dedykowany księgowy będzie zawsze pod ręką,',
        'poprowadzimy ewidencję środków trwałych, rozliczymy Twoje podatki i złożymy terminowo konieczne deklaracje,',
        'przygotujemy analizy kosztów i przychodów i na bieżąco będziemy Cię informować o stanie i rozwoju Twojego przedsiębiorstwa,',
        'informacja to podstawa, staramy się odpowiedzieć na e-maile jeszcze tego samego dnia!',
        'nasze ceny zawierają kompleksową obsługę nie ma „haczyków” – sprawdź nas!',
    ];
    $check = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M7.5 12.2l3 3 6-6.2"/></svg>';

    $servicesHtml = '';
    foreach ($services as $item) {
        $servicesHtml .= '<a class="offer-card" href="' . e($item['href']) . '">
      <span class="offer-icon">' . $icons[$item['icon']] . '</span>
      <span class="offer-label">' . e($item['label']) . '</span>
      <span class="offer-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h12M13 6l6 6-6 6"/></svg></span>
    </a>';
    }
    $highlightsHtml = '';
    foreach ($highlights as $item) {
        $highlightsHtml .= '<article class="highlight">
      <div class="highlight-icon">' . $hicons[$item['icon']] . '</div>
      <div class="highlight-copy">
        <h3>' . e($item['title']) . '</h3>
        <p>' . e($item['text']) . '</p>
      </div>
    </article>';
    }
    $benefitsHtml = '';
    foreach ($benefits as $item) {
        $benefitsHtml .= '<li class="benefit-card">
      <span class="benefit-icon">' . $check . '</span>
      <p>' . e($item) . '</p>
    </li>';
    }

    $main = '<section class="hero" data-hero-slider>
      <div class="hero-slides" aria-hidden="true">
        <img class="hero-photo is-active" src="/img/hero-slide-1.webp" width="2048" height="682" alt="" fetchpriority="high">
        <img class="hero-photo" src="/img/hero-slide-2.webp" width="2172" height="724" alt="" loading="lazy">
      </div>
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
        <div class="offer-cards">' . $servicesHtml . '</div>
      </div>
    </section>
    <section class="highlights" aria-label="Wyróżniki">
      <svg class="highlights-arch" viewBox="0 0 1440 96" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0 0 H1440 V28 Q720 96 0 28 Z"></path>
      </svg>
      <div class="wrap highlights-grid">' . $highlightsHtml . '</div>
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
          <ul class="benefit-grid">' . $benefitsHtml . '</ul>
        </div>
      </div>
    </section>';

    return layout(array_merge($ctx, [
        'title' => 'Księgowość Biuro AS - Biuro Rachunkowe Nowy Sącz',
        'description' => 'Księgowość firm. Rzetelna obsługa księgowa, kadry i płace, rozliczenia PIT. Usługi księgowe, rozliczenia podatkowe. Zapraszamy Biuro Rachunkowe AS Nowy Sącz.',
        'active' => '/',
        'main' => $main,
    ]));
}

function render_about(array $ctx): string
{
    $page = subpage(
        'O firmie',
        'Biuro Rachunkowe AS w Nowym Sączu. Licencja Ministerstwa Finansów nr 61564/2013. Ubezpieczenie OC na kwotę 10 000 euro.',
        '/o-firmie',
        '<div class="page-split">
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
      ' . page_media('/img/office-1209640_1920.jpg', ['width' => 1920, 'height' => 1281]) . '
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}
function offer_section_media(string $key): string
{
    $map = [
        'ksiegowosc' => page_media('/img/banner-doswiadczenie.jpg', [
            'width' => 1280,
            'height' => 720,
            'className' => 'offer-block-media offer-block-media--desk',
        ]),
        'kadry' => page_media('/img/kadry-banner.jpg', [
            'width' => 1600,
            'height' => 900,
            'className' => 'offer-block-media offer-block-media--people',
        ]),
        'pit' => page_media('/img/banner-doradca.jpg', [
            'width' => 1280,
            'height' => 720,
            'className' => 'offer-block-media offer-block-media--docs',
        ]),
    ];
    return $map[$key] ?? '';
}

function render_offer(array $ctx, ?array $offer = null): string
{
    $icons = service_icons();
    $offer = $offer ?? offer_defaults();
    $labels = [
        'ksiegowosc' => ['icon' => 'books', 'label' => $offer['sections']['ksiegowosc']['title']],
        'kadry' => ['icon' => 'people', 'label' => $offer['sections']['kadry']['title']],
        'pit' => ['icon' => 'doc', 'label' => $offer['sections']['pit']['title']],
    ];

    $tiles = '';
    $panels = '';
    $first = true;
    foreach ($labels as $key => $meta) {
        $active = $first ? ' is-active' : '';
        $selected = $first ? 'true' : 'false';
        $tabindex = $first ? '' : ' tabindex="-1"';
        $tiles .= '<button class="offer-tile' . $active . '" type="button" role="tab" id="tab-' . e($key) . '" aria-controls="' . e($key) . '" aria-selected="' . $selected . '"' . $tabindex . ' data-offer-tab="' . e($key) . '">
          <span class="offer-tile-icon">' . $icons[$meta['icon']] . '</span>
          <span class="offer-tile-label">' . e($meta['label']) . '</span>
        </button>';
        $body = sanitize_offer_html((string) $offer['sections'][$key]['html']);
        $panels .= '<section class="offer-block offer-panel' . $active . '" id="' . e($key) . '" role="tabpanel" aria-labelledby="tab-' . e($key) . '" data-offer-panel="' . e($key) . '">
        ' . offer_section_media($key) . '
        <h2>' . e((string) $offer['sections'][$key]['title']) . '</h2>
        <div class="offer-richtext">' . $body . '</div>
      </section>';
        $first = false;
    }

    $page = subpage(
        'Oferta',
        'Księgowość, kadry i płace oraz rozliczenia PIT. Biuro Rachunkowe AS w Nowym Sączu.',
        '/oferta',
        '<p class="lead">' . e((string) $offer['lead']) . '</p>
      <div class="offer-tabs" data-offer-tabs>
      <div class="offer-tiles" role="tablist" aria-label="Usługi">' . $tiles . '</div>
      ' . $panels . '
      </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

function admin_edit_choices(array $offer): array
{
    return [
        [
            'href' => '/admin/oferta/lead',
            'label' => 'Nagłówek oferty',
            'hint' => 'Krótki tekst nad zakładkami (np. „Nasze usługi:”).',
        ],
        [
            'href' => '/admin/oferta/ksiegowosc',
            'label' => (string) $offer['sections']['ksiegowosc']['title'],
            'hint' => 'Treść zakładki Księgowość.',
        ],
        [
            'href' => '/admin/oferta/kadry',
            'label' => (string) $offer['sections']['kadry']['title'],
            'hint' => 'Treść zakładki Kadry i płace.',
        ],
        [
            'href' => '/admin/oferta/pit',
            'label' => (string) $offer['sections']['pit']['title'],
            'hint' => 'Treść zakładki Rozliczenia PIT (tekst i tabela).',
        ],
        [
            'href' => '/cennik',
            'label' => 'Cennik',
            'hint' => 'Dodawanie, zmiana i usuwanie pozycji cennika.',
        ],
    ];
}

function render_admin_hub(array $ctx, array $offer): string
{
    $cards = '';
    foreach (admin_edit_choices($offer) as $item) {
        $cards .= '<a class="admin-hub-card" href="' . e($item['href']) . '">
        <span class="admin-hub-card-label">' . e($item['label']) . '</span>
        <span class="admin-hub-card-hint">' . e($item['hint']) . '</span>
      </a>';
    }

    $main = '<section class="admin-hub wrap">
    <div class="admin-hub-panel">
      <p class="eyebrow">Panel edycji</p>
      <h1>Co chcesz edytować?</h1>
      <p class="admin-hub-intro">Wybierz jedną treść. Otworzy się edytor tylko dla niej — bez długiego przewijania.</p>
      <div class="admin-hub-grid">' . $cards . '</div>
    </div>
  </section>';

    return layout(array_merge($ctx, [
        'title' => 'Panel edycji - Biuro rachunkowe AS Nowy Sącz - Księgowość',
        'description' => 'Wybór treści do edycji w panelu Biura Rachunkowego AS.',
        'active' => '',
        'main' => $main,
    ]));
}

function render_offer_part_editor(array $ctx, array $offer, string $part): string
{
    $csrf = e((string) $ctx['csrf']);
    $back = '<p class="offer-admin-back"><a href="/admin">← Wróć do wyboru</a></p>';

    if ($part === 'lead') {
        $main = '<article class="wrap offer-admin">
    ' . $back . '
    <h1>Nagłówek oferty</h1>
    <p>Tekst wyświetlany nad zakładkami na stronie oferty.</p>
    <form class="offer-admin-form" method="post" action="/admin/oferta/lead">
      <input type="hidden" name="csrf" value="' . $csrf . '">
      <label class="offer-admin-lead">Nagłówek
        <input name="lead" required maxlength="200" value="' . e((string) $offer['lead']) . '">
      </label>
      <p class="offer-admin-actions">
        <button type="submit" class="button">Zapisz</button>
        <a class="button button-line" href="/admin">Anuluj</a>
      </p>
    </form>
  </article>';
    } else {
        $section = $offer['sections'][$part];
        $main = '<article class="wrap offer-admin">
    ' . $back . '
    <h1>Edycja: ' . e((string) $section['title']) . '</h1>
    <p>Edytor wizualny — zapisz, gdy skończysz.</p>
    <form class="offer-admin-form" method="post" action="/admin/oferta/' . e($part) . '" data-offer-admin-form>
      <input type="hidden" name="csrf" value="' . $csrf . '">
      <label class="offer-admin-title">Tytuł sekcji
        <input name="title_' . e($part) . '" required maxlength="120" value="' . e((string) $section['title']) . '">
      </label>
      <label class="offer-admin-body">Treść
        <textarea id="editor-' . e($part) . '" name="html_' . e($part) . '" rows="18" class="offer-admin-textarea">' . e((string) $section['html']) . '</textarea>
      </label>
      <p class="offer-admin-actions">
        <button type="submit" class="button">Zapisz</button>
        <a class="button button-line" href="/admin">Anuluj</a>
      </p>
    </form>
  </article>
  <script src="/vendor/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
  <script src="/js/offer-admin.js?v=2" defer></script>';
    }

    return layout(array_merge($ctx, [
        'title' => 'Edycja treści - Biuro rachunkowe AS Nowy Sącz - Księgowość',
        'description' => 'Edycja wybranej treści oferty Biura Rachunkowego AS.',
        'active' => '',
        'main' => $main,
    ]));
}

function price_editor(array $items, string $csrf): string
{
    $rows = '';
    foreach ($items as $item) {
        $id = (int) $item['id'];
        $rows .= '<li class="editor-row">
        <form method="post" action="/cennik/' . $id . '">
          <input type="hidden" name="csrf" value="' . e($csrf) . '">
          <label>Nazwa <input name="name" required maxlength="160" value="' . e($item['name']) . '"></label>
          <label>Cena <input name="price" required maxlength="80" value="' . e($item['price']) . '"></label>
          <button type="submit">Zapisz</button>
        </form>
        <form method="post" action="/cennik/' . $id . '/usun" data-confirm="Usunąć tę pozycję cennika?">
          <input type="hidden" name="csrf" value="' . e($csrf) . '">
          <button type="submit" class="danger">Usuń</button>
        </form>
      </li>';
    }

    return '<section class="editor">
    <p class="offer-admin-back"><a href="/admin">← Wróć do wyboru</a></p>
    <h2>Edycja pozycji</h2>
    <p>Pozycja na stronie wyświetla się jako nazwa i cena w jednym wierszu. Zmiany są od razu widoczne dla odwiedzających.</p>
    <ul class="editor-list">' . $rows . '</ul>
    <h3>Nowa pozycja</h3>
    <form class="editor-add" method="post" action="/cennik">
      <input type="hidden" name="csrf" value="' . e($csrf) . '">
      <label>Nazwa <input name="name" required maxlength="160"></label>
      <label>Cena <input name="price" required maxlength="80"></label>
      <button type="submit">Dodaj pozycję</button>
    </form>
  </section>';
}

function render_prices(array $ctx, array $items): string
{
    if ($items) {
        $list = '<ul class="price-list">';
        foreach ($items as $item) {
            $list .= '<li><span>' . e($item['name']) . '</span> <strong>' . e($item['price']) . '</strong></li>';
        }
        $list .= '</ul>';
    } else {
        $list = '<p>Cennik jest obecnie pusty.</p>';
    }

    $editor = !empty($ctx['session_user'])
        ? price_editor($items, (string) $ctx['csrf'])
        : '';

    $page = subpage(
        'Cennik',
        'Cennik usług księgowych Biura Rachunkowego AS. Ceny netto, negocjowane indywidualnie.',
        '/cennik',
        '<div class="page-split">
      <div class="page-copy price-panel">
        <h2 class="section-label">Cennik:</h2>
        ' . $list . '
        <div class="price-notes">
          <p>Przedstawione powyżej ceny są kwotami netto, do których należy doliczyć podatek VAT.</p>
          <p>Wskazane ceny minimalne dotyczą księgowania dokumentacji o liczbie do pięciu miesięcznie. Wynagrodzenie za nasze usługi negocjowane jest indywidualnie z każdym Klientem na etapie podpisywania umowy w zależności od formy prowadzonej księgowości, liczby księgowanych dokumentów, rodzaju transakcji, liczby zatrudnionych pracowników.</p>
        </div>
        ' . $editor . '
      </div>
      ' . page_media('/img/hero-office.jpg', ['width' => 1280, 'height' => 720]) . '
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}
function render_remote(array $ctx): string
{
    $page = subpage(
        'Zdalna księgowość',
        'Zdalna księgowość Biura Rachunkowego AS w Nowym Sączu. Umowa i rozliczenia bez wychodzenia z domu.',
        '/zdalnaksiegowosc',
        '<div class="page-split">
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
      ' . page_media('/img/zdalna-ksiegowosc.jpg', ['width' => 900, 'height' => 1200, 'className' => 'page-side-media page-side-media--remote']) . '
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

const MAP_EMBED_SRC = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2587.417126271306!2d20.733742315699747!3d49.5710201793638!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x5c4d388467052915!2sBiuro+Rachunkowe+AS!5e0!3m2!1spl!2spl!4v1492596107391';

function render_contact(array $ctx): string
{
    $page = subpage(
        'Kontakt',
        'Biuro Rachunkowe AS Aneta Stanik, ul. Bora-Komorowskiego 22/1, 33-300 Nowy Sącz. Tel. 18 541 22 17, 530 770 208, biuro@biuro-as.pl.',
        '/kontakt',
        page_media('/img/1-6.jpg', ['width' => 1900, 'height' => 1010, 'className' => 'page-banner page-banner--contact']) . '
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
      <div class="map-embed reveal-media" data-map-embed data-map-src="' . e(MAP_EMBED_SRC) . '">
        <div class="map-embed-prompt">
          <p>Mapa Google ładuje się dopiero po Twojej zgodzie. Po kliknięciu Google może przetwarzać dane i ustawiać własne cookies.</p>
          <button type="button" class="button" data-map-load>Pokaż mapę</button>
          <p class="map-embed-note"><a href="/polityka-prywatnosci">Polityka prywatności</a> · <a href="/polityka-cookies">Cookies</a></p>
        </div>
      </div>
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

function render_privacy(array $ctx): string
{
    $page = subpage(
        'Polityka prywatności',
        'Polityka prywatności Biura Rachunkowego AS Aneta Stanik w Nowym Sączu — informacje o przetwarzaniu danych osobowych.',
        '/polityka-prywatnosci',
        '<div class="page-copy legal-copy">
      <p>Niniejsza polityka prywatności opisuje zasady przetwarzania danych osobowych w związku z korzystaniem ze strony internetowej Biura Rachunkowego AS.</p>
      <h2>Administrator danych</h2>
      <p>Administratorem danych osobowych jest <strong>Biuro Rachunkowe AS Aneta Stanik</strong>, ul. Bora-Komorowskiego 22/1, 33-300 Nowy Sącz, e-mail: <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a>, tel. <a href="tel:+48185412217">18 541 22 17</a>, <a href="tel:+48530770208">530 770 208</a>.</p>
      <h2>Zakres i cele przetwarzania</h2>
      <p>W ramach działania strony przetwarzamy dane w zakresie niezbędnym do:</p>
      <ul>
        <li>zapewnienia bezpieczeństwa i działania formularzy oraz panelu edycji cennika (sesja i ochrona CSRF),</li>
        <li>kontaktu z osobami, które same do nas napiszą lub zadzwonią (dane podane dobrowolnie w korespondencji lub rozmowie),</li>
        <li>realizacji umów i obowiązków prawnych wobec klientów biura (poza samą stroną WWW — na podstawie umowy i przepisów).</li>
      </ul>
      <p>Strona nie prowadzi newslettera ani nie zbiera danych przez formularz kontaktowy online.</p>
      <h2>Podstawy prawne</h2>
      <p>Przetwarzanie odbywa się na podstawie art. 6 ust. 1 lit. a, b, c lub f RODO — w zależności od kontekstu (zgoda, umowa, obowiązek prawny lub prawnie uzasadniony interes administratora, np. bezpieczeństwo serwisu).</p>
      <h2>Cookies i podobne technologie</h2>
      <p>Strona używa niezbędnego cookie sesji <code>sid</code> (HttpOnly, SameSite=Lax). Szczegóły: <a href="/polityka-cookies">polityka cookies</a>.</p>
      <h2>Mapa Google</h2>
      <p>Na stronie <a href="/kontakt">Kontakt</a> mapa Google Maps nie ładuje się automatycznie. Dopiero po kliknięciu „Pokaż mapę” przeglądarka łączy się z serwerami Google, które mogą przetwarzać adres IP i inne dane techniczne oraz ustawiać własne cookies zgodnie z polityką Google.</p>
      <h2>Odbiorcy danych</h2>
      <p>Dane mogą być powierzane podmiotom obsługującym hosting lub infrastrukturę IT, wyłącznie w zakresie niezbędnym do działania serwisu. Przy załadowaniu mapy odbiorcą danych technicznych staje się Google.</p>
      <h2>Okres przechowywania</h2>
      <p>Cookie sesji wygasa po 8 godzinach lub wcześniej po wylogowaniu / restarcie serwera. Dane z korespondencji przechowujemy przez czas potrzebny do obsługi sprawy oraz wynikający z przepisów (np. rachunkowych i podatkowych).</p>
      <h2>Prawa osób</h2>
      <p>Przysługuje Państwu prawo dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia, wniesienia sprzeciwu oraz cofnięcia zgody (jeśli przetwarzanie opiera się na zgodzie), a także złożenia skargi do Prezesa Urzędu Ochrony Danych Osobowych.</p>
      <h2>Kontakt w sprawach RODO</h2>
      <p>W sprawach dotyczących danych osobowych prosimy o kontakt: <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a> lub listownie na adres biura.</p>
      <p class="note">Dokument ma charakter informacyjny i dotyczy serwisu WWW. Szczegółowe klauzule dla klientów biura mogą być przekazywane przy zawieraniu umowy.</p>
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

function render_cookies(array $ctx): string
{
    $page = subpage(
        'Polityka cookies',
        'Informacja o plikach cookies używanych na stronie Biura Rachunkowego AS w Nowym Sączu.',
        '/polityka-cookies',
        '<div class="page-copy legal-copy">
      <p>Ta strona wykorzystuje pliki cookies w ograniczonym zakresie, opisanym poniżej.</p>
      <h2>Czym są cookies</h2>
      <p>Cookies to niewielkie pliki zapisywane w przeglądarce. Mogą być niezbędne do działania serwisu albo pochodzić od podmiotów trzecich (np. po załadowaniu mapy).</p>
      <h2>Cookies własne (niezbędne)</h2>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th scope="col">Nazwa</th>
              <th scope="col">Cel</th>
              <th scope="col">Czas</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row"><code>sid</code></th>
              <td>Sesja użytkownika, token CSRF, bezpieczeństwo logowania i edycji cennika</td>
              <td>do 8 godzin (HttpOnly, SameSite=Lax; Secure przy HTTPS)</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p>Bez cookie <code>sid</code> strona nie może bezpiecznie obsłużyć sesji i formularzy. Jest to cookie niezbędne — nie wymaga zgody w rozumieniu przepisów o cookies, ale informujemy o nim transparentnie.</p>
      <h2>Preferencja banera</h2>
      <p>Po kliknięciu „Rozumiem” w banerze informacyjnym zapisujemy w <code>localStorage</code> przeglądarki informację, że komunikat został zamknięty. Nie jest to cookie HTTP i nie służy do śledzenia.</p>
      <h2>Cookies / dane podmiotów trzecich</h2>
      <p>Nie używamy narzędzi analitycznych ani reklamowych. Jedynym treścią zewnętrzną jest opcjonalna mapa Google na stronie kontaktu — ładowana dopiero po kliknięciu „Pokaż mapę”. Wtedy Google może ustawić własne cookies i przetwarzać dane zgodnie ze swoimi zasadami.</p>
      <h2>Zarządzanie</h2>
      <p>Cookies można usuwać lub blokować w ustawieniach przeglądarki. Zablokowanie <code>sid</code> uniemożliwi logowanie do edycji cennika. Mapę można po prostu nie uruchamiać.</p>
      <p>Więcej o przetwarzaniu danych: <a href="/polityka-prywatnosci">polityka prywatności</a>.</p>
      <p>Administrator: Biuro Rachunkowe AS Aneta Stanik, ul. Bora-Komorowskiego 22/1, 33-300 Nowy Sącz, <a href="mailto:biuro@biuro-as.pl">biuro@biuro-as.pl</a>.</p>
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

const ADVISOR_TEXT = 'Naszą oferta jest szeroka, ale staramy się ją dostosować do potrzeb młodych i początkujących przedsiębiorców. W początkowym okresie istnienia firmy, przedsiębiorcy bardzo często nie współpracują z biurem księgowym, biorąc na siebie wszystkie dokumenty i zobowiązania, które wiążą się z istnieniem firmy. Niepotrzebnie! Jeśli jesteś właśnie w takiej sytuacji, to pokażemy Ci, jak wielkim odciążeniem jest doświadczony księgowy, który nie tylko pomoże Ci z „papierkową robotą”, ale odpowie na wszystkie pytania i wyjaśni nieścisłości.';

function render_advisor(array $ctx): string
{
    $page = subpage(
        'Indywidualny doradca',
        'Indywidualny doradca w Biurze Rachunkowym AS w Nowym Sączu.',
        '/indywidualny-doradca',
        '<div class="page-split">
      <div class="page-copy feature-page">
        <p>' . e(ADVISOR_TEXT) . '</p>
        <p class="hero-actions"><a class="button" href="/oferta">Nasza oferta <span aria-hidden="true">→</span></a></p>
      </div>
      ' . page_media('/img/banner-doradca.jpg', ['width' => 1280, 'height' => 720]) . '
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

function render_support(array $ctx): string
{
    $page = subpage(
        'Wsparcie',
        'Wsparcie księgowe Biura Rachunkowego AS w Nowym Sączu.',
        '/wsparcie',
        '<div class="page-split">
      <div class="page-copy feature-page">
        <p>' . e(ADVISOR_TEXT) . '</p>
        <p class="hero-actions"><a class="button" href="/oferta">Nasza oferta <span aria-hidden="true">→</span></a></p>
      </div>
      ' . page_media('/img/banner-wsparcie.jpg', ['width' => 1280, 'height' => 720]) . '
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

function render_professional(array $ctx): string
{
    $page = subpage(
        'Profesjonalizm',
        'Profesjonalna obsługa księgowa Biura Rachunkowego AS.',
        '/profesjonalizm',
        '<div class="page-split">
      <div class="page-copy feature-page">
        <p>Stosujemy sprawdzone systemy obsługi księgowej, zawsze indywidualnie dostosowane do konkretnych potrzeb klienta. Nasze motto: „Ty rozwijasz swój biznes – my księgujemy!”</p>
        <p class="hero-actions"><a class="button" href="/oferta">Nasza oferta <span aria-hidden="true">→</span></a></p>
      </div>
      ' . page_media('/img/shutterstock_77448604.jpg', ['width' => 1600, 'height' => 799]) . '
    </div>'
    );
    return layout(array_merge($ctx, shell_from($page)));
}

function render_login(array $ctx, string $error = ''): string
{
    $message = $error !== ''
        ? '<p class="form-error" role="alert">' . e($error) . '</p>'
        : '';
    $main = '<article class="wrap narrow">
    <h1>Panel edycji</h1>
    <p>Zaloguj się, a potem wybierz, co chcesz edytować.</p>
    ' . $message . '
    <form class="login-form" method="post" action="/logowanie">
      <input type="hidden" name="csrf" value="' . e((string) $ctx['csrf']) . '">
      <label>Nazwa użytkownika
        <input name="username" autocomplete="username" required maxlength="80">
      </label>
      <label>Hasło
        <input name="password" type="password" autocomplete="current-password" required maxlength="200">
      </label>
      <button type="submit">Zaloguj się</button>
    </form>
  </article>';

    return layout(array_merge($ctx, [
        'title' => 'Logowanie - Biuro rachunkowe AS Nowy Sącz - Księgowość',
        'description' => 'Logowanie do edycji cennika Biura Rachunkowego AS.',
        'active' => '',
        'main' => $main,
    ]));
}
