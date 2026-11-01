<?php

require_once __DIR__ . '/../app/bootstrap.php';

if (defined('APP_ENV') && APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
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

function redirectToLogin(): never
{
    header('Location: /login.html');
    exit;
}

function requirePageCsrf(): void
{
    $expected = $_SESSION['csrf_token'] ?? '';
    $provided = $_POST['csrf_token'] ?? '';
    if (!is_string($expected) || $expected === '' || !is_string($provided) ||
        !hash_equals($expected, $provided)) {
        http_response_code(403);
        echo 'Token de segurança inválido ou ausente. Volte à página anterior e tente novamente.';
        exit;
    }
}

if (preg_match('#^/assets/pages/(perfil-admin|personalizacao|gerenciar-usuarios|perfil-funcionario)\.html$#', $request, $privateAlias)) {
    $requiredRole = in_array($privateAlias[1], ['perfil-admin', 'personalizacao', 'gerenciar-usuarios'], true)
        ? 'admin'
        : 'funcionario';
    $user = \App\Security\Auth::currentUser();
    if (!$user) {
        redirectToLogin();
    }
    if ($user['role'] !== $requiredRole) {
        header('Location: ' . ($user['role'] === 'admin' ? '/perfil-admin.html' : '/perfil-funcionario.html'));
        exit;
    }
    header('Cache-Control: no-store, private');
}

// Rota raiz (/) mapeada para a homepage estática
if ($request === '/' && !isset($_GET['action'])) {
    if (serve_static_file(__DIR__ . '/assets/pages/index.html')) {
        exit;
    }
}

// Antonio - rotas de noticias (seja via query param action ou rota /noticias)

if (isset($_GET['action']) || $request === '/noticias') {
    $action = $_GET['action'] ?? 'index';
    if ($action !== 'view') {
        $user = \App\Security\Auth::currentUser();
        if (!$user) {
            redirectToLogin();
        }
        header('Cache-Control: no-store, private');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requirePageCsrf();
    }

    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo 'Método não permitido.';
        exit;
    }

    require_once __DIR__ . '/../app/Controllers/NoticiaController.php';

    $controller = new \App\Controllers\NoticiaController();

    switch ($action) {
        case 'create':
            $controller->create();
            break;

        case 'view':
            $controller->view();
            break;

        case 'edit':
            $controller->edit();
            break;

        case 'delete':
            $controller->delete();
            break;

        default:
            $controller->index();
            break;
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
    $privatePages = [
        'perfil-admin.html' => 'admin',
        'personalizacao.html' => 'admin',
        'gerenciar-usuarios.html' => 'admin',
        'perfil-funcionario.html' => 'funcionario',
    ];

    if (isset($privatePages[$matches[1]])) {
        $user = \App\Security\Auth::currentUser();
        if (!$user) {
            redirectToLogin();
        }

        if ($user['role'] !== $privatePages[$matches[1]]) {
            header('Location: ' . ($user['role'] === 'admin' ? '/perfil-admin.html' : '/perfil-funcionario.html'));
            exit;
        }
        header('Cache-Control: no-store, private');
    }

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
        if (preg_match('#^pages/(perfil-admin|personalizacao|gerenciar-usuarios|perfil-funcionario)\.html$#', $rest, $pageMatch)) {
            $roles = [
                'perfil-admin' => 'admin',
                'personalizacao' => 'admin',
                'gerenciar-usuarios' => 'admin',
                'perfil-funcionario' => 'funcionario',
            ];
            $user = \App\Security\Auth::currentUser();
            if (!$user) {
                redirectToLogin();
            }
            if ($user['role'] !== $roles[$pageMatch[1]]) {
                header('Location: ' . ($user['role'] === 'admin' ? '/perfil-admin.html' : '/perfil-funcionario.html'));
                exit;
            }
            header('Cache-Control: no-store, private');
        }
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