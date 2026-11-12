<?php
declare(strict_types=1);

require_once __DIR__ . '/escape.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sanitize.php';
require_once __DIR__ . '/offer.php';
require_once __DIR__ . '/render.php';

function app_root(): string
{
    return dirname(__DIR__);
}

function load_config(): array
{
    $defaults = [
        'admin_username' => 'admin',
        'admin_password' => '',
        'site_url' => 'https://www.biuro-as.pl',
        'trust_proxy' => 0,
        'data_path' => '',
    ];

    $file = app_root() . '/config.php';
    $fromFile = [];
    if (is_file($file)) {
        $loaded = require $file;
        if (is_array($loaded)) {
            $fromFile = $loaded;
        }
    }

    $config = array_merge($defaults, $fromFile);

    $envPass = getenv('ADMIN_PASSWORD');
    if (is_string($envPass) && $envPass !== '') {
        $config['admin_password'] = $envPass;
    }
    $envUser = getenv('ADMIN_USERNAME');
    if (is_string($envUser) && $envUser !== '') {
        $config['admin_username'] = $envUser;
    }
    $envSite = getenv('SITE_URL');
    if (is_string($envSite) && $envSite !== '') {
        $config['site_url'] = $envSite;
    }
    $envTrust = getenv('TRUST_PROXY');
    if ($envTrust !== false && $envTrust !== '') {
        $config['trust_proxy'] = (int) $envTrust;
    }
    $envData = getenv('DATA_PATH');
    if (is_string($envData) && $envData !== '') {
        $config['data_path'] = $envData;
    }

    $config['site_url'] = rtrim((string) $config['site_url'], '/');
    $config['admin_username'] = (string) $config['admin_username'];
    $config['admin_password'] = (string) $config['admin_password'];
    $config['trust_proxy'] = (int) $config['trust_proxy'];
    $config['data_path'] = (string) $config['data_path'];

    return $config;
}

function resolve_data_path(array $config): string
{
    if ($config['data_path'] !== '') {
        return $config['data_path'];
    }
    return app_root() . '/data/cennik';
}
