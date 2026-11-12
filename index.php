<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$APP_CONFIG = load_config();
$db = open_price_store(resolve_data_path($APP_CONFIG));
$offerStore = open_offer_store(resolve_offer_data_path($APP_CONFIG));

$method = request_method();
$path = request_path();

if ($path === null) {
    send_html(400, page_error('Błędny adres', 'Nie udało się odczytać adresu.'), $APP_CONFIG);
    exit;
}

if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
    send_html(405, page_error('Niedozwolona metoda', 'Ta metoda nie jest obsługiwana.'), $APP_CONFIG);
    exit;
}

start_app_session($APP_CONFIG);

if ($path === '/kontakt-2') {
    redirect_to('/kontakt', $APP_CONFIG);
}

$pages = [
    '/' => 'render_home',
    '/o-firmie' => 'render_about',
    '/zdalnaksiegowosc' => 'render_remote',
    '/kontakt' => 'render_contact',
    '/indywidualny-doradca' => 'render_advisor',
    '/wsparcie' => 'render_support',
    '/profesjonalizm' => 'render_professional',
    '/polityka-prywatnosci' => 'render_privacy',
    '/polityka-cookies' => 'render_cookies',
];

function require_mutations(array $config, array $form): bool
{
    if (!origin_ok($config) || !csrf_ok($form['csrf'])) {
        send_html(403, page_error('Odmowa', 'Żądanie zostało odrzucone.'), $config);
        return false;
    }
    if (session_user() !== 'admin') {
        send_html(403, page_error('Wymagane logowanie', 'Zaloguj się, aby edytować treści.'), $config);
        return false;
    }
    return true;
}

if ($method === 'POST') {
    $form = read_post();

    if ($path === '/logowanie') {
        if (!origin_ok($APP_CONFIG) || !csrf_ok($form['csrf'])) {
            send_html(403, page_error('Odmowa', 'Żądanie zostało odrzucone.'), $APP_CONFIG);
            exit;
        }
        $ip = client_address($APP_CONFIG);
        if (rate_limited($ip)) {
            set_flash('Zbyt wiele prób logowania. Spróbuj ponownie później.');
            redirect_to('/logowanie', $APP_CONFIG);
        }
        if (!credentials_match($APP_CONFIG, $form['username'], $form['password'])) {
            record_failure($ip);
            set_flash('Nieprawidłowa nazwa użytkownika lub hasło.');
            redirect_to('/logowanie', $APP_CONFIG);
        }
        clear_failures($ip);
        regenerate_session_auth($APP_CONFIG);
        redirect_to('/admin', $APP_CONFIG);
    }

    if ($path === '/wyloguj') {
        if (!origin_ok($APP_CONFIG) || !csrf_ok($form['csrf'])) {
            send_html(403, page_error('Odmowa', 'Żądanie zostało odrzucone.'), $APP_CONFIG);
            exit;
        }
        destroy_app_session();
        redirect_to('/', $APP_CONFIG);
    }

    if ($path === '/cennik') {
        if (!require_mutations($APP_CONFIG, $form)) {
            exit;
        }
        $name = clean_text($form['name'], 160);
        $price = clean_text($form['price'], 80);
        if ($name === null || $price === null) {
            set_flash('Uzupełnij nazwę i cenę.');
            redirect_to('/cennik', $APP_CONFIG);
        }
        $db->create($name, $price);
        set_flash('Dodano pozycję cennika.');
        redirect_to('/cennik', $APP_CONFIG);
    }

    if (preg_match('#^/cennik/(\d+)$#', $path, $m)) {
        if (!require_mutations($APP_CONFIG, $form)) {
            exit;
        }
        $name = clean_text($form['name'], 160);
        $price = clean_text($form['price'], 80);
        if ($name === null || $price === null) {
            set_flash('Uzupełnij nazwę i cenę.');
            redirect_to('/cennik', $APP_CONFIG);
        }
        $ok = $db->update((int) $m[1], $name, $price);
        set_flash($ok ? 'Zapisano pozycję cennika.' : 'Nie znaleziono pozycji.');
        redirect_to('/cennik', $APP_CONFIG);
    }

    if (preg_match('#^/cennik/(\d+)/usun$#', $path, $m)) {
        if (!require_mutations($APP_CONFIG, $form)) {
            exit;
        }
        $ok = $db->remove((int) $m[1]);
        set_flash($ok ? 'Usunięto pozycję cennika.' : 'Nie znaleziono pozycji.');
        redirect_to('/cennik', $APP_CONFIG);
    }

    if (preg_match('#^/admin/oferta/(lead|ksiegowosc|kadry|pit)$#', $path, $m)) {
        if (!require_mutations($APP_CONFIG, $form)) {
            exit;
        }
        $part = $m[1];
        $current = $offerStore->get();
        if ($part === 'lead') {
            $current['lead'] = $form['lead'];
        } else {
            $current['sections'][$part] = $form['sections'][$part];
        }
        $offerStore->save($current);
        set_flash('Zapisano. Możesz wybrać kolejną treść do edycji.');
        redirect_to('/admin', $APP_CONFIG);
    }

    send_html(404, page_error('Nie znaleziono', 'Nie ma takiej strony.'), $APP_CONFIG);
    exit;
}

$flash = take_flash();

if ($path === '/logowanie') {
    if (session_user() === 'admin') {
        redirect_to('/admin', $APP_CONFIG);
    }
    send_html(200, render_login(view_context(''), $flash), $APP_CONFIG);
    exit;
}

if ($path === '/cennik') {
    send_html(200, render_prices(view_context($flash), $db->list()), $APP_CONFIG);
    exit;
}

if ($path === '/admin' || $path === '/admin/oferta') {
    if (session_user() !== 'admin') {
        set_flash('Zaloguj się, aby edytować treści.');
        redirect_to('/logowanie', $APP_CONFIG);
    }
    if ($path === '/admin/oferta') {
        redirect_to('/admin', $APP_CONFIG);
    }
    send_html(200, render_admin_hub(view_context($flash), $offerStore->get()), $APP_CONFIG);
    exit;
}

if (preg_match('#^/admin/oferta/(lead|ksiegowosc|kadry|pit)$#', $path, $m)) {
    if (session_user() !== 'admin') {
        set_flash('Zaloguj się, aby edytować treści.');
        redirect_to('/logowanie', $APP_CONFIG);
    }
    send_html(
        200,
        render_offer_part_editor(view_context($flash), $offerStore->get(), $m[1]),
        $APP_CONFIG
    );
    exit;
}

if ($path === '/oferta') {
    send_html(200, render_offer(view_context($flash), $offerStore->get()), $APP_CONFIG);
    exit;
}

if (!isset($pages[$path])) {
    send_html(404, page_error('Nie znaleziono', 'Nie ma takiej strony.'), $APP_CONFIG);
    exit;
}

$renderer = $pages[$path];
send_html(200, $renderer(view_context($flash)), $APP_CONFIG);
