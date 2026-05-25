<?php

require_once __DIR__ . '/../app/bootstrap.php';

if (defined('APP_ENV') && APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

function serve_static_file(string $path): bool {
    if (!file_exists($path)) {
        return false;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'html' => 'text/html; charset=utf-8',
        'htm' => 'text/html; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'otf' => 'font/otf',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'xml' => 'application/xml',
    ];

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : null;
        if ($finfo) {
            finfo_close($finfo);
        }
    } else {
        $mime = null;
    }

    if (!$mime) {
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    }

    header('Content-Type: ' . $mime);
    readfile($path);
    return true;
}

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$requestFile = __DIR__ . $request;

// Rota raiz (/) mapeada para a homepage estática
if ($request === '/' && !isset($_GET['action'])) {
    if (serve_static_file(__DIR__ . '/assets/pages/index.html')) {
        exit;
    }
}

// Antonio - rotas de noticias (seja via query param action ou rota /noticias)
if (isset($_GET['action']) || $request === '/noticias') {
    require_once __DIR__ . '/../app/Controllers/NoticiaController.php';
    $controller = new NoticiaController();
    $action = $_GET['action'] ?? 'index';

    switch ($action) {
        case 'create': $controller->create(); break;
        case 'view': $controller->view(); break;
        case 'edit': $controller->edit(); break;
        case 'delete': $controller->delete(); break;
        default: $controller->index(); break;
    }
    exit;
}

// Rotas de API
if (strpos($request, '/api/') !== false || $request === '/api' || strpos($request, '/api') === 0) {
    require_once __DIR__ . '/api/router.php';
    exit;
}

// Serve arquivos estáticos já existentes diretamente
if ($request !== '/' && file_exists($requestFile)) {
    return false;
}

// Suporte a páginas root-level como /cadastro.html mapeando para assets/pages
if (preg_match('#^/([^/]+\.html)$#', $request, $matches)) {
    $page = __DIR__ . '/assets/pages/' . $matches[1];
    if (serve_static_file($page)) {
        exit;
    }
}

// Suporte a recursos mapeando /css, /js, /images, /img, /components, /assets e /resources para public/assets/*
if (preg_match('#^/(css|js|img|images|components|assets|resources)/(.*)$#', $request, $matches)) {
    $prefix = $matches[1];
    $rest   = $matches[2];
    $mapped = null;

    if ($prefix === 'css') {
        $mapped = __DIR__ . '/assets/css/' . $rest;
    } elseif ($prefix === 'js') {
        $mapped = __DIR__ . '/assets/js/' . $rest;
    } elseif ($prefix === 'img' || $prefix === 'images') {
        $mapped = __DIR__ . '/assets/images/' . $rest;
    } elseif ($prefix === 'components') {
        $mapped = __DIR__ . '/assets/components/' . $rest;
    } elseif ($prefix === 'assets') {
        $mapped = __DIR__ . '/assets/' . $rest;
    } elseif ($prefix === 'resources') {
        $mapped = __DIR__ . '/../resources/' . $rest;
    }

    if ($mapped && serve_static_file($mapped)) {
        exit;
    }
}

// Se nada acima serviu e não era uma rota mapeada, cai no API router para responder com 404
require_once __DIR__ . '/api/router.php';