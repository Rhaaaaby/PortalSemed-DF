<?php

$publicPath = __DIR__ . '/public';
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$query   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?: '';

// Serve arquivos existentes no diretório raiz se houver.
$rootFile = __DIR__ . $request;
if ($request !== '/' && file_exists($rootFile)) {
    return false;
}

// Redireciona pedidos para /public/* para o diretório public interno.
if (strpos($request, '/public') === 0) {
    $trimmed = substr($request, strlen('/public')) ?: '/';
    $publicFile = $publicPath . $trimmed;

    if ($trimmed !== '/' && file_exists($publicFile)) {
        return false;
    }

    $_SERVER['REQUEST_URI'] = $trimmed . ($query ? '?' . $query : '');
    chdir($publicPath);
    require_once $publicPath . '/index.php';
    return;
}

// Se for página ou rota sem arquivo físico, serve a aplicação public.
$publicFile = $publicPath . $request;
if ($request !== '/' && file_exists($publicFile)) {
    return false;
}

chdir($publicPath);
require_once $publicPath . '/index.php';
